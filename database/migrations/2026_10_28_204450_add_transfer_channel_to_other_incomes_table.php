<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pemasukan lainnya yang diterima lewat transfer mencatat lewat bank atau
     * dompet digital mana uangnya masuk. Pembayaran santri di kasir tetap
     * memakai rekening resmi pesantren dan tidak ikut berubah.
     */
    public function up(): void
    {
        Schema::table('other_incomes', function (Blueprint $table) {
            $table->string('transfer_channel', 50)->nullable()->after('payment_method');
        });
    }

    public function down(): void
    {
        Schema::table('other_incomes', function (Blueprint $table) {
            $table->dropColumn('transfer_channel');
        });
    }
};
