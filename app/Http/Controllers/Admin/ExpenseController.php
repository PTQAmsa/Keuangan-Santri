<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ExpenseCategory;
use App\Models\Expense;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExpenseController
{
    /**
     * Daftar pengeluaran operasional (kas keluar) beserta ringkasannya.
     */
    public function index(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());
        $category = ExpenseCategory::tryFrom((string) $request->input('category'));

        $query = Expense::with(['recordedBy', 'voidedBy'])
            ->whereDate('expense_date', '>=', $startDate)
            ->whereDate('expense_date', '<=', $endDate)
            ->latest('expense_date')
            ->latest('id');

        if ($category) {
            $query->where('category', $category->value);
        }

        $expenses = $query->get();
        $activeExpenses = $expenses->whereNull('voided_at');

        $totalPengeluaran = (float) $activeExpenses->sum(fn (Expense $expense) => (float) $expense->amount);
        $totalTransaksi = $activeExpenses->count();
        $totalCash = (float) $activeExpenses->where('payment_method', 'cash')->sum(fn (Expense $expense) => (float) $expense->amount);
        $totalTransfer = (float) $activeExpenses->where('payment_method', 'transfer')->sum(fn (Expense $expense) => (float) $expense->amount);

        $categoryBreakdown = $activeExpenses
            ->groupBy(fn (Expense $expense) => $expense->category->label())
            ->map(fn ($group) => [
                'count' => $group->count(),
                'total' => (float) $group->sum(fn (Expense $expense) => (float) $expense->amount),
            ])
            ->sortByDesc('total');

        $categories = ExpenseCategory::cases();

        return view('admin.expenses', compact(
            'expenses',
            'startDate',
            'endDate',
            'category',
            'categories',
            'totalPengeluaran',
            'totalTransaksi',
            'totalCash',
            'totalTransfer',
            'categoryBreakdown'
        ));
    }

    /**
     * Simpan pengeluaran operasional baru.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'category' => ['required', Rule::enum(ExpenseCategory::class)],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:1', 'max:999999999999'],
            'expense_date' => ['required', 'date'],
            'payment_method' => ['required', 'in:cash,transfer'],
            'recipient' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'category.required' => 'Kategori pengeluaran wajib dipilih.',
            'description.required' => 'Uraian pengeluaran wajib diisi.',
            'amount.required' => 'Nominal pengeluaran wajib diisi.',
            'amount.min' => 'Nominal pengeluaran harus lebih dari nol.',
            'expense_date.required' => 'Tanggal pengeluaran wajib diisi.',
            'payment_method.required' => 'Metode pembayaran wajib dipilih.',
        ], [
            'category' => 'kategori',
            'description' => 'uraian',
            'amount' => 'nominal',
            'expense_date' => 'tanggal',
            'payment_method' => 'metode',
            'recipient' => 'penerima',
            'notes' => 'catatan',
        ]);

        $monthYear = now()->format('Ym');
        $sequence = Expense::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count() + 1;

        do {
            $code = 'EXP-'.$monthYear.'-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
            $sequence++;
        } while (Expense::where('expense_code', $code)->exists());

        $data['expense_code'] = $code;
        $data['recorded_by'] = auth()->id();

        $expense = Expense::create($data);

        ActivityLogService::expenseRecorded($expense, auth()->id());

        return redirect()
            ->route('admin.expenses')
            ->with('success', 'Kas keluar '.$expense->expense_code.' sebesar Rp '.number_format((float) $expense->amount, 0, ',', '.').' berhasil dicatat.');
    }

    /**
     * Batalkan pengeluaran yang salah input. Data tetap tersimpan untuk audit.
     */
    public function void(Request $request, Expense $expense)
    {
        $data = $request->validate([
            'void_reason' => ['required', 'string', 'max:500'],
        ], [
            'void_reason.required' => 'Alasan pembatalan wajib diisi.',
        ]);

        if ($expense->isVoided()) {
            return back()->with('info', 'Pengeluaran ini sudah dibatalkan sebelumnya.');
        }

        $expense->voided_at = now();
        $expense->voided_by = auth()->id();
        $expense->void_reason = $data['void_reason'];
        $expense->save();

        ActivityLogService::expenseVoided($expense, $data['void_reason'], auth()->id());

        return back()->with('success', 'Kas keluar '.$expense->expense_code.' berhasil dibatalkan.');
    }
}
