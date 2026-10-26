<?php

namespace App\Jobs;

use App\Models\WhatsAppMessage;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWhatsAppMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300];

    public function __construct(public int $messageId) {}

    public function handle(WhatsAppService $service): void
    {
        $message = WhatsAppMessage::find($this->messageId);

        if (! $message) {
            return;
        }

        // Kegagalan gateway disimpan pada baris outbox (status failed + error),
        // bukan dilempar, agar pencatatan pembayaran tidak ikut gagal.
        $service->deliver($message);
    }
}
