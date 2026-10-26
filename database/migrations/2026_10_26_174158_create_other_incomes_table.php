<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pemasukan pesantren di luar pembayaran santri: donasi, infaq, hibah,
     * hasil unit usaha, sewa, dan lain-lain. Ikut dihitung di arus kas,
     * dashboard, dan tutup buku bersama pembayaran santri.
     */
    public function up(): void
    {
        Schema::create('other_incomes', function (Blueprint $table) {
            $table->id();
            $table->string('income_code', 30)->unique();
            $table->string('category', 30);
            $table->string('description', 255);
            $table->decimal('amount', 15, 2);
            $table->date('income_date');
            $table->enum('payment_method', ['cash', 'transfer'])->default('cash');
            $table->string('source', 150)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('void_reason')->nullable();
            $table->timestamps();

            $table->index(['income_date', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('other_incomes');
    }
};
