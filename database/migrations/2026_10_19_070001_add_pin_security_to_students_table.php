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
        Schema::table('students', function (Blueprint $table) {
            // Keamanan PIN portal wali santri.
            $table->boolean('must_change_pin')->default(false)->after('pin_portal');
            $table->timestamp('pin_changed_at')->nullable()->after('must_change_pin');
            $table->timestamp('pin_reset_requested_at')->nullable()->after('pin_changed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['must_change_pin', 'pin_changed_at', 'pin_reset_requested_at']);
        });
    }
};
