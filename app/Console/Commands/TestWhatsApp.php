<?php

namespace App\Console\Commands;

use App\Services\WhatsAppService;
use Illuminate\Console\Command;

class TestWhatsApp extends Command
{
    protected $signature = 'whatsapp:test
                            {phone : Nomor tujuan, contoh 081234567890}
                            {--message= : Isi pesan uji}';

    protected $description = 'Uji pengiriman WhatsApp dan catat hasilnya di outbox';

    public function handle(WhatsAppService $service): int
    {
        $phone = WhatsAppService::normalizePhone((string) $this->argument('phone'));

        if ($phone === '') {
            $this->error('Nomor tujuan tidak valid.');

            return self::FAILURE;
        }

        $outbox = $service->queue(
            phone: $phone,
            message: (string) ($this->option('message') ?: 'Uji koneksi WhatsApp dari Sistem Keuangan PTQ Amsa001.'),
            template: 'test',
        );

        $outbox->refresh();

        $this->info('Pesan outbox #'.$outbox->id.' untuk +'.$phone);
        $this->line('Status: '.$outbox->status);

        if ($outbox->status === 'simulated') {
            $this->warn('WHATSAPP_TOKEN belum diisi, jadi pesan hanya disimulasikan. Isi WHATSAPP_ENDPOINT dan WHATSAPP_TOKEN di .env untuk pengiriman nyata.');
        } elseif ($outbox->status === 'queued') {
            $this->line('Pesan masuk antrean. Jalankan `php artisan queue:work` agar terkirim.');
        } elseif ($outbox->status === 'failed') {
            $this->error('Gagal: '.$outbox->error);

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
