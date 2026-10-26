<?php

namespace App\Services;

use App\Models\BookClosing;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class BookClosingService
{
    public function __construct(private readonly CashFlowService $cashFlow) {}

    /**
     * Ringkasan kas satu bulan penuh, dihitung dari transaksi aktif.
     *
     * @return array{
     *     opening_balance: float,
     *     total_in: float,
     *     total_out: float,
     *     closing_balance: float,
     *     payment_count: int,
     *     expense_count: int
     * }
     */
    public function summary(int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $flow = $this->cashFlow->build($start, $end);

        return [
            'opening_balance' => (float) $flow['openingBalance'],
            'total_in' => (float) $flow['totalMasuk'],
            'total_out' => (float) $flow['totalKeluar'],
            'closing_balance' => (float) $flow['closingBalance'],
            'payment_count' => (int) $flow['jumlahMasuk'],
            'expense_count' => (int) $flow['jumlahKeluar'],
        ];
    }

    /**
     * Tutup buku satu bulan dan simpan snapshot angkanya.
     */
    public function close(int $year, int $month, ?int $userId = null): BookClosing
    {
        $periodStart = Carbon::create($year, $month, 1)->startOfMonth();

        if ($periodStart->greaterThan(now()->startOfMonth())) {
            throw ValidationException::withMessages([
                'period' => 'Periode yang belum berjalan belum dapat ditutup.',
            ]);
        }

        $summary = $this->summary($year, $month);

        $closing = BookClosing::updateOrCreate(
            ['period_year' => $year, 'period_month' => $month],
            $summary + [
                'closed_by' => $userId,
                'closed_at' => now(),
                'reopen_reason' => null,
                'reopened_by' => null,
                'reopened_at' => null,
            ]
        );

        ActivityLogService::bookClosed($closing, $userId);

        return $closing;
    }

    /**
     * Buka kembali periode yang sudah ditutup agar dapat dikoreksi.
     */
    public function reopen(BookClosing $closing, string $reason, ?int $userId = null): BookClosing
    {
        if (! $closing->isClosed()) {
            throw ValidationException::withMessages([
                'period' => 'Periode ini belum ditutup, jadi tidak perlu dibuka kembali.',
            ]);
        }

        $closing->forceFill([
            'reopened_at' => now(),
            'reopened_by' => $userId,
            'reopen_reason' => $reason,
        ])->save();

        ActivityLogService::bookReopened($closing, $reason, $userId);

        return $closing;
    }

    /**
     * Apakah tanggal tersebut berada pada periode yang sedang dikunci.
     */
    public function isLocked(CarbonInterface|string $date): bool
    {
        $date = $date instanceof CarbonInterface ? $date : Carbon::parse($date);
        $closing = BookClosing::forDate($date);

        return $closing?->isClosed() ?? false;
    }

    /**
     * Hentikan perubahan data kas bila periodenya sudah ditutup.
     *
     * @throws ValidationException
     */
    public function assertOpen(CarbonInterface|string $date, string $activity = 'transaksi kas'): void
    {
        $date = $date instanceof CarbonInterface ? $date : Carbon::parse($date);
        $closing = BookClosing::forDate($date);

        if ($closing?->isClosed()) {
            throw ValidationException::withMessages([
                'period' => sprintf(
                    'Periode %s sudah ditutup pada %s, sehingga %s bertanggal periode tersebut tidak dapat dicatat, diubah, atau dibatalkan. Administrator harus membuka kembali periode ini terlebih dahulu.',
                    $closing->periodLabel(),
                    $closing->closed_at?->translatedFormat('d F Y H:i') ?? '-',
                    $activity,
                ),
            ]);
        }
    }

    public static function periodLabel(int $year, int $month): string
    {
        return Carbon::create($year, $month, 1)->translatedFormat('F Y');
    }
}
