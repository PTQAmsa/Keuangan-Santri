<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Students Table
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('nis', 20)->unique();
            $table->string('nisn', 20)->nullable();
            $table->string('nama_lengkap', 100);
            $table->enum('jenis_kelamin', ['L', 'P'])->default('L');
            // Pesantren melayani santri putra & putri dalam satu pembukuan kas.
            $table->enum('santri_group', ['putra', 'putri'])->default('putra');
            $table->string('kelas', 20); // misal 7A, 7 Putri, 8B
            $table->string('kamar_asrama', 50)->nullable(); // misal Kamar 01, Asrama Banat
            $table->year('tahun_masuk')->default(2026);
            $table->enum('status', ['aktif', 'lulus', 'pindah', 'nonaktif'])->default('aktif');
            $table->string('kota_asal', 50)->nullable();
            $table->text('alamat_lengkap')->nullable();
            $table->string('nama_wali', 100);
            $table->string('no_whatsapp_wali', 25);
            $table->enum('hubungan_wali', [
                'Ayah Kandung', 'Ibu Kandung', 'Ayah Angkat', 'Ibu Angkat', 'Wali', 'Lainnya',
            ])->default('Ayah Kandung');
            $table->string('pin_portal', 10)->default('123456');
            $table->decimal('diskon_beasiswa', 5, 2)->default(0.00); // 0.00 - 100.00 (%)
            $table->boolean('bebas_wakaf')->default(false);
            $table->timestamps();
        });

        // 2. Payment Categories
        Schema::create('payment_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 30)->unique(); // SPP, WAKAF, FASILITAS, FORMULIR, BUKU, EVENT
            $table->enum('payment_type', ['monthly', 'installment', 'one_time', 'periodic', 'conditional'])->default('monthly');
            $table->decimal('default_amount', 12, 2)->default(0);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. Invoices Table
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 40)->unique();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('payment_category_id')->constrained('payment_categories')->cascadeOnDelete();
            $table->string('title', 150);
            $table->decimal('amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->date('due_date');
            $table->enum('status', ['unpaid', 'partial', 'paid', 'cancelled'])->default('unpaid');
            $table->unsignedTinyInteger('period_month')->nullable(); // 1 - 12
            $table->year('period_year')->nullable(); // 2026
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 4. Payments Table
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_code', 40)->unique();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->decimal('amount_paid', 12, 2);
            $table->date('payment_date');
            $table->enum('payment_method', ['cash', 'transfer'])->default('transfer');
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 5. Receipts Table (Digital Receipt with unique QR Token)
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number', 40)->unique();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->string('qr_verification_token', 64)->unique();
            $table->timestamp('issued_at')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipts');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('payment_categories');
        Schema::dropIfExists('students');
    }
};
