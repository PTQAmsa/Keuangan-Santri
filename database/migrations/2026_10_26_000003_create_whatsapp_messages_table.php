<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Outbox WhatsApp: satu baris untuk setiap pesan yang akan/dikirim ke wali,
     * termasuk respons gateway agar pengiriman bisa diaudit dan dicoba ulang.
     */
    public function up(): void
    {
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('phone', 25);
            $table->string('template', 50)->default('custom');
            $table->text('message');
            $table->string('status', 20)->default('queued'); // queued, sent, failed, simulated
            $table->string('provider_message_id', 100)->nullable();
            $table->text('provider_response')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
