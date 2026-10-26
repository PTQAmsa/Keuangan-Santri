<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Data guru/pengajar beserta acuan bisyarah bulanan.
     *
     * Nominal di sini adalah acuan pembayaran, bukan transaksi kas. Pembayaran
     * tetap dicatat pada tabel expenses kategori Bisyarah.
     */
    public function up(): void
    {
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->string('nama_lengkap', 100);
            $table->string('kota_asal', 50)->nullable();
            $table->string('no_whatsapp', 25)->nullable();
            $table->string('status', 30)->default('guru_tetap');
            $table->string('bank_name', 50)->nullable();
            $table->string('bank_account_number', 40)->nullable();
            $table->string('bank_account_name', 100)->nullable();
            $table->decimal('bisyarah_amount', 12, 2)->nullable();
            $table->enum('payment_method', ['transfer', 'cash'])->default('transfer');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'nama_lengkap']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
