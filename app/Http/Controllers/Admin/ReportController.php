<?php

namespace App\Http\Controllers\Admin;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentCategory;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController
{
    /**
     * Laporan Penerimaan Kas Masuk (Harian / Rentang Tanggal / Bulanan)
     */
    public function cashInflow(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());
        $categoryId = $request->input('category_id');
        $paymentMethod = $request->input('payment_method');
        $kelompok = $request->input('kelompok');

        // Kolom tabel yang boleh diurutkan (whitelist mencegah kolom tak dikenal).
        $allowedSorts = [
            'id' => 'id',
            'receipt_number' => 'receipt_number',
            'payment_date' => 'payment_date',
            'student_name' => 'student_name',
            'category' => 'category',
            'payment_method' => 'payment_method',
            'amount_paid' => 'amount_paid',
        ];

        $sortBy = $request->input('sort_by', 'payment_date');
        $sortOrder = strtolower((string) $request->input('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (! array_key_exists($sortBy, $allowedSorts)) {
            $sortBy = 'payment_date';
        }

        $query = Payment::with(['student', 'invoice.category', 'receipt'])
            ->whereNull('voided_at')
            ->whereDate('payment_date', '>=', $startDate)
            ->whereDate('payment_date', '<=', $endDate);

        if ($categoryId) {
            $query->whereHas('invoice', function ($q) use ($categoryId) {
                $q->where('payment_category_id', $categoryId);
            });
        }

        if ($paymentMethod && in_array($paymentMethod, ['cash', 'transfer'])) {
            $query->where('payment_method', $paymentMethod);
        }

        if (in_array($kelompok, ['putra', 'putri'], true)) {
            $query->whereHas('student', fn ($q) => $q->where('santri_group', $kelompok));
        }

        // Urutan kolom. Sebagian kolom berasal dari tabel relasi sehingga perlu join.
        if ($sortBy === 'student_name') {
            $query->leftJoin('students', 'payments.student_id', '=', 'students.id')
                ->orderBy('students.nama_lengkap', $sortOrder)
                ->select('payments.*');
        } elseif ($sortBy === 'category') {
            $query->leftJoin('invoices', 'payments.invoice_id', '=', 'invoices.id')
                ->leftJoin('payment_categories', 'invoices.payment_category_id', '=', 'payment_categories.id')
                ->orderBy('payment_categories.name', $sortOrder)
                ->select('payments.*');
        } elseif ($sortBy === 'receipt_number') {
            $query->leftJoin('receipts', 'receipts.payment_id', '=', 'payments.id')
                ->orderBy('receipts.receipt_number', $sortOrder)
                ->select('payments.*');
        } else {
            $query->orderBy($allowedSorts[$sortBy], $sortOrder);
        }

        // Urutan sekunder agar posisi baris tetap konsisten pada nilai yang sama.
        if ($sortBy !== 'id') {
            $query->orderBy('payments.id', 'desc');
        }

        $payments = $query->get();

        // Metrik Ringkasan
        $totalPenerimaan = $payments->sum('amount_paid');
        $totalTransaksi = $payments->count();
        $totalCash = $payments->where('payment_method', 'cash')->sum('amount_paid');
        $totalTransfer = $payments->where('payment_method', 'transfer')->sum('amount_paid');

        // Rekapitulasi per Kategori Biaya
        $categoryBreakdown = $payments->groupBy(function ($payment) {
            return $payment->invoice->category->name ?? 'Lain-lain';
        })->map(function ($group) {
            return [
                'count' => $group->count(),
                'total' => $group->sum('amount_paid'),
            ];
        })->sortByDesc('total');

        $categories = PaymentCategory::where('is_active', true)->orderBy('name')->get();

        return view('admin.reports.cash-inflow', compact(
            'payments',
            'startDate',
            'endDate',
            'categoryId',
            'paymentMethod',
            'kelompok',
            'totalPenerimaan',
            'totalTransaksi',
            'totalCash',
            'totalTransfer',
            'categoryBreakdown',
            'categories',
            'sortBy',
            'sortOrder'
        ));
    }

    /**
     * Export Laporan Penerimaan Kas ke CSV / Excel
     */
    public function exportCashInflowCsv(Request $request): StreamedResponse
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());
        $categoryId = $request->input('category_id');
        $paymentMethod = $request->input('payment_method');
        $kelompok = $request->input('kelompok');

        $query = Payment::with(['student', 'invoice.category', 'receipt'])
            ->whereNull('voided_at')
            ->whereDate('payment_date', '>=', $startDate)
            ->whereDate('payment_date', '<=', $endDate)
            ->orderBy('payment_date', 'asc');

        if ($categoryId) {
            $query->whereHas('invoice', function ($q) use ($categoryId) {
                $q->where('payment_category_id', $categoryId);
            });
        }

        if ($paymentMethod && in_array($paymentMethod, ['cash', 'transfer'])) {
            $query->where('payment_method', $paymentMethod);
        }

        if (in_array($kelompok, ['putra', 'putri'], true)) {
            $query->whereHas('student', fn ($q) => $q->where('santri_group', $kelompok));
        }

        $payments = $query->get();
        $filename = "Laporan_Penerimaan_Kas_PTQ_Amsa_{$startDate}_sd_{$endDate}.csv";

        return response()->streamDownload(function () use ($payments, $startDate, $endDate) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM agar terbaca sempurna di Microsoft Excel
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['LAPORAN PENERIMAAN KAS - PESANTREN TADABBUR AL-QURAN AMSA001']);
            fputcsv($handle, ["Periode: {$startDate} s/d {$endDate}"]);
            fputcsv($handle, ['Tanggal Unduh: '.Carbon::now()->translatedFormat('d F Y H:i:s')]);
            fputcsv($handle, []);

            // Header Tabel
            fputcsv($handle, [
                'No',
                'No Kuitansi',
                'Tanggal',
                'NIS',
                'Nama Santri',
                'Kelas',
                'Kelompok',
                'Kategori Tagihan',
                'Keterangan Tagihan',
                'Metode',
                'Nominal (Rp)',
                'Catatan',
            ]);

            $totalNominal = 0;
            foreach ($payments as $index => $pay) {
                $totalNominal += (float) $pay->amount_paid;
                fputcsv($handle, [
                    $index + 1,
                    $pay->receipt->receipt_number ?? '-',
                    $pay->payment_date ? $pay->payment_date->format('d/m/Y') : '-',
                    "'".($pay->student->nis ?? '-'),
                    $pay->student->nama_lengkap ?? '-',
                    $pay->student->kelas ?? '-',
                    $pay->student->kelompok ?? '-',
                    $pay->invoice->category->name ?? '-',
                    $pay->invoice->title ?? '-',
                    strtoupper($pay->payment_method),
                    (float) $pay->amount_paid,
                    $pay->notes ?? '-',
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['', '', '', '', '', '', '', '', '', 'TOTAL PENERIMAAN:', $totalNominal]);

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Unduh Laporan Penerimaan Kas dalam format PDF Resmi
     */
    public function exportCashInflowPdf(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());
        $categoryId = $request->input('category_id');
        $paymentMethod = $request->input('payment_method');
        $kelompok = $request->input('kelompok');

        $query = Payment::with(['student', 'invoice.category', 'receipt'])
            ->whereNull('voided_at')
            ->whereDate('payment_date', '>=', $startDate)
            ->whereDate('payment_date', '<=', $endDate)
            ->orderBy('payment_date', 'asc');

        if ($categoryId) {
            $query->whereHas('invoice', function ($q) use ($categoryId) {
                $q->where('payment_category_id', $categoryId);
            });
        }

        if ($paymentMethod && in_array($paymentMethod, ['cash', 'transfer'])) {
            $query->where('payment_method', $paymentMethod);
        }

        if (in_array($kelompok, ['putra', 'putri'], true)) {
            $query->whereHas('student', fn ($q) => $q->where('santri_group', $kelompok));
        }

        $payments = $query->get();

        $totalPenerimaan = $payments->sum('amount_paid');
        $totalTransaksi = $payments->count();
        $totalCash = $payments->where('payment_method', 'cash')->sum('amount_paid');
        $totalTransfer = $payments->where('payment_method', 'transfer')->sum('amount_paid');

        $categoryBreakdown = $payments->groupBy(function ($payment) {
            return $payment->invoice->category->name ?? 'Lain-lain';
        })->map(function ($group) {
            return [
                'count' => $group->count(),
                'total' => $group->sum('amount_paid'),
            ];
        })->sortByDesc('total');

        $pdf = Pdf::loadView('pdf.cash-inflow-report', compact(
            'payments',
            'startDate',
            'endDate',
            'kelompok',
            'totalPenerimaan',
            'totalTransaksi',
            'totalCash',
            'totalTransfer',
            'categoryBreakdown'
        ))->setPaper('a4', 'landscape');

        $filename = "Laporan-Kas-PTQ-Amsa-{$startDate}-sd-{$endDate}.pdf";

        return $pdf->stream($filename);
    }

    /**
     * Laporan Rekapitulasi Tunggakan Santri (Outstanding Invoices)
     */
    public function arrears(Request $request)
    {
        $selectedClass = $request->input('kelas');
        $categoryId = $request->input('category_id');
        $search = $request->input('search');
        $kelompok = $request->input('kelompok');

        // Query tagihan yang belum lunas
        $invoiceQuery = Invoice::with(['student', 'category'])
            ->whereIn('status', ['unpaid', 'partial']);

        if ($categoryId) {
            $invoiceQuery->where('payment_category_id', $categoryId);
        }

        if ($selectedClass) {
            $invoiceQuery->whereHas('student', function ($q) use ($selectedClass) {
                $q->where('kelas', $selectedClass);
            });
        }

        if (in_array($kelompok, ['putra', 'putri'], true)) {
            $invoiceQuery->whereHas('student', fn ($q) => $q->where('santri_group', $kelompok));
        }

        if ($search) {
            $invoiceQuery->whereHas('student', function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%");
            });
        }

        $unpaidInvoices = $invoiceQuery->get();

        // Grouping berdasarkan santri
        $studentArrears = $unpaidInvoices->groupBy('student_id')->map(function ($invoices) {
            $student = $invoices->first()->student;
            $totalTunggakan = $invoices->sum(function ($inv) {
                return $inv->remaining_amount;
            });

            return [
                'student' => $student,
                'invoices' => $invoices,
                'total_tunggakan' => $totalTunggakan,
                'count_invoices' => $invoices->count(),
            ];
        })->sortByDesc('total_tunggakan');

        $totalSantriMenunggak = $studentArrears->count();
        $totalNilaiTunggakan = $studentArrears->sum('total_tunggakan');
        $totalTagihanTertunggak = $unpaidInvoices->count();

        // Master data untuk filter
        $availableClasses = Student::where('status', 'aktif')->select('kelas')->distinct()->pluck('kelas')->filter();
        $categories = PaymentCategory::where('is_active', true)->orderBy('name')->get();

        return view('admin.reports.arrears', compact(
            'studentArrears',
            'totalSantriMenunggak',
            'totalNilaiTunggakan',
            'totalTagihanTertunggak',
            'availableClasses',
            'categories',
            'selectedClass',
            'categoryId',
            'search',
            'kelompok'
        ));
    }

    /**
     * Export Laporan Tunggakan ke CSV / Excel
     */
    public function exportArrearsCsv(Request $request): StreamedResponse
    {
        $selectedClass = $request->input('kelas');
        $categoryId = $request->input('category_id');
        $kelompok = $request->input('kelompok');

        $invoiceQuery = Invoice::with(['student', 'category'])
            ->whereIn('status', ['unpaid', 'partial']);

        if ($categoryId) {
            $invoiceQuery->where('payment_category_id', $categoryId);
        }

        if ($selectedClass) {
            $invoiceQuery->whereHas('student', function ($q) use ($selectedClass) {
                $q->where('kelas', $selectedClass);
            });
        }

        if (in_array($kelompok, ['putra', 'putri'], true)) {
            $invoiceQuery->whereHas('student', fn ($q) => $q->where('santri_group', $kelompok));
        }

        $unpaidInvoices = $invoiceQuery->get();
        $studentArrears = $unpaidInvoices->groupBy('student_id')->map(function ($invoices) {
            $student = $invoices->first()->student;
            $totalTunggakan = $invoices->sum(function ($inv) {
                return $inv->remaining_amount;
            });

            return [
                'student' => $student,
                'invoices' => $invoices,
                'total_tunggakan' => $totalTunggakan,
            ];
        })->sortByDesc('total_tunggakan');

        $filename = 'Laporan_Tunggakan_Santri_PTQ_Amsa_'.Carbon::now()->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($studentArrears) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['LAPORAN TUNGGAKAN BIAYA SANTRI - PTQ AMSA001']);
            fputcsv($handle, ['Tanggal Cetak: '.Carbon::now()->translatedFormat('d F Y H:i:s')]);
            fputcsv($handle, []);

            fputcsv($handle, [
                'No',
                'NIS',
                'Nama Lengkap Santri',
                'Kelas',
                'Kelompok',
                'Nama Wali',
                'No WhatsApp Wali',
                'Rincian Tagihan Tertunggak',
                'Total Tunggakan (Rp)',
            ]);

            $totalSemua = 0;
            $index = 1;
            foreach ($studentArrears as $item) {
                $totalSemua += $item['total_tunggakan'];
                $rincian = $item['invoices']->map(function ($inv) {
                    return "{$inv->title} (Sisa Rp ".number_format($inv->remaining_amount, 0, ',', '.').')';
                })->implode('; ');

                fputcsv($handle, [
                    $index++,
                    "'".($item['student']->nis ?? '-'),
                    $item['student']->nama_lengkap ?? '-',
                    $item['student']->kelas ?? '-',
                    $item['student']->kelompok ?? '-',
                    $item['student']->nama_wali ?? '-',
                    "'".($item['student']->no_whatsapp_wali ?? '-'),
                    $rincian,
                    $item['total_tunggakan'],
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['', '', '', '', '', '', '', 'TOTAL SELURUH TUNGGAKAN:', $totalSemua]);

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Unduh Laporan Tunggakan dalam format PDF Resmi
     */
    public function exportArrearsPdf(Request $request)
    {
        $selectedClass = $request->input('kelas');
        $categoryId = $request->input('category_id');
        $kelompok = $request->input('kelompok');

        $invoiceQuery = Invoice::with(['student', 'category'])
            ->whereIn('status', ['unpaid', 'partial']);

        if ($categoryId) {
            $invoiceQuery->where('payment_category_id', $categoryId);
        }

        if ($selectedClass) {
            $invoiceQuery->whereHas('student', function ($q) use ($selectedClass) {
                $q->where('kelas', $selectedClass);
            });
        }

        if (in_array($kelompok, ['putra', 'putri'], true)) {
            $invoiceQuery->whereHas('student', fn ($q) => $q->where('santri_group', $kelompok));
        }

        $unpaidInvoices = $invoiceQuery->get();
        $studentArrears = $unpaidInvoices->groupBy('student_id')->map(function ($invoices) {
            $student = $invoices->first()->student;
            $totalTunggakan = $invoices->sum(function ($inv) {
                return $inv->remaining_amount;
            });

            return [
                'student' => $student,
                'invoices' => $invoices,
                'total_tunggakan' => $totalTunggakan,
            ];
        })->sortByDesc('total_tunggakan');

        $totalSantriMenunggak = $studentArrears->count();
        $totalNilaiTunggakan = $studentArrears->sum('total_tunggakan');

        $pdf = Pdf::loadView('pdf.arrears-report', compact(
            'studentArrears',
            'totalSantriMenunggak',
            'totalNilaiTunggakan',
            'selectedClass',
            'kelompok'
        ))->setPaper('a4', 'portrait');

        $filename = 'Laporan-Tunggakan-Santri-PTQ-Amsa-'.Carbon::now()->format('Ymd').'.pdf';

        return $pdf->stream($filename);
    }
}
