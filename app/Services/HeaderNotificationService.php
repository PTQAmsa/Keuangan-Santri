<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Teacher;
use App\Models\TeacherBisyarahPayment;
use App\Models\User;

/**
 * Pusat informasi di header panel: hal-hal yang perlu perhatian Bendahara
 * hari ini, dihitung langsung dari data (tanpa tabel notifikasi terpisah).
 */
class HeaderNotificationService
{
    /**
     * @return array{count: int, items: list<array{icon: string, color: string, title: string, text: string, url: string}>}
     */
    public function build(?User $user): array
    {
        $today = now()->startOfDay();
        $items = [];

        // 1. Tagihan lewat jatuh tempo & mendekati jatuh tempo (7 hari ke depan).
        $overdue = Invoice::query()
            ->whereIn('status', ['unpaid', 'partial'])
            ->whereDate('due_date', '<', $today)
            ->count();

        if ($overdue > 0) {
            $items[] = [
                'icon' => 'fa-triangle-exclamation',
                'color' => 'rose',
                'title' => $overdue.' tagihan lewat jatuh tempo',
                'text' => 'Belum lunas dan sudah melewati tanggal jatuh tempo. Perlu ditagihkan ke wali.',
                'url' => route('admin.reports.arrears'),
            ];
        }

        $dueSoon = Invoice::query()
            ->whereIn('status', ['unpaid', 'partial'])
            ->whereDate('due_date', '>=', $today)
            ->whereDate('due_date', '<=', $today->copy()->addDays(7))
            ->count();

        if ($dueSoon > 0) {
            $items[] = [
                'icon' => 'fa-hourglass-half',
                'color' => 'amber',
                'title' => $dueSoon.' tagihan jatuh tempo pekan ini',
                'text' => 'Jatuh tempo dalam 7 hari ke depan. Ingatkan wali lewat WhatsApp.',
                'url' => route('admin.reports.arrears'),
            ];
        }

        // 2. Pembayaran transfer hari ini yang perlu dicocokkan dengan mutasi rekening.
        $transfersToday = Payment::query()
            ->whereNull('voided_at')
            ->where('payment_method', 'transfer')
            ->whereDate('payment_date', $today)
            ->count();

        if ($transfersToday > 0) {
            $items[] = [
                'icon' => 'fa-building-columns',
                'color' => 'blue',
                'title' => $transfersToday.' pembayaran transfer hari ini',
                'text' => 'Cocokkan dengan mutasi rekening pesantren sebelum tutup kas.',
                'url' => route('admin.transactions', ['status' => 'active']),
            ];
        }

        // 3. Bisyarah guru bulan ini yang belum dicatat.
        $activeTeachers = Teacher::active()->count();
        $recorded = TeacherBisyarahPayment::forPeriod(now()->year, now()->month)
            ->whereIn('teacher_id', Teacher::active()->select('id'))
            ->count();
        $pending = max(0, $activeTeachers - $recorded);

        if ($pending > 0) {
            $items[] = [
                'icon' => 'fa-chalkboard-user',
                'color' => 'violet',
                'title' => $pending.' guru belum dicatat bisyarahnya',
                'text' => 'Bisyarah '.now()->translatedFormat('F Y').' belum lengkap. Catat di menu Guru, Staf & Bisyarah.',
                'url' => route('admin.bisyarah', ['period' => now()->format('Y-m')]),
            ];
        }

        // 4. Pengingat kas keluar operasional bulan berjalan.
        $expensesThisMonth = Expense::query()
            ->whereNull('voided_at')
            ->whereYear('expense_date', now()->year)
            ->whereMonth('expense_date', now()->month);

        $expenseCount = (clone $expensesThisMonth)->count();
        $expenseTotal = (float) (clone $expensesThisMonth)->sum('amount');

        $items[] = [
            'icon' => 'fa-money-bill-trend-up',
            'color' => 'slate',
            'title' => $expenseCount > 0
                ? 'Kas keluar '.now()->translatedFormat('F').': Rp '.number_format($expenseTotal, 0, ',', '.')
                : 'Belum ada kas keluar '.now()->translatedFormat('F'),
            'text' => $expenseCount > 0
                ? $expenseCount.' pengeluaran tercatat. Pastikan semua nota operasional sudah dimasukkan.'
                : 'Catat pengeluaran operasional agar laporan arus kas lengkap.',
            'url' => route('admin.expenses'),
        ];

        // Badge hanya menghitung hal yang benar-benar perlu ditindak.
        $count = $overdue + $dueSoon + $transfersToday + ($pending ?? 0);

        return ['count' => $count, 'items' => $items];
    }
}
