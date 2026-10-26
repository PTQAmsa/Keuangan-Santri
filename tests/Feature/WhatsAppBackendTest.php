<?php

namespace Tests\Feature;

use App\Jobs\SendWhatsAppMessage;
use App\Models\Payment;
use App\Models\WhatsAppMessage;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WhatsAppBackendTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        config()->set('services.whatsapp.token', null);
        config()->set('services.whatsapp.endpoint', 'https://api.fonnte.com/send');
    }

    public function test_phone_numbers_are_normalized_to_international_format(): void
    {
        $this->assertSame('628123456789', WhatsAppService::normalizePhone('0812-3456-789'));
        $this->assertSame('628123456789', WhatsAppService::normalizePhone('+62 812 3456 789'));
        $this->assertSame('628123456789', WhatsAppService::normalizePhone('8123456789'));
        $this->assertSame('', WhatsAppService::normalizePhone('  '));
    }

    public function test_queue_creates_outbox_and_dispatches_delivery_job(): void
    {
        Queue::fake();

        $outbox = app(WhatsAppService::class)->queue('0812-3456-789', 'Halo wali santri', 'test');

        $this->assertSame('628123456789', $outbox->phone);
        $this->assertSame('queued', $outbox->status);
        $this->assertSame('test', $outbox->template);
        $this->assertDatabaseHas('whatsapp_messages', ['id' => $outbox->id, 'status' => 'queued']);

        Queue::assertPushed(SendWhatsAppMessage::class, fn (SendWhatsAppMessage $job) => $job->messageId === $outbox->id);
    }

    public function test_delivery_is_simulated_without_token(): void
    {
        $outbox = WhatsAppMessage::create([
            'phone' => '628111222333',
            'template' => 'test',
            'message' => 'Pesan uji',
            'status' => 'queued',
        ]);

        app(WhatsAppService::class)->deliver($outbox);

        $outbox->refresh();
        $this->assertSame('simulated', $outbox->status);
        $this->assertNotNull($outbox->sent_at);
        $this->assertNull($outbox->error);
        $this->assertStringContainsString('Token', (string) $outbox->provider_response);
    }

    public function test_delivery_sends_to_gateway_and_records_provider_id(): void
    {
        config()->set('services.whatsapp.token', 'rahasia-token');

        Http::fake([
            'api.fonnte.com/*' => Http::response(['status' => true, 'id' => 'wa-12345'], 200),
        ]);

        $outbox = WhatsAppMessage::create([
            'phone' => '628111222333',
            'template' => 'test',
            'message' => 'Pesan uji gateway',
            'status' => 'queued',
        ]);

        app(WhatsAppService::class)->deliver($outbox);

        $outbox->refresh();
        $this->assertSame('sent', $outbox->status);
        $this->assertSame('wa-12345', $outbox->provider_message_id);
        $this->assertNotNull($outbox->sent_at);
        $this->assertNull($outbox->error);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.fonnte.com/send'
                && $request->hasHeader('Authorization', 'rahasia-token')
                && $request['target'] === '628111222333'
                && $request['message'] === 'Pesan uji gateway';
        });
    }

    public function test_delivery_records_gateway_failure(): void
    {
        config()->set('services.whatsapp.token', 'token-salah');

        Http::fake([
            'api.fonnte.com/*' => Http::response(['status' => false, 'reason' => 'Token tidak valid'], 200),
        ]);

        $outbox = WhatsAppMessage::create([
            'phone' => '628111222333',
            'template' => 'test',
            'message' => 'Pesan uji gagal',
            'status' => 'queued',
        ]);

        app(WhatsAppService::class)->deliver($outbox);

        $outbox->refresh();
        $this->assertSame('failed', $outbox->status);
        $this->assertNotNull($outbox->error);
        $this->assertStringContainsString('Token tidak valid', $outbox->error);
        $this->assertNull($outbox->sent_at);
    }

    public function test_payment_receipt_can_be_queued_for_student_guardian(): void
    {
        Queue::fake();

        $payment = Payment::whereHas('receipt')->with('student')->firstOrFail();

        $outbox = app(WhatsAppService::class)->queuePaymentReceipt($payment);

        $this->assertNotNull($outbox);
        $this->assertSame($payment->student_id, $outbox->student_id);
        $this->assertSame('payment_receipt', $outbox->template);
        $this->assertStringContainsString($payment->receipt->receipt_number, $outbox->message);
        $this->assertSame(
            WhatsAppService::normalizePhone($payment->student->no_whatsapp_wali),
            $outbox->phone
        );

        Queue::assertPushed(SendWhatsAppMessage::class);
    }

    public function test_whatsapp_test_command_reports_simulation(): void
    {
        $this->artisan('whatsapp:test', ['phone' => '0812-3456-789'])
            ->expectsOutputToContain('Pesan outbox')
            ->expectsOutputToContain('simulated')
            ->assertSuccessful();

        $this->assertDatabaseHas('whatsapp_messages', [
            'phone' => '628123456789',
            'template' => 'test',
            'status' => 'simulated',
        ]);
    }
}
