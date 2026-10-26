<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Alasan guru dinonaktifkan (mis. pindah, berhenti mengajar) beserta
     * waktunya, agar riwayat tetap jelas saat data dibaca kemudian hari.
     */
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->string('inactive_reason', 255)->nullable()->after('is_active');
            $table->timestamp('inactivated_at')->nullable()->after('inactive_reason');
        });
    }

    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn(['inactive_reason', 'inactivated_at']);
        });
    }
};
