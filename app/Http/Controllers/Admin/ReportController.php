<?php

namespace App\Http\Controllers\Admin;

use App\Models\Invoice;
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
