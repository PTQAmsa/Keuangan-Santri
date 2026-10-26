<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Setiap catatan bisyarah kini terhubung ke satu baris kas keluar agar
     * laporan arus kas dan tutup buku otomatis memuat pembayaran bisyarah.
     *
     * Catatan lama yang belum punya kas keluar dibuatkan di sini supaya
     * saldo tidak melompat setelah pembaruan.
     */
    public function up(): void
    {
        Schema::table('teacher_bisyarah_payments', function (Blueprint $table) {
            $table->foreignId('expense_id')
                ->nullable()
                ->after('recorded_by')
                ->constrained('expenses')
                ->nullOnDelete();

            $table->unique('expense_id', 'teacher_bisyarah_expense_unique');
        });

        $this->backfillExpenses();
    }

    public function down(): void
    {
        Schema::table('teacher_bisyarah_payments', function (Blueprint $table) {
            $table->dropUnique('teacher_bisyarah_expense_unique');
            $table->dropConstrainedForeignId('expense_id');
        });
    }

    private function backfillExpenses(): void
    {
        $now = now();
        $sequences = [];

        DB::table('teacher_bisyarah_payments as b')
            ->join('teachers as t', 't.id', '=', 'b.teacher_id')
            ->whereNull('b.expense_id')
            ->orderBy('b.id')
            ->select('b.*', 't.nama_lengkap')
            ->get()
            ->each(function (object $row) use ($now, &$sequences): void {
                $periodStart = Carbon::create($row->period_year, $row->period_month, 1)->startOfMonth();
                $periodEnd = $periodStart->copy()->endOfMonth();
                $expenseDate = $now->copy()->startOfDay();

                if ($expenseDate->lessThan($periodStart)) {
                    $expenseDate = $periodStart;
                } elseif ($expenseDate->greaterThan($periodEnd)) {
                    $expenseDate = $periodEnd;
                }

                $prefix = 'EXP-'.$expenseDate->format('Ym').'-';

                if (! array_key_exists($prefix, $sequences)) {
                    $sequences[$prefix] = DB::table('expenses')
                        ->where('expense_code', 'like', $prefix.'%')
                        ->pluck('expense_code')
                        ->reduce(fn (int $max, string $code): int => max($max, (int) substr($code, strlen($prefix))), 0);
                }

                $sequences[$prefix]++;

                $expenseId = DB::table('expenses')->insertGetId([
                    'expense_code' => $prefix.str_pad((string) $sequences[$prefix], 4, '0', STR_PAD_LEFT),
                    'category' => 'gaji',
                    'description' => 'Bisyarah '.$row->nama_lengkap.' '.$periodStart->translatedFormat('F Y'),
                    'amount' => $row->amount,
                    'expense_date' => $expenseDate->toDateString(),
                    'payment_method' => $row->payment_method,
                    'recipient' => $row->nama_lengkap,
                    'notes' => $row->notes,
                    'recorded_by' => $row->recorded_by,
                    'created_at' => $row->created_at ?? $now,
                    'updated_at' => $now,
                ]);

                DB::table('teacher_bisyarah_payments')
                    ->where('id', $row->id)
                    ->update(['expense_id' => $expenseId, 'updated_at' => $now]);
            });
    }
};
