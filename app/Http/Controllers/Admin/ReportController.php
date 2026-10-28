<?php

namespace App\Http\Controllers\Admin;

use App\Models\Invoice;
use App\Models\OtherIncome;
use App\Models\Payment;
use App\Models\PaymentCategory;
use App\Models\Student;
use App\Services\CashFlowService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController
{
    /**
     * Laporan Penerimaan Kas Masuk (Harian / Rentang Tanggal / Bulanan)
     */
    public function cashInflow(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        // Ringkasan laporan selalu per satu bulan penuh dan otomatis berganti
        // saat tanggal 1 bulan berikutnya tiba.
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());
        $categoryId = $request->input('category_id');
        $paymentMethod = $request->input('payment_method');
        $kelompok = $request->input('kelompok');

        // Kolom tabel yang boleh diurutkan (whitelist mencegah kolom tak dikenal).
        $allowedSorts = [
            'id' => 'id',
            'receipt_number' => 'receipt_number',
            'payment_date' => 'payment_date',
            'student_name' => 'student_name',
            'kelompok' => 'kelompok',
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
        } elseif ($sortBy === 'kelompok') {
            $query->leftJoin('students', 'payments.student_id', '=', 'students.id')
                ->orderBy('students.santri_group', $sortOrder)
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

        $otherIncomes = $this->otherIncomesInRange($startDate, $endDate, $paymentMethod, $categoryId, $kelompok);
        $totalLainnya = (float) $otherIncomes->sum('amount');
        $totalKasMasuk = (float) $totalPenerimaan + $totalLainnya;
        $totalCash += (float) $otherIncomes->where('payment_method', 'cash')->sum('amount');
        $totalTransfer += (float) $otherIncomes->where('payment_method', 'transfer')->sum('amount');

        return view('admin.reports.cash-inflow', compact(
            'payments',
            'otherIncomes',
            'startDate',
            'endDate',
            'categoryId',
            'paymentMethod',
            'kelompok',
            'totalPenerimaan',
            'totalLainnya',
            'totalKasMasuk',
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
     * Pemasukan lainnya (donasi, infaq, unit usaha, dsb.) pada rentang laporan.
     * Filter kategori tagihan dan marhalah hanya berlaku untuk pembayaran santri,
     * sehingga saat salah satunya dipakai bagian ini dikosongkan.
     *
     * @return Collection<int, OtherIncome>
     */
    private function otherIncomesInRange(string $startDate, string $endDate, ?string $paymentMethod, mixed $categoryId, mixed $kelompok): Collection
    {
        if ($categoryId || in_array($kelompok, ['putra', 'putri'], true)) {
            return new Collection;
        }

        $query = OtherIncome::with('recordedBy')
            ->whereNull('voided_at')
            ->whereDate('income_date', '>=', $startDate)
            ->whereDate('income_date', '<=', $endDate)
            ->orderBy('income_date')
            ->orderBy('id');

        if ($paymentMethod && in_array($paymentMethod, ['cash', 'transfer'], true)) {
            $query->where('payment_method', $paymentMethod);
        }

        return $query->get();
    }

    /**
     * Kerangka Laporan Arus Kas: kas masuk & kas keluar dalam satu buku kas.
     */
    public function cashFlow(Request $request, CashFlowService $cashFlow)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());

        $allowedSorts = [
            'date',
            'code',
            'description',
            'party',
            'method',
            'masuk',
            'keluar',
            'saldo',
        ];

        $sortBy = (string) $request->input('sort_by', 'date');
        $sortOrder = strtolower((string) $request->input('sort_order', 'asc')) === 'desc' ? 'desc' : 'asc';

        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'date';
        }

        $flow = $cashFlow->build(Carbon::parse($startDate), Carbon::parse($endDate));
        $flow['entries'] = $this->sortCashFlowEntries($flow['entries'], $sortBy, $sortOrder);

        return view('admin.reports.cash-flow', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
            ...$flow,
        ]);
    }

    /**
     * Urutkan buku kas sesuai kolom yang dipilih. Saldo berjalan tetap
     * dihitung secara kronologis oleh CashFlowService.
     *
     * @param  Collection<int, array<string, mixed>>  $entries
     * @return Collection<int, array<string, mixed>>
     */
    private function sortCashFlowEntries(Collection $entries, string $sortBy, string $sortOrder): Collection
    {
        $descending = $sortOrder === 'desc';

        $entries = match ($sortBy) {
            'code' => $entries->sortBy(
                fn (array $entry) => mb_strtolower((string) $entry['code']),
                SORT_NATURAL | SORT_FLAG_CASE,
                $descending
            ),
            'description' => $entries->sortBy(
                fn (array $entry) => mb_strtolower((string) $entry['description']),
                SORT_NATURAL | SORT_FLAG_CASE,
                $descending
            ),
            'party' => $entries->sortBy(
                fn (array $entry) => mb_strtolower((string) $entry['party']),
                SORT_NATURAL | SORT_FLAG_CASE,
                $descending
            ),
            'method' => $entries->sortBy(
                fn (array $entry) => mb_strtolower((string) $entry['method']),
                SORT_NATURAL | SORT_FLAG_CASE,
                $descending
            ),
            'masuk' => $entries->sortBy(
                fn (array $entry) => $entry['direction'] === 'masuk' ? $entry['amount'] : 0,
                SORT_NUMERIC,
                $descending
            ),
            'keluar' => $entries->sortBy(
                fn (array $entry) => $entry['direction'] === 'keluar' ? $entry['amount'] : 0,
                SORT_NUMERIC,
                $descending
            ),
            'saldo' => $entries->sortBy(
                fn (array $entry) => $entry['running_balance'],
                SORT_NUMERIC,
                $descending
            ),
            default => $entries->sortBy(
                fn (array $entry) => sprintf(
                    '%s-%d-%06d',
                    $entry['date']->toDateString(),
                    $entry['sort_group'],
                    $entry['sort_id'],
                ),
                SORT_STRING,
                $descending
            ),
        };

        return $entries->values();
    }

    /**
     * Export Laporan Penerimaan Kas ke CSV / Excel
     */
    public function exportCashInflowCsv(Request $request): StreamedResponse
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        // Ringkasan laporan selalu per satu bulan penuh dan otomatis berganti
        // saat tanggal 1 bulan berikutnya tiba.
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());
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
        $otherIncomes = $this->otherIncomesInRange($startDate, $endDate, $paymentMethod, $categoryId, $kelompok);
        $filename = "Laporan_Penerimaan_Kas_PTQ_Amsa_{$startDate}_sd_{$endDate}.csv";

        return response()->streamDownload(function () use ($payments, $otherIncomes, $startDate, $endDate) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM agar terbaca sempurna di Microsoft Excel
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['LAPORAN PENERIMAAN KAS - PESANTREN TADABBUR AL-QURAN AMSA001']);
            fputcsv($handle, ["Periode: {$startDate} s/d {$endDate}"]);
            fputcsv($handle, ['Tanggal Unduh: '.Carbon::now()->translatedFormat('d F Y')]);
            fputcsv($handle, []);

            // Header Tabel
            fputcsv($handle, [
                'No',
                'No Kuitansi',
                'Tanggal',
                'NIS',
                'Nama Santri',
                'Kelas',
                'Marhalah',
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
                    $pay->invoice?->receipt_title ?? '-',
                    strtoupper($pay->payment_method),
                    (float) $pay->amount_paid,
                    $pay->notes ?? '-',
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['', '', '', '', '', '', '', '', '', 'TOTAL PEMBAYARAN SANTRI:', $totalNominal]);

            $totalLainnya = 0;

            if ($otherIncomes->isNotEmpty()) {
                fputcsv($handle, []);
                fputcsv($handle, ['PEMASUKAN LAINNYA (DONASI, INFAQ, UNIT USAHA, DLL.)']);
                fputcsv($handle, ['No', 'Kode', 'Tanggal', 'Jenis', 'Uraian', 'Sumber / Pemberi', 'Metode', 'Nominal (Rp)', 'Catatan']);

                foreach ($otherIncomes as $index => $income) {
                    $totalLainnya += (float) $income->amount;
                    fputcsv($handle, [
                        $index + 1,
                        $income->income_code,
                        $income->income_date?->format('d/m/Y') ?? '-',
                        $income->category->label(),
                        $income->description,
                        $income->source ?: '-',
                        strtoupper($income->payment_method),
                        (float) $income->amount,
                        $income->notes ?? '-',
                    ]);
                }

                fputcsv($handle, ['', '', '', '', '', '', 'TOTAL PEMASUKAN LAINNYA:', $totalLainnya]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['', '', '', '', '', '', '', '', '', 'TOTAL SELURUH KAS MASUK:', $totalNominal + $totalLainnya]);

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
        // Ringkasan laporan selalu per satu bulan penuh dan otomatis berganti
        // saat tanggal 1 bulan berikutnya tiba.
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());
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

        $otherIncomes = $this->otherIncomesInRange($startDate, $endDate, $paymentMethod, $categoryId, $kelompok);
        $totalLainnya = (float) $otherIncomes->sum('amount');
        $totalKasMasuk = (float) $totalPenerimaan + $totalLainnya;
        $totalCash += (float) $otherIncomes->where('payment_method', 'cash')->sum('amount');
        $totalTransfer += (float) $otherIncomes->where('payment_method', 'transfer')->sum('amount');

        $pdf = Pdf::loadView('pdf.cash-inflow-report', compact(
            'payments',
            'otherIncomes',
            'startDate',
            'endDate',
            'kelompok',
            'totalPenerimaan',
            'totalLainnya',
            'totalKasMasuk',
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

        $allowedSorts = ['student_name', 'wali', 'detail', 'total_tunggakan'];
        $sortBy = (string) $request->input('sort_by', 'total_tunggakan');
        $sortOrder = strtolower((string) $request->input('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'total_tunggakan';
        }

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
                $q->whereLike('nama_lengkap', "%{$search}%")
                    ->orWhereLike('nis', "%{$search}%");
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
        });

        $sortOptions = in_array($sortBy, ['detail', 'total_tunggakan'], true)
            ? SORT_NUMERIC
            : SORT_NATURAL | SORT_FLAG_CASE;

        $studentArrears = $studentArrears
            ->sortBy(function (array $item) use ($sortBy) {
                return match ($sortBy) {
                    'student_name' => mb_strtolower((string) ($item['student']?->nama_lengkap ?? '')),
                    'wali' => mb_strtolower((string) ($item['student']?->nama_wali ?? '')),
                    'detail' => $item['count_invoices'],
                    default => $item['total_tunggakan'],
                };
            }, $sortOptions, $sortOrder === 'desc')
            ->values();

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
            'kelompok',
            'sortBy',
            'sortOrder'
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
            fputcsv($handle, ['Tanggal Unduh: '.Carbon::now()->translatedFormat('d F Y')]);
            fputcsv($handle, []);

            fputcsv($handle, [
                'No',
                'NIS',
                'Nama Lengkap Santri',
                'Kelas',
                'Marhalah',
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
