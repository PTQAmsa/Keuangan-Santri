<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Saldo kas awal saat sistem mulai dipakai (hasil migrasi dari pembukuan
     * lama). Satu baris per tempat uang: kas tunai, atau satu rekening bank.
     * Dihitung sebagai saldo awal di arus kas dan tutup buku, bukan kas masuk.
     */
    public function up(): void
    {
        Schema::create('opening_balances', function (Blueprint $table) {
            $table->id();
            $table->date('effective_date');
            $table->enum('payment_method', ['cash', 'transfer'])->default('cash');
            $table->decimal('amount', 15, 2);
            $table->string('bank_name', 50)->nullable();
            $table->string('bank_account_number', 50)->nullable();
            $table->string('bank_account_name', 100)->nullable();
            $table->string('notes', 255)->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('effective_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opening_balances');
    }
};
