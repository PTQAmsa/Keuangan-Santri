<?php

namespace App\Services;

use App\Enums\ExpenseCategory;
use App\Models\Expense;
use App\Models\TeacherBisyarahPayment;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Menjaga agar setiap catatan bisyarah selalu punya satu baris kas keluar
 * kategori Bisyarah, sehingga laporan arus kas, dashboard, dan tutup buku
 * otomatis memuat pembayaran bisyarah tanpa pencatatan ganda.
 */
class BisyarahExpenseService
{
    /**
     * Buat atau perbarui kas keluar milik catatan bisyarah ini.
     */
    public function sync(TeacherBisyarahPayment $payment, ?int $userId = null): Expense
    {
        $payment->loadMissing(['teacher', 'expense']);

        $attributes = [
            'category' => ExpenseCategory::Gaji,
            'description' => 'Bisyarah '.$payment->teacher->nama_lengkap.' '.$payment->periodLabel(),
            'amount' => $payment->amount,
            'payment_method' => $payment->payment_method,
            'recipient' => $payment->teacher->nama_lengkap,
            'notes' => $this->expenseNotes($payment),
        ];

        $expense = $payment->expense;

        if ($expense !== null && ! $expense->isVoided()) {
            $expense->fill($attributes)->save();

            return $expense;
        }

        $expenseDate = $this->expenseDateFor($payment->period_year, $payment->period_month);

        $expense = Expense::create($attributes + [
            'expense_code' => Expense::generateCode($expenseDate),
            'expense_date' => $expenseDate->toDateString(),
            'recorded_by' => $userId ?? $payment->recorded_by,
        ]);

        $payment->expense()->associate($expense);
        $payment->save();

        return $expense;
    }

    /**
     * Batalkan kas keluar bisyarah, lalu hapus catatan bisyarahnya agar
     * guru tersebut dapat dicatat ulang pada periode yang sama.
     */
    public function cancel(TeacherBisyarahPayment $payment, string $reason, ?int $userId = null): ?Expense
    {
        $payment->loadMissing('expense');
        $expense = $payment->expense;

        if ($expense !== null && ! $expense->isVoided()) {
            $expense->voided_at = now();
            $expense->voided_by = $userId;
            $expense->void_reason = $reason;
            $expense->save();
        }

        $payment->delete();

        return $expense;
    }

    /**
     * Tanggal kas keluar mengikuti hari pencatatan selama masih di dalam
     * bulan periode. Periode yang sudah lewat memakai akhir bulan tersebut,
     * periode mendatang memakai awal bulannya, supaya sejalan dengan
     * penguncian tutup buku yang berbasis periode.
     */
    public function expenseDateFor(int $year, int $month, ?CarbonInterface $today = null): Carbon
    {
        $today = Carbon::instance($today ?? now())->startOfDay();
        $periodStart = Carbon::create($year, $month, 1)->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth()->startOfDay();

        if ($today->lessThan($periodStart)) {
            return $periodStart;
        }

        if ($today->greaterThan($periodEnd)) {
            return $periodEnd;
        }

        return $today;
    }

    private function expenseNotes(TeacherBisyarahPayment $payment): ?string
    {
        $parts = [];

        if ($payment->hasBankAccount()) {
            $parts[] = 'Transfer ke '.trim(($payment->bank_name ?: 'Bank').' '.$payment->bank_account_number)
                .($payment->bank_account_name ? ' a.n. '.$payment->bank_account_name : '');
        }

        if (filled($payment->notes)) {
            $parts[] = $payment->notes;
        }

        return $parts === [] ? null : implode('. ', $parts);
    }
}
