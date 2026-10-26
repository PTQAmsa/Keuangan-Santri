<?php

namespace App\Http\Controllers\Admin;

use App\Models\BookClosing;
use App\Services\BookClosingService;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Penutupan buku bulanan: snapshot laporan + kunci transaksi kas.
 */
class BookClosingController
{
    public function index(Request $request, BookClosingService $service)
    {
        $year = (int) $request->input('year', now()->year);
        $year = max(2025, min((int) now()->year, $year));

        $closings = BookClosing::where('period_year', $year)->get()->keyBy('period_month');

        $rows = [];
        $totals = ['in' => 0.0, 'out' => 0.0];

        for ($month = 1; $month <= 12; $month++) {
            $closing = $closings->get($month);
            $start = Carbon::create($year, $month, 1)->startOfMonth();

            // Angka snapshot hanya dipakai selama periode masih terkunci.
            // Setelah dibuka kembali, tampilkan angka terkini agar koreksi terlihat.
            $summary = $closing?->isClosed()
                ? [
                    'opening_balance' => (float) $closing->opening_balance,
                    'total_in' => (float) $closing->total_in,
                    'total_out' => (float) $closing->total_out,
                    'closing_balance' => (float) $closing->closing_balance,
                    'payment_count' => (int) $closing->payment_count,
                    'expense_count' => (int) $closing->expense_count,
                ]
                : $service->summary($year, $month);

            $totals['in'] += $summary['total_in'];
            $totals['out'] += $summary['total_out'];

            $rows[] = [
                'month' => $month,
                'label' => $start->translatedFormat('F'),
                'start' => $start,
                'is_future' => $start->greaterThan(now()->startOfMonth()),
                'closing' => $closing,
                'summary' => $summary,
            ];
        }

        $closedCount = $closings->filter(fn (BookClosing $closing) => $closing->isClosed())->count();
        $saldoTahun = $rows === [] ? 0.0 : (float) end($rows)['summary']['closing_balance'];

        return view('admin.reports.closing', compact(
            'rows',
            'year',
            'totals',
            'closedCount',
            'saldoTahun',
        ));
    }

    /**
     * Tutup buku satu bulan dan kunci transaksi kas di dalamnya.
     */
    public function store(Request $request, BookClosingService $service)
    {
        $data = $request->validate([
            'period_year' => ['required', 'integer', 'between:2025,'.now()->year],
            'period_month' => ['required', 'integer', 'between:1,12'],
            'confirm_close' => ['accepted'],
        ], [
            'period_year.required' => 'Tahun periode wajib dipilih.',
            'period_month.required' => 'Bulan periode wajib dipilih.',
            'period_year.between' => 'Tahun periode belum dapat ditutup.',
            'confirm_close.accepted' => 'Centang konfirmasi bahwa laporan sudah diperiksa sebelum menutup buku.',
        ]);

        $closing = $service->close((int) $data['period_year'], (int) $data['period_month'], auth()->id());

        return redirect()
            ->route('admin.reports.closing', ['year' => $closing->period_year])
            ->with('success', 'Buku '.$closing->periodLabel().' ditutup. Saldo akhir Rp '.number_format((float) $closing->closing_balance, 0, ',', '.').' terkunci dan transaksi periode ini tidak dapat diubah sampai dibuka kembali.');
    }

    /**
     * Buka kembali periode terkunci. Khusus Administrator (lihat route).
     */
    public function reopen(Request $request, BookClosing $bookClosing, BookClosingService $service)
    {
        $data = $request->validate([
            'reopen_reason' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'reopen_reason.required' => 'Alasan pembukaan kembali wajib diisi.',
            'reopen_reason.min' => 'Alasan pembukaan kembali minimal 5 karakter.',
        ], [
            'reopen_reason' => 'alasan',
        ]);

        $service->reopen($bookClosing, $data['reopen_reason'], auth()->id());

        return redirect()
            ->route('admin.reports.closing', ['year' => $bookClosing->period_year])
            ->with('success', 'Periode '.$bookClosing->periodLabel().' dibuka kembali. Transaksi bulan ini sudah dapat dikoreksi.');
    }
}
