<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Payment;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Kerangka laporan arus kas: menggabungkan kas masuk (pembayaran santri) dan
 * kas keluar (pengeluaran operasional) menjadi satu buku kas berurutan tanggal
 * dengan saldo berjalan.
 *
 * Ekspor CSV/PDF dan rekap per metode menyusul pada tahap berikutnya.
 */
class CashFlowService
{
    /**
     * Susun buku kas untuk rentang tanggal tertentu.
     *
     * @return array{
     *     openingBalance: float,
     *     closingBalance: float,
     *     entries: Collection<int, array<string, mixed>>,
     *     totalMasuk: float,
     *     totalKeluar: float,
     *     jumlahMasuk: int,
     *     jumlahKeluar: int,
     *     cashMasuk: float,
     *     cashKeluar: float,
     *     transferMasuk: float,
     *     transferKeluar: float
     * }
     */
    public function build(CarbonInterface $startDate, CarbonInterface $endDate): array
    {
        $start = $startDate->toDateString();
        $end = $endDate->toDateString();

        $openingBalance = $this->balanceBefore($start);

        $payments = Payment::with(['student', 'invoice.category', 'receipt'])
            ->whereNull('voided_at')
            ->whereDate('payment_date', '>=', $start)
            ->whereDate('payment_date', '<=', $end)
            ->get();

        $expenses = Expense::with('recordedBy')
            ->whereNull('voided_at')
            ->whereDate('expense_date', '>=', $start)
            ->whereDate('expense_date', '<=', $end)
            ->get();

        $entries = collect();

        foreach ($payments as $payment) {
            $entries->push([
                'date' => $payment->payment_date,
                'direction' => 'masuk',
                'code' => $payment->receipt?->receipt_number ?? $payment->payment_code,
                'description' => 'Penerimaan '.($payment->invoice?->category?->name ?? 'Pembayaran Santri'),
                'party' => $payment->received_from ?: ($payment->student?->nama_lengkap ?? '-'),
                'method' => $payment->payment_method,
                'amount' => (float) $payment->amount_paid,
                'sort_group' => 0,
                'sort_id' => (int) $payment->id,
            ]);
        }

        foreach ($expenses as $expense) {
            $entries->push([
                'date' => $expense->expense_date,
                'direction' => 'keluar',
                'code' => $expense->expense_code,
                'description' => $expense->description,
                'party' => $expense->recipient ?: ($expense->recordedBy?->name ?? '-'),
                'method' => $expense->payment_method,
                'amount' => (float) $expense->amount,
                'sort_group' => 1,
                'sort_id' => (int) $expense->id,
            ]);
        }

        $balance = $openingBalance;

        $entries = $entries
            ->sortBy(fn (array $entry) => sprintf(
                '%s-%d-%06d',
                $entry['date']->toDateString(),
                $entry['sort_group'],
                $entry['sort_id'],
            ))
            ->values()
            ->map(function (array $entry) use (&$balance): array {
                $signedAmount = $entry['direction'] === 'masuk' ? $entry['amount'] : -$entry['amount'];
                $balance += $signedAmount;

                $entry['signed_amount'] = $signedAmount;
                $entry['running_balance'] = $balance;

                return $entry;
            });

        return [
            'openingBalance' => $openingBalance,
            'closingBalance' => $balance,
            'entries' => $entries,
            'totalMasuk' => (float) $entries->where('direction', 'masuk')->sum('amount'),
            'totalKeluar' => (float) $entries->where('direction', 'keluar')->sum('amount'),
            'jumlahMasuk' => $entries->where('direction', 'masuk')->count(),
            'jumlahKeluar' => $entries->where('direction', 'keluar')->count(),
            'cashMasuk' => (float) $entries->where('direction', 'masuk')->where('method', 'cash')->sum('amount'),
            'cashKeluar' => (float) $entries->where('direction', 'keluar')->where('method', 'cash')->sum('amount'),
            'transferMasuk' => (float) $entries->where('direction', 'masuk')->where('method', 'transfer')->sum('amount'),
            'transferKeluar' => (float) $entries->where('direction', 'keluar')->where('method', 'transfer')->sum('amount'),
        ];
    }

    /**
     * Saldo kas sebelum tanggal awal periode (kas masuk - kas keluar).
     */
    private function balanceBefore(string $date): float
    {
        $masuk = (float) Payment::whereNull('voided_at')
            ->whereDate('payment_date', '<', $date)
            ->sum('amount_paid');

        $keluar = (float) Expense::whereNull('voided_at')
            ->whereDate('expense_date', '<', $date)
            ->sum('amount');

        return $masuk - $keluar;
    }
}
