<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class StudentFinancialCardService
{
    /**
     * Susun data kartu keuangan satu santri: ringkasan, buku kas, tagihan,
     * dan seluruh pembayaran/kuitansi (termasuk yang sudah dibatalkan).
     *
     * @return array<string, mixed>
     */
    public function build(Student $student, ?Carbon $asOf = null): array
    {
        $asOf = ($asOf ?? now())->copy();

        $invoices = $student->invoices()
            ->with(['category', 'payments.receipt'])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $payments = $student->payments()
            ->with(['receipt', 'invoice', 'recordedBy'])
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get();

        $activeInvoices = $invoices->where('status', '!=', 'cancelled');
        $activePayments = $payments->whereNull('voided_at');

        $totalTagihan = (float) $activeInvoices->sum(fn ($invoice) => (float) $invoice->amount);
        $totalDibayar = (float) $activePayments->sum(fn ($payment) => (float) $payment->amount_paid);
        $totalTunggakan = (float) $activeInvoices
            ->whereIn('status', ['unpaid', 'partial'])
            ->sum(fn ($invoice) => $invoice->remaining_amount);

        // Angka yang dipakai di kartu/panel wali dibatasi sampai bulan berjalan
        // agar tagihan bulan-bulan mendatang tidak terasa membebani.
        $tunggakanBulanBerjalan = $this->limitArrearsToCurrentMonth(
            $activeInvoices->whereIn('status', ['unpaid', 'partial']),
            $asOf
        );

        // Buku kas juga hanya memuat transaksi s.d. bulan berjalan, sehingga
        // saldo akhirnya konsisten dengan angka sisa tunggakan di ringkasan.
        $invoicesBukuKas = $invoices
            ->reject(fn (Invoice $invoice) => $this->isScheduledAfterCurrentMonth($invoice, $asOf))
            ->values();

        return [
            'student' => $student,
            'invoices' => $invoices,
            'payments' => $payments,
            'activePayments' => $activePayments,
            'totalTagihan' => $totalTagihan,
            'totalDibayar' => $totalDibayar,
            'totalTunggakan' => $totalTunggakan,
            'totalTunggakanBulanBerjalan' => (float) $tunggakanBulanBerjalan
                ->sum(fn ($invoice) => $invoice->remaining_amount),
            'jumlahKuitansi' => $activePayments->count(),
            'batasTunggakanLabel' => $this->monthLabel($asOf),
            'batasTunggakanCatatan' => $this->arrearsNote($asOf),
            'semester' => $this->semesterWindow($asOf),
            'ledger' => $this->buildLedger($invoicesBukuKas, $payments),
        ];
    }

    /**
     * Data PDF "Daftar Tagihan & Sisa Kewajiban": hanya tagihan dalam
     * semester berjalan dan tidak melewati bulan saat dokumen diunduh.
     *
     * @return array<string, mixed>
     */
    public function buildInvoicesDocument(Student $student, ?Carbon $asOf = null): array
    {
        $asOf = ($asOf ?? now())->copy();
        $semester = $this->semesterWindow($asOf);
        $batasAtas = $asOf->copy()->endOfMonth();

        $invoices = $student->invoices()
            ->with(['category', 'payments.receipt'])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->filter(function (Invoice $invoice) use ($semester, $batasAtas) {
                $referensi = $this->invoiceReferenceDate($invoice);

                if ($referensi !== null
                    && ! $referensi->lt($semester['start'])
                    && ! $referensi->gt($batasAtas)) {
                    return true;
                }

                // Wakaf adalah komitmen yang tidak terikat bulan tagihan, jadi
                // sisa cicilannya tetap tampil walau dibuat pada semester lalu.
                return $this->isWakaf($invoice) && $invoice->remaining_amount > 0;
            })
            ->values();

        $activeInvoices = $invoices->where('status', '!=', 'cancelled');

        return [
            'mode' => 'invoices',
            'student' => $student,
            'documentTitle' => 'Daftar Tagihan & Sisa Kewajiban',
            'documentCode' => 'KARTU-TAGIHAN',
            'semester' => $semester,
            'periodEnd' => $batasAtas->copy(),
            'invoices' => $invoices,
            'totalTagihan' => (float) $activeInvoices->sum(fn ($invoice) => (float) $invoice->amount),
            'totalDibayar' => (float) $activeInvoices->sum(fn ($invoice) => (float) $invoice->paid_amount),
            'totalTunggakan' => (float) $activeInvoices
                ->whereIn('status', ['unpaid', 'partial'])
                ->sum(fn ($invoice) => $invoice->remaining_amount),
            'batasTunggakanLabel' => $this->monthLabel($asOf),
            'batasTunggakanCatatan' => $this->arrearsNote($asOf),
        ];
    }

    /**
     * Data PDF "Riwayat Pembayaran & Kuitansi": maksimal satu semester
     * terakhir sampai tanggal dokumen diunduh.
     *
     * @return array<string, mixed>
     */
    public function buildPaymentsDocument(Student $student, ?Carbon $asOf = null): array
    {
        $asOf = ($asOf ?? now())->copy();
        $semester = $this->semesterWindow($asOf);
        $batasAtas = $asOf->copy()->endOfMonth();

        $payments = $student->payments()
            ->with(['receipt', 'invoice', 'recordedBy'])
            ->whereDate('payment_date', '>=', $semester['start']->toDateString())
            ->whereDate('payment_date', '<=', $batasAtas->toDateString())
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get();

        $activePayments = $payments->whereNull('voided_at');

        return [
            'mode' => 'payments',
            'student' => $student,
            'documentTitle' => 'Riwayat Pembayaran & Kuitansi',
            'documentCode' => 'KARTU-PEMBAYARAN',
            'semester' => $semester,
            'periodEnd' => $batasAtas->copy(),
            'payments' => $payments,
            'jumlahKuitansi' => $activePayments->count(),
            'totalDibayar' => (float) $activePayments->sum(fn ($payment) => (float) $payment->amount_paid),
            'batasTunggakanLabel' => $this->monthLabel($asOf),
            'batasTunggakanCatatan' => $this->arrearsNote($asOf),
        ];
    }

    /**
     * Unduh PDF daftar tagihan (maksimal satu semester berjalan).
     */
    public function invoicesPdf(Student $student)
    {
        return Pdf::loadView('pdf.student-document', $this->buildInvoicesDocument($student))
            ->setPaper('a4', 'portrait')
            ->stream('Daftar-Tagihan-'.$student->nis.'.pdf');
    }

    /**
     * Unduh PDF riwayat pembayaran & kuitansi (maksimal satu semester berjalan).
     */
    public function paymentsPdf(Student $student)
    {
        return Pdf::loadView('pdf.student-document', $this->buildPaymentsDocument($student))
            ->setPaper('a4', 'portrait')
            ->stream('Riwayat-Pembayaran-'.$student->nis.'.pdf');
    }

    /**
     * Saring tagihan agar hanya sampai bulan berjalan. Tagihan wakaf selalu
     * disertakan karena bukan kewajiban bulanan.
     *
     * @param  Collection<int, Invoice>  $invoices
     * @return Collection<int, Invoice>
     */
    public function limitArrearsToCurrentMonth(Collection $invoices, ?Carbon $asOf = null): Collection
    {
        $asOf = ($asOf ?? now())->copy();

        return $invoices
            ->filter(fn (Invoice $invoice) => ! $this->isScheduledAfterCurrentMonth($invoice, $asOf))
            ->values();
    }

    /**
     * Rentang semester akademik berjalan (Ganjil: Jul–Des, Genap: Jan–Jun).
     *
     * @return array{start: Carbon, end: Carbon, label: string}
     */
    public function semesterWindow(?Carbon $asOf = null): array
    {
        $asOf = ($asOf ?? now())->copy();
        $tahun = (int) $asOf->year;

        if ((int) $asOf->month >= 7) {
            return [
                'start' => Carbon::create($tahun, 7, 1)->startOfDay(),
                'end' => Carbon::create($tahun, 12, 31)->endOfDay(),
                'label' => 'Semester Ganjil '.$tahun.'/'.($tahun + 1),
            ];
        }

        return [
            'start' => Carbon::create($tahun, 1, 1)->startOfDay(),
            'end' => Carbon::create($tahun, 6, 30)->endOfDay(),
            'label' => 'Semester Genap '.($tahun - 1).'/'.$tahun,
        ];
    }

    /**
     * Apakah tagihan ini berasal dari pos wakaf.
     */
    public function isWakaf(Invoice $invoice): bool
    {
        return $invoice->isWakaf();
    }

    /**
     * Apakah tagihan dijadwalkan setelah bulan berjalan.
     */
    private function isScheduledAfterCurrentMonth(Invoice $invoice, Carbon $asOf): bool
    {
        if ($this->isWakaf($invoice)) {
            return false;
        }

        if ($invoice->period_year && $invoice->period_month) {
            $periode = ((int) $invoice->period_year * 100) + (int) $invoice->period_month;
            $bulanIni = ((int) $asOf->year * 100) + (int) $asOf->month;

            return $periode > $bulanIni;
        }

        $referensi = $invoice->due_date ?? $invoice->created_at;

        return $referensi !== null && $referensi->copy()->startOfDay()->gt($asOf->copy()->endOfMonth());
    }

    private function invoiceReferenceDate(Invoice $invoice): ?Carbon
    {
        if ($invoice->period_year && $invoice->period_month) {
            return Carbon::create((int) $invoice->period_year, (int) $invoice->period_month, 1)->startOfDay();
        }

        return $invoice->due_date ?? $invoice->created_at;
    }

    private function monthLabel(Carbon $asOf): string
    {
        return $asOf->copy()->translatedFormat('F Y');
    }

    private function arrearsNote(Carbon $asOf): string
    {
        return 'Sisa tunggakan dihitung sampai '.$this->monthLabel($asOf).'.';
    }

    /**
     * Gabungkan tagihan (debit) dan pembayaran aktif (kredit) menjadi buku kas
     * berurutan tanggal dengan saldo berjalan.
     *
     * @param  Collection<int, Invoice>  $invoices
     * @param  Collection<int, Payment>  $payments
     * @return Collection<int, array<string, mixed>>
     */
    private function buildLedger(Collection $invoices, Collection $payments): Collection
    {
        $entries = collect();

        foreach ($invoices as $invoice) {
            $cancelled = $invoice->status === 'cancelled';

            $entries->push([
                'date' => $this->invoiceEntryDate($invoice, $payments),
                'sort' => 0,
                'type' => 'tagihan',
                'document' => $invoice->invoice_number,
                'description' => $invoice->title,
                'debit' => $cancelled ? 0.0 : (float) $invoice->amount,
                'credit' => 0.0,
                'status' => $invoice->status_label,
                'note' => $cancelled ? 'Tagihan dibatalkan, tidak menambah saldo.' : null,
                'invoice' => $invoice,
                'payment' => null,
            ]);
        }

        foreach ($payments as $payment) {
            $voided = $payment->isVoided();

            $entries->push([
                'date' => $payment->payment_date ?? $payment->created_at ?? now(),
                'sort' => 1,
                'type' => 'pembayaran',
                'document' => $payment->receipt->receipt_number ?? $payment->payment_code,
                'description' => 'Pembayaran '.($payment->invoice?->receipt_title ?? '-'),
                'debit' => 0.0,
                'credit' => $voided ? 0.0 : (float) $payment->amount_paid,
                'status' => $voided ? 'Dibatalkan' : 'Sah',
                'note' => $voided ? 'Kuitansi dibatalkan: '.($payment->void_reason ?: '-') : null,
                'invoice' => $payment->invoice,
                'payment' => $payment,
            ]);
        }

        $balance = 0.0;

        return $entries
            ->sortBy([
                ['date', 'asc'],
                ['sort', 'asc'],
            ])
            ->values()
            ->map(function (array $entry) use (&$balance) {
                $balance += $entry['debit'] - $entry['credit'];
                $entry['balance'] = $balance;

                return $entry;
            });
    }

    /**
     * Tanggal efektif tagihan di buku kas: tanggal pembayaran pertama (agar
     * tagihan selalu muncul sebelum pembayarannya), atau tanggal tagihan dibuat.
     */
    private function invoiceEntryDate(Invoice $invoice, Collection $payments): Carbon
    {
        $firstPayment = $payments
            ->where('invoice_id', $invoice->id)
            ->whereNull('voided_at')
            ->sortBy('payment_date')
            ->first();

        return $firstPayment?->payment_date
            ?? $invoice->created_at
            ?? $invoice->due_date
            ?? now();
    }
}
