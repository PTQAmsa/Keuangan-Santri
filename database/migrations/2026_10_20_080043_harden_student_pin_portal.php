<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Perlebar kolom PIN agar cukup menampung hash, lalu hash PIN lama.
     *
     * Catatan rollback: PIN yang sudah di-hash tidak bisa dikembalikan menjadi
     * plaintext. Rollback hanya mengembalikan lebar kolom; data PIN harus
     * dipulihkan dari backup bila benar-benar diperlukan.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('pin_portal', 255)->default('')->change();
        });

        DB::table('students')
            ->select(['id', 'pin_portal', 'pin_changed_at'])
            ->orderBy('id')
            ->chunkById(100, function ($students): void {
                foreach ($students as $student) {
                    $pin = (string) $student->pin_portal;

                    $updates = [];

                    if ($pin !== '' && Hash::needsRehash($pin)) {
                        $updates['pin_portal'] = Hash::make($pin);
                    }

                    // PIN lama yang belum pernah diganti wajib diganti saat login berikutnya.
                    if ($student->pin_changed_at === null) {
                        $updates['must_change_pin'] = true;
                    }

                    if ($updates !== []) {
                        DB::table('students')->where('id', $student->id)->update($updates);
                    }
                }
            });
    }

    /**
     * Rollback tidak dapat memulihkan PIN plaintext dari hash.
     */
    public function down(): void
    {
        // Sengaja tidak mengubah data. Pulihkan dari backup bila perlu rollback.
    }
};
