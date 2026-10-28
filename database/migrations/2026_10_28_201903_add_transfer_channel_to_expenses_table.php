<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kas keluar transfer kini mencatat lewat mana uangnya keluar
     * (bank atau dompet digital). Kas keluar bisyarah yang sudah ada
     * mengambil nama bank dari catatan bisyarahnya.
     */
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->string('transfer_channel', 50)->nullable()->after('payment_method');
        });

        DB::table('teacher_bisyarah_payments')
            ->whereNotNull('expense_id')
            ->where('payment_method', 'transfer')
            ->whereNotNull('bank_name')
            ->select('expense_id', 'bank_name')
            ->orderBy('id')
            ->get()
            ->each(function (object $row): void {
                DB::table('expenses')
                    ->where('id', $row->expense_id)
                    ->update(['transfer_channel' => $row->bank_name]);
            });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn('transfer_channel');
        });
    }
};
