<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pengeluaran operasional pesantren (kas keluar).
     */
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_code', 30)->unique();
            $table->string('category', 30);
            $table->string('description', 255);
            $table->decimal('amount', 15, 2);
            $table->date('expense_date');
            $table->enum('payment_method', ['cash', 'transfer'])->default('cash');
            $table->string('recipient', 150)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('void_reason')->nullable();
            $table->timestamps();

            $table->index(['expense_date', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
