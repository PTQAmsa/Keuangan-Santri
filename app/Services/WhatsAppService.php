<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Format teks notifikasi kuitansi pembayaran berhasil
     */
    public function formatPaymentSuccessMessage(Payment $payment): string
    {
        $student = $payment->student;
        $invoice = $payment->invoice;
        $receipt = $payment->receipt;

        $nominalFormatted = 'Rp '.number_format($payment->amount_paid, 0, ',', '.');
        $sisaFormatted = 'Rp '.number_format($invoice->remaining_amount, 0, ',', '.');
        $statusKet = $invoice->status === 'paid' ? 'LUNAS' : "DICICIL (Sisa: {$sisaFormatted})";

        $receiptUrl = $receipt ? url("/kuitansi/{$receipt->id}/download") : url('/portal');

        return <<<TEXT
*BUKTI PEMBAYARAN PTQ AMSA001 GADOG*
_Pesantren Tadabbur Al-Qur'an Amsa001_
-----------------------------------------
Assalamu'alaikum Wr. Wb.
Yth. Bpk/Ibu *{$student->nama_wali}*
(Wali dari ananda *{$student->nama_lengkap}* - Kelas {$student->kelas})

Alhamdulillah, pembayaran telah kami terima dengan rincian sbb:

• *No. Kuitansi:* {$receipt->receipt_number}
• *Pos Tagihan:* {$invoice->title}
• *Nominal Dibayar:* *{$nominalFormatted}*
• *Tanggal:* {$payment->payment_date->format('d/m/Y')}
• *Metode:* {$payment->payment_method}
• *Status Tagihan:* *{$statusKet}*

📄 *Unduh Kuitansi PDF Resmi:*
{$receiptUrl}

Jazakumullahu khairan katsiran atas amanah dan kerjasamanya. Semoga berkah bagi ananda dan keluarga.

_Wassalamu'alaikum Wr. Wb._
*Layanan Keuangan PTQ Amsa001*
WA: +62 856-9225-3516 | Web: www.ptqamsa.id
TEXT;
    }

    /**
     * Format teks notifikasi tagihan baru / pengingat jatuh tempo
     */
    public function formatInvoiceReminderMessage(Invoice $invoice): string
    {
        $student = $invoice->student;
        $nominalFormatted = 'Rp '.number_format($invoice->remaining_amount, 0, ',', '.');
        $dueDateFormatted = $invoice->due_date ? $invoice->due_date->format('d/m/Y') : '-';
        $portalUrl = url('/portal');

        return <<<TEXT
*PEMBERITAHUAN TAGIHAN PTQ AMSA001*
_Pesantren Tadabbur Al-Qur'an Amsa001_
-----------------------------------------
Assalamu'alaikum Wr. Wb.
Yth. Bpk/Ibu *{$student->nama_wali}*
(Wali dari ananda *{$student->nama_lengkap}* - Kelas {$student->kelas})

Kami informasikan tagihan untuk ananda sbb:

• *Uraian:* {$invoice->title}
• *Sisa Tagihan:* *{$nominalFormatted}*
• *Batas Jatuh Tempo:* {$dueDateFormatted}

Rincian pembayaran & kuitansi dapat dicek melalui Portal Wali Santri:
🔗 {$portalUrl}
(Login dengan NIS: *{$student->nis}*)

*Rekening Resmi Pesantren:*
Bank Syariah Indonesia (BSI)
No. Rekening: *1202020212*
A.n: *Pesantren Tadabbur Al-Qur'an*

Setelah melakukan transfer, bukti transfer dapat dikonfirmasikan ke nomor ini.
_Jazakumullahu khairan._

*Layanan Keuangan PTQ Amsa001*
TEXT;
    }

    /**
     * Mengirim pesan WA via REST API Gateway (Fonnte / Starsender / Wablas)
     */
    public function sendMessage(string $targetPhone, string $message): array
    {
        $token = config('services.whatsapp.token');
        $endpoint = config('services.whatsapp.endpoint', 'https://api.fonnte.com/send');

        // Log pesan di lingkungan lokal/dev jika belum ada token aktif
        if (empty($token) || $token === 'YOUR_WHATSAPP_TOKEN') {
            Log::info("WhatsApp Gateway [Simulation] to {$targetPhone}:\n".$message);

            return [
                'status' => true,
                'simulated' => true,
                'message' => 'Pesan tercatat di antrean/log simulasi (Token belum dikonfigurasi).',
                'target' => $targetPhone,
                'text' => $message,
            ];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $token,
            ])->post($endpoint, [
                'target' => $targetPhone,
                'message' => $message,
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('WhatsApp Gateway Error: '.$e->getMessage());

            return [
                'status' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
