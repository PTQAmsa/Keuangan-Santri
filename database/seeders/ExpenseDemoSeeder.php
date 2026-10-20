<?php

namespace Database\Seeders;

use App\Enums\ExpenseCategory;
use App\Models\Expense;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Database\Seeder;

class ExpenseDemoSeeder extends Seeder
{
    /**
     * Contoh pengeluaran operasional untuk demo kas keluar.
     */
    public function run(): void
    {
        $bendahara = User::where('email', 'bendahara1@ptqamsa.id')->firstOrFail();

        $expenses = [
            [
                'expense_code' => 'EXP-202607-0001',
                'category' => ExpenseCategory::Operasional,
                'description' => 'Pembelian alat kebersihan asrama',
                'amount' => 350000,
                'expense_date' => '2026-07-05',
                'payment_method' => 'cash',
                'recipient' => 'Toko Bangunan Amanah',
                'notes' => 'Sapu, pel, tempat sampah, dan pembersih lantai.',
            ],
            [
                'expense_code' => 'EXP-202607-0002',
                'category' => ExpenseCategory::Pemeliharaan,
                'description' => 'Perbaikan kran dan pipa kamar mandi',
                'amount' => 750000,
                'expense_date' => '2026-07-18',
                'payment_method' => 'transfer',
                'recipient' => 'CV Karya Teknik',
                'notes' => 'Perbaikan 4 titik kran bocor di asrama putra.',
            ],
            [
                'expense_code' => 'EXP-202610-0001',
                'category' => ExpenseCategory::Konsumsi,
                'description' => 'Konsumsi rapat wali santri',
                'amount' => 1250000,
                'expense_date' => '2026-10-12',
                'payment_method' => 'cash',
                'recipient' => 'Dapur Pesantren',
                'notes' => 'Snack dan makan siang peserta rapat.',
            ],
        ];

        foreach ($expenses as $data) {
            $expense = Expense::firstOrCreate(
                ['expense_code' => $data['expense_code']],
                $data + ['recorded_by' => $bendahara->id]
            );

            if ($expense->wasRecentlyCreated) {
                ActivityLogService::expenseRecorded($expense, $bendahara->id);
            }
        }

        $voided = Expense::firstOrCreate(
            ['expense_code' => 'EXP-202610-0002'],
            [
                'category' => ExpenseCategory::Transport,
                'description' => 'Sewa kendaraan operasional (salah input)',
                'amount' => 500000,
                'expense_date' => '2026-10-15',
                'payment_method' => 'cash',
                'recipient' => 'Pak Rahmat',
                'notes' => 'Contoh data yang dibatalkan karena salah input.',
                'recorded_by' => $bendahara->id,
            ]
        );

        if ($voided->wasRecentlyCreated) {
            ActivityLogService::expenseRecorded($voided, $bendahara->id);

            $voided->voided_at = now();
            $voided->voided_by = $bendahara->id;
            $voided->void_reason = 'Salah input nominal; data yang benar sudah dicatat terpisah.';
            $voided->save();

            ActivityLogService::expenseVoided($voided, $voided->void_reason, $bendahara->id);
        }
    }
}
