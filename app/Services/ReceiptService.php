<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Receipt;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ReceiptService
{
    /**
     * Konversi angka nominal ke kalimat terbilang Rupiah
     */
    public static function terbilang(float|int $angka): string
    {
        $angka = abs((int) $angka);
        $baca = [
            '', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas',
        ];
        $terbilang = '';

        if ($angka < 12) {
            $terbilang = ' '.$baca[$angka];
        } elseif ($angka < 20) {
            $terbilang = self::terbilang($angka - 10).' Belas';
        } elseif ($angka < 100) {
            $terbilang = self::terbilang(intdiv($angka, 10)).' Puluh'.self::terbilang($angka % 10);
        } elseif ($angka < 200) {
            $terbilang = ' Seratus'.self::terbilang($angka - 100);
        } elseif ($angka < 1000) {
            $terbilang = self::terbilang(intdiv($angka, 100)).' Ratus'.self::terbilang($angka % 100);
        } elseif ($angka < 2000) {
            $terbilang = ' Seribu'.self::terbilang($angka - 1000);
        } elseif ($angka < 1000000) {
            $terbilang = self::terbilang(intdiv($angka, 1000)).' Ribu'.self::terbilang($angka % 1000);
        } elseif ($angka < 1000000000) {
            $terbilang = self::terbilang(intdiv($angka, 1000000)).' Juta'.self::terbilang($angka % 1000000);
        } elseif ($angka < 1000000000000) {
            $terbilang = self::terbilang(intdiv($angka, 1000000000)).' Milyar'.self::terbilang($angka % 1000000000);
        }

        return trim($terbilang);
    }

    /**
     * Catat pembayaran dan terbitkan kuitansi digital
     */
    public function recordPayment(Invoice $invoice, float $amountPaid, string $method, ?string $notes = null, ?int $adminId = null, ?string $receivedFrom = null): Payment
    {
        // 1. Generate payment code & receipt number
        $monthYear = now()->format('Ym');
        $count = Payment::whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->count() + 1;
        $paymentCode = 'PAY-'.$monthYear.'-'.str_pad($count, 4, '0', STR_PAD_LEFT);
        $receiptNumber = 'KWT-'.$monthYear.'-'.str_pad($count, 4, '0', STR_PAD_LEFT);

        // 2. Buat Payment
        $payment = Payment::create([
            'payment_code' => $paymentCode,
            'invoice_id' => $invoice->id,
            'student_id' => $invoice->student_id,
            'amount_paid' => $amountPaid,
            'payment_date' => now()->toDateString(),
            'payment_method' => $method,
            'received_from' => $receivedFrom,
            'notes' => $notes,
            'recorded_by' => $adminId,
        ]);

        // 3. Buat Receipt dengan Token Verifikasi Unik
        Receipt::create([
            'receipt_number' => $receiptNumber,
            'payment_id' => $payment->id,
            'qr_verification_token' => Str::random(32),
            'issued_at' => now(),
        ]);

        // 4. Update status tagihan (Invoice)
        $previousStatus = $invoice->status;
        $previousPaidAmount = (float) $invoice->paid_amount;

        $newPaidAmount = $invoice->paid_amount + $amountPaid;
        $invoice->paid_amount = $newPaidAmount;
        if ($newPaidAmount >= $invoice->amount) {
            $invoice->status = 'paid';
        } else {
            $invoice->status = 'partial';
        }
        $invoice->save();

        // 5. Catat perubahan keuangan ke log aktivitas
        ActivityLogService::paymentRecorded($payment, $previousStatus, $previousPaidAmount, $adminId);

        return $payment;
    }

    /**
     * Batalkan kuitansi/pembayaran. Data tidak dihapus agar jejak audit tetap utuh,
     * dan status tagihan dihitung ulang dari pembayaran yang masih aktif.
     */
    public function voidPayment(Payment $payment, string $reason, ?int $adminId = null): Payment
    {
        $invoice = $payment->invoice;

        $previousStatus = (string) $invoice->status;
        $previousPaidAmount = (float) $invoice->paid_amount;

        $payment->voided_at = now();
        $payment->voided_by = $adminId;
        $payment->void_reason = $reason;
        $payment->save();

        $invoice->recalculateStatus();

        ActivityLogService::paymentVoided(
            payment: $payment,
            reason: $reason,
            previousStatus: $previousStatus,
            previousPaidAmount: $previousPaidAmount,
            userId: $adminId,
        );

        return $payment;
    }

    /**
     * Perbaiki data pembayaran yang salah input (nominal, metode, tanggal, dsb).
     *
     * @param  array<string, mixed>  $data
     */
    public function updatePayment(Payment $payment, array $data, ?int $adminId = null): Payment
    {
        $invoice = $payment->invoice;

        $labels = [
            'amount_paid' => 'Nominal Bayar',
            'payment_method' => 'Metode Bayar',
            'payment_date' => 'Tanggal Bayar',
            'received_from' => 'Diterima Dari',
            'notes' => 'Catatan',
        ];

        $changes = [];

        foreach ($labels as $field => $label) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            $format = function (string $field, mixed $value) {
                return match ($field) {
                    'payment_date' => $value ? Carbon::parse($value)->format('d/m/Y') : '-',
                    'amount_paid' => self::rupiah((float) $value),
                    'payment_method' => $value === 'cash' ? 'Tunai' : 'Transfer',
                    default => (string) ($value ?? ''),
                };
            };

            $old = $format($field, $payment->{$field});
            $new = $format($field, $data[$field]);

            if ($old !== $new) {
                $changes[$label] = [$old === '' ? '-' : $old, $new === '' ? '-' : $new];
            }
        }

        if ($changes === []) {
            return $payment;
        }

        $payment->fill($data)->save();

        $invoice->recalculateStatus();

        ActivityLogService::paymentUpdated($payment, $changes, $adminId);

        return $payment;
    }

    /**
     * Format nominal menjadi teks Rupiah (dipakai untuk log perubahan).
     */
    private static function rupiah(float|int|string|null $amount): string
    {
        return 'Rp '.number_format((float) $amount, 0, ',', '.');
    }

    /**
     * Generate file PDF Kuitansi Resmi
     */
    public function generateReceiptPdf(Receipt $receipt)
    {
        $payment = $receipt->payment;
        $invoice = $payment->invoice;
        $student = $payment->student;

        $terbilang = self::terbilang((int) $payment->amount_paid).' Rupiah';

        $data = [
            'receipt' => $receipt,
            'payment' => $payment,
            'invoice' => $invoice,
            'student' => $student,
            'terbilang' => $terbilang,
            'verification_url' => $receipt->verification_url,
        ];

        $pdf = Pdf::loadView('pdf.receipt', $data)
            ->setPaper('a5', 'landscape');

        return $pdf;
    }
}
