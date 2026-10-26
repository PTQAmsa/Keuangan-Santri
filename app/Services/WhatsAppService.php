<?php

namespace App\Services;

use App\Jobs\SendWhatsAppMessage;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use App\Models\WhatsAppMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

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
     * Seragamkan nomor Indonesia ke format 62xxxxxxxxxx.
     */
    public static function normalizePhone(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';

        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62'.$digits;
        }

        return $digits;
    }

    /**
     * Masukkan pesan ke outbox lalu kirim melalui queue worker.
     */
    public function queue(
        string $phone,
        string $message,
        string $template = 'custom',
        ?Student $student = null,
        ?int $userId = null,
    ): WhatsAppMessage {
        $outbox = WhatsAppMessage::create([
            'student_id' => $student?->id,
            'sent_by' => $userId,
            'phone' => self::normalizePhone($phone),
            'template' => $template,
            'message' => $message,
            'status' => 'queued',
        ]);

        SendWhatsAppMessage::dispatch($outbox->id);

        return $outbox->fresh() ?? $outbox;
    }

    /**
     * Antrekan kuitansi pembayaran ke wali santri.
     */
    public function queuePaymentReceipt(Payment $payment, ?int $userId = null): ?WhatsAppMessage
    {
        $payment->loadMissing(['student', 'invoice', 'receipt']);

        if (! $payment->student?->no_whatsapp_wali) {
            return null;
        }

        return $this->queue(
            phone: $payment->student->no_whatsapp_wali,
            message: $this->formatPaymentSuccessMessage($payment),
            template: 'payment_receipt',
            student: $payment->student,
            userId: $userId,
        );
    }

    /**
     * Antrekan pengingat tagihan ke wali santri.
     */
    public function queueInvoiceReminder(Invoice $invoice, ?int $userId = null): ?WhatsAppMessage
    {
        $invoice->loadMissing('student');

        if (! $invoice->student?->no_whatsapp_wali) {
            return null;
        }

        return $this->queue(
            phone: $invoice->student->no_whatsapp_wali,
            message: $this->formatInvoiceReminderMessage($invoice),
            template: 'invoice_reminder',
            student: $invoice->student,
            userId: $userId,
        );
    }

    /**
     * Kirim satu baris outbox ke gateway dan simpan hasilnya.
     *
     * Tanpa token, pesan ditandai "simulated" agar alur tetap bisa diuji lokal.
     */
    public function deliver(WhatsAppMessage $message): WhatsAppMessage
    {
        if (in_array($message->status, ['sent', 'simulated'], true)) {
            return $message;
        }

        $token = $this->token();

        if ($token === null) {
            $message->forceFill([
                'status' => 'simulated',
                'sent_at' => now(),
                'provider_response' => 'Token WhatsApp belum dikonfigurasi. Pesan hanya dicatat di outbox.',
                'error' => null,
            ])->save();

            return $message;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $token,
            ])->timeout(20)->acceptJson()->post($this->endpoint(), [
                'target' => $message->phone,
                'message' => $message->message,
            ]);

            $payload = $response->json();
            $payload = is_array($payload) ? $payload : [];
            $successful = $response->successful() && (($payload['status'] ?? true) !== false);

            $message->forceFill([
                'status' => $successful ? 'sent' : 'failed',
                'provider_response' => Str::limit(json_encode($payload, JSON_UNESCAPED_UNICODE) ?: $response->body(), 2000),
                'provider_message_id' => (string) ($payload['id'] ?? $payload['message_id'] ?? data_get($payload, 'data.id') ?? '') ?: null,
                'sent_at' => $successful ? now() : null,
                'error' => $successful ? null : Str::limit((string) ($payload['reason'] ?? $payload['message'] ?? 'Gateway menolak pesan (HTTP '.$response->status().').'), 1000),
            ])->save();
        } catch (Throwable $e) {
            Log::error('WhatsApp Gateway Error: '.$e->getMessage());

            $message->forceFill([
                'status' => 'failed',
                'error' => Str::limit($e->getMessage(), 1000),
            ])->save();
        }

        return $message;
    }

    /**
     * Kirim pesan langsung tanpa outbox (dipakai untuk pengujian/gateway manual).
     */
    public function sendMessage(string $targetPhone, string $message): array
    {
        $phone = self::normalizePhone($targetPhone);
        $token = $this->token();

        if ($token === null) {
            Log::info("WhatsApp Gateway [Simulation] to {$phone}:\n".$message);

            return [
                'status' => true,
                'simulated' => true,
                'message' => 'Pesan tercatat di antrean/log simulasi (Token belum dikonfigurasi).',
                'target' => $phone,
                'text' => $message,
            ];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $token,
            ])->timeout(20)->acceptJson()->post($this->endpoint(), [
                'target' => $phone,
                'message' => $message,
            ]);

            return [
                'http_status' => $response->status(),
                'successful' => $response->successful(),
                'response' => $response->json() ?? $response->body(),
            ];
        } catch (Throwable $e) {
            Log::error('WhatsApp Gateway Error: '.$e->getMessage());

            return [
                'status' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function endpoint(): string
    {
        return (string) config('services.whatsapp.endpoint', 'https://api.fonnte.com/send');
    }

    private function token(): ?string
    {
        $token = config('services.whatsapp.token');

        if (blank($token) || $token === 'YOUR_WHATSAPP_TOKEN') {
            return null;
        }

        return (string) $token;
    }
}
