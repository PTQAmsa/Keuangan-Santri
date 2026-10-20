<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
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
    public function build(Student $student): array
    {
        $invoices = $student->invoices()
            ->with('category')
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

        return [
            'student' => $student,
            'invoices' => $invoices,
            'payments' => $payments,
            'activePayments' => $activePayments,
            'totalTagihan' => $totalTagihan,
            'totalDibayar' => $totalDibayar,
            'totalTunggakan' => $totalTunggakan,
            'saldoAkhir' => $totalTunggakan,
            'jumlahKuitansi' => $activePayments->count(),
            'ledger' => $this->buildLedger($invoices, $payments),
        ];
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
                'description' => 'Pembayaran '.($payment->invoice->title ?? '-'),
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
