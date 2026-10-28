<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Enums\ActivityModule;
use App\Models\Invoice;
use App\Models\PaymentCategory;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Impor tunggakan awal: tagihan santri yang belum lunas saat sistem mulai dipakai.
 *
 * Tagihan dibuat sebesar SISA yang masih harus dibayar. Bagian yang sudah
 * dibayar sebelum sistem dipakai tidak dicatat sebagai kas masuk (sudah
 * termasuk saldo awal), hanya disebut di catatan tagihan.
 */
class ArrearsImportService
{
    public const IMPORT_NOTE = 'Tunggakan awal migrasi dari pembukuan lama.';

    /**
     * @var array<string, string>
     */
    public const COLUMN_ALIASES = [
        'NIS' => 'nis',
        'NAMA' => 'nama',
        'NAMA SANTRI' => 'nama',
        'NAMA LENGKAP' => 'nama',
        'JENIS TAGIHAN' => 'jenis',
        'JENIS' => 'jenis',
        'POS' => 'jenis',
        'KATEGORI' => 'jenis',
        'PERIODE' => 'periode',
        'BULAN' => 'periode',
        'NOMINAL TAGIHAN' => 'nominal',
        'NOMINAL' => 'nominal',
        'TAGIHAN' => 'nominal',
        'SUDAH DIBAYAR' => 'dibayar',
        'DIBAYAR' => 'dibayar',
        'SISA' => 'sisa',
        'SISA TAGIHAN' => 'sisa',
        'JATUH TEMPO' => 'jatuh_tempo',
        'KETERANGAN' => 'keterangan',
    ];

    private const MONTHS = [
        'januari' => 1, 'jan' => 1, 'february' => 2, 'februari' => 2, 'feb' => 2, 'maret' => 3, 'mar' => 3,
        'april' => 4, 'apr' => 4, 'mei' => 5, 'juni' => 6, 'jun' => 6, 'juli' => 7, 'jul' => 7,
        'agustus' => 8, 'agu' => 8, 'agt' => 8, 'september' => 9, 'sep' => 9, 'sept' => 9,
        'oktober' => 10, 'okt' => 10, 'november' => 11, 'nov' => 11, 'desember' => 12, 'des' => 12,
    ];

    public function __construct(private readonly InvoiceNumberService $invoiceNumbers) {}

    /**
     * @return array{
     *     imported: array<int, array{nis: string, nama: string, tagihan: string, sisa: float}>,
     *     skipped: array<int, array{baris: int, nis: string, alasan: string}>,
     *     total: float
     * }
     */
    public function import(string $path, ?int $userId = null): array
    {
        $rows = CsvImportReader::read($path, self::COLUMN_ALIASES, ['nis', 'jenis']);

        if ($rows === []) {
            throw new \RuntimeException('Berkas CSV tidak berisi data tunggakan.');
        }

        $categories = PaymentCategory::all();

        return DB::transaction(function () use ($rows, $categories, $userId, $path) {
            $imported = [];
            $skipped = [];

            foreach ($rows as $index => $row) {
                $line = $index + 2;
                $nis = trim($row['nis'] ?? '');
                $student = $nis !== '' ? Student::where('nis', $nis)->first() : null;

                if (! $student) {
                    $skipped[] = ['baris' => $line, 'nis' => $nis ?: '-', 'alasan' => 'NIS tidak ditemukan'];

                    continue;
                }

                $category = $this->findCategory($categories, $row['jenis'] ?? '');

                if (! $category) {
                    $skipped[] = ['baris' => $line, 'nis' => $nis, 'alasan' => 'Jenis tagihan tidak dikenali: '.($row['jenis'] ?? '')];

                    continue;
                }

                $amount = CsvImportReader::parseAmount($row['nominal'] ?? '');
                $paid = CsvImportReader::parseAmount($row['dibayar'] ?? '');
                $remaining = ($row['sisa'] ?? '') !== ''
                    ? CsvImportReader::parseAmount($row['sisa'])
                    : $amount - $paid;

                if ($remaining <= 0) {
                    $skipped[] = ['baris' => $line, 'nis' => $nis, 'alasan' => 'Sisa tagihan nol, sudah lunas'];

                    continue;
                }

                $period = $this->parsePeriod($row['periode'] ?? '');

                if ($category->payment_type === 'monthly' && $period === null) {
                    $skipped[] = ['baris' => $line, 'nis' => $nis, 'alasan' => 'Periode bulan wajib diisi untuk tagihan bulanan, contoh 2026-08'];

                    continue;
                }

                $title = $this->titleFor($category, $period, $row['keterangan'] ?? '');

                $duplicate = Invoice::where('student_id', $student->id)
                    ->where('payment_category_id', $category->id)
                    ->where('status', '!=', 'cancelled')
                    ->when($period !== null, fn ($query) => $query
                        ->where('period_month', $period['month'])
                        ->where('period_year', $period['year']))
                    ->when($period === null, fn ($query) => $query->where('title', $title))
                    ->exists();

                if ($duplicate) {
                    $skipped[] = ['baris' => $line, 'nis' => $nis, 'alasan' => 'Sudah ada tagihan '.$title.' untuk santri ini'];

                    continue;
                }

                $dueDate = $this->parseDueDate($row['jatuh_tempo'] ?? '', $period);
                $createdAt = $period !== null
                    ? Carbon::create($period['year'], $period['month'], 1)
                    : $dueDate->copy()->startOfMonth();

                $notes = self::IMPORT_NOTE;

                if ($amount > 0 && $amount !== $remaining) {
                    $notes .= sprintf(
                        ' Tagihan semula Rp %s, sudah dibayar Rp %s sebelum sistem dipakai.',
                        number_format($amount, 0, ',', '.'),
                        number_format($amount - $remaining, 0, ',', '.'),
                    );
                }

                $invoice = Invoice::create([
                    'invoice_number' => $this->invoiceNumbers->generate($category->code, 1, $createdAt)[0],
                    'student_id' => $student->id,
                    'payment_category_id' => $category->id,
                    'title' => mb_substr($title, 0, 150),
                    'amount' => $remaining,
                    'paid_amount' => 0,
                    'due_date' => $dueDate->toDateString(),
                    'status' => 'unpaid',
                    'period_month' => $period['month'] ?? null,
                    'period_year' => $period['year'] ?? null,
                    'notes' => $notes,
                ]);

                $imported[] = [
                    'nis' => $nis,
                    'nama' => $student->nama_lengkap,
                    'tagihan' => $invoice->invoice_number.' '.$title,
                    'sisa' => $remaining,
                ];
            }

            $total = (float) array_sum(array_column($imported, 'sisa'));

            ActivityLogService::record(
                module: ActivityModule::Invoice,
                action: ActivityAction::Created,
                description: 'Mengimpor '.count($imported).' tunggakan awal dari berkas '.basename($path).'.',
                properties: [
                    'Berkas' => basename($path),
                    'Tagihan Dibuat' => (string) count($imported),
                    'Baris Dilewati' => (string) count($skipped),
                    'Total Tunggakan' => 'Rp '.number_format($total, 0, ',', '.'),
                ],
                userId: $userId,
            );

            return [
                'imported' => $imported,
                'skipped' => $skipped,
                'total' => $total,
            ];
        });
    }

    /**
     * @param  Collection<int, PaymentCategory>  $categories
     */
    private function findCategory($categories, string $value): ?PaymentCategory
    {
        $needle = strtoupper(trim($value));

        if ($needle === '') {
            return null;
        }

        $byCode = $categories->first(fn (PaymentCategory $category) => strtoupper($category->code) === $needle);

        if ($byCode) {
            return $byCode;
        }

        $aliases = [
            'SPP' => ['SPP', 'IURAN', 'BULANAN', 'SYAHRIAH'],
            'WAKAF' => ['WAKAF'],
            'FASILITAS' => ['FASILITAS', 'KAMAR', 'ASRAMA'],
            'FORMULIR' => ['FORMULIR', 'PENDAFTARAN', 'DAFTAR'],
            'BUKU' => ['BUKU', 'KITAB'],
            'EVENT' => ['EVENT', 'KEGIATAN', 'RIHLAH', 'CAMPING', 'WISUDA'],
        ];

        foreach ($aliases as $code => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($needle, $keyword)) {
                    $match = $categories->first(fn (PaymentCategory $category) => strtoupper($category->code) === $code);

                    if ($match) {
                        return $match;
                    }
                }
            }
        }

        return $categories->first(fn (PaymentCategory $category) => str_contains(strtoupper($category->name), $needle));
    }

    /**
     * Terima "2026-08", "08/2026", "8-2026", "Agustus 2026", "Agu 2026".
     *
     * @return array{month: int, year: int}|null
     */
    private function parsePeriod(string $value): ?array
    {
        $value = strtolower(trim($value));

        if ($value === '') {
            return null;
        }

        if (preg_match('/^(\d{4})[-\/.](\d{1,2})$/', $value, $m) === 1) {
            return $this->validPeriod((int) $m[2], (int) $m[1]);
        }

        if (preg_match('/^(\d{1,2})[-\/.](\d{4})$/', $value, $m) === 1) {
            return $this->validPeriod((int) $m[1], (int) $m[2]);
        }

        if (preg_match('/^([a-z]+)\s*[-\/]?\s*(\d{4})$/', $value, $m) === 1 && isset(self::MONTHS[$m[1]])) {
            return $this->validPeriod(self::MONTHS[$m[1]], (int) $m[2]);
        }

        return null;
    }

    /**
     * @return array{month: int, year: int}|null
     */
    private function validPeriod(int $month, int $year): ?array
    {
        if ($month < 1 || $month > 12 || $year < 2000 || $year > 2100) {
            return null;
        }

        return ['month' => $month, 'year' => $year];
    }

    /**
     * @param  array{month: int, year: int}|null  $period
     */
    private function parseDueDate(string $value, ?array $period): Carbon
    {
        if (trim($value) !== '') {
            try {
                return Carbon::parse(trim($value));
            } catch (\Throwable) {
                // Jatuh ke tanggal bawaan di bawah.
            }
        }

        if ($period !== null) {
            return Carbon::create($period['year'], $period['month'], 10);
        }

        return now()->endOfMonth();
    }

    /**
     * @param  array{month: int, year: int}|null  $period
     */
    private function titleFor(PaymentCategory $category, ?array $period, string $keterangan): string
    {
        $base = trim($keterangan) !== '' ? trim($keterangan) : $category->name;

        if ($period !== null) {
            $base .= ' - '.Carbon::create($period['year'], $period['month'], 1)->translatedFormat('F Y');
        }

        return $base;
    }
}
