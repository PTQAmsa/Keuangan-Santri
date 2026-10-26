<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catatan bisyarah kini dibuat per guru per bulan, bukan menempel pada
     * data induk guru. Data induk cukup diisi sekali, lalu setiap bulan
     * Bendahara memilih guru dan mengisi nominal serta metodenya.
     */
    public function up(): void
    {
        Schema::create('teacher_bisyarah_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');
            $table->decimal('amount', 12, 2);
            $table->enum('payment_method', ['transfer', 'cash']);
            $table->string('bank_name', 50)->nullable();
            $table->string('bank_account_number', 40)->nullable();
            $table->string('bank_account_name', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['teacher_id', 'period_year', 'period_month'],
                'teacher_bisyarah_unique_period'
            );
            $table->index(['period_year', 'period_month']);
        });

        if (! Schema::hasColumn('teachers', 'bisyarah_amount')) {
            return;
        }

        // Pindahkan nominal acuan yang sudah terlanjur diisi ke catatan bulan
        // berjalan supaya tidak hilang ketika kolom acuan dihapus.
        $now = now();

        DB::table('teachers')
            ->whereNotNull('bisyarah_amount')
            ->orderBy('id')
            ->get()
            ->each(function (object $teacher) use ($now): void {
                DB::table('teacher_bisyarah_payments')->insert([
                    'teacher_id' => $teacher->id,
                    'period_year' => $now->year,
                    'period_month' => $now->month,
                    'amount' => $teacher->bisyarah_amount,
                    'payment_method' => $teacher->payment_method ?? 'transfer',
                    'bank_name' => $teacher->bank_name ?? null,
                    'bank_account_number' => $teacher->bank_account_number ?? null,
                    'bank_account_name' => $teacher->bank_account_name ?? null,
                    'notes' => 'Dipindahkan dari acuan bisyarah saat pembaruan sistem.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });

        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn([
                'bank_name',
                'bank_account_number',
                'bank_account_name',
                'bisyarah_amount',
                'payment_method',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->string('bank_name', 50)->nullable();
            $table->string('bank_account_number', 40)->nullable();
            $table->string('bank_account_name', 100)->nullable();
            $table->decimal('bisyarah_amount', 12, 2)->nullable();
            $table->enum('payment_method', ['transfer', 'cash'])->default('transfer');
        });

        Schema::dropIfExists('teacher_bisyarah_payments');
    }
};
