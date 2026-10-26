<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ExpenseCategory;
use App\Models\Expense;
use App\Services\ActivityLogService;
use App\Services\BookClosingService;
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

        // Kolom tabel yang boleh diurutkan lewat klik judul kolom.
        $allowedSorts = [
            'expense_date' => 'expenses.expense_date',
            'expense_code' => 'expenses.expense_code',
            'category' => 'expenses.category',
            'description' => 'expenses.description',
            'amount' => 'expenses.amount',
            'payment_method' => 'expenses.payment_method',
            'recipient' => 'expenses.recipient',
            'status' => 'expenses.voided_at',
        ];

        $sortBy = (string) $request->input('sort_by', 'expense_date');
        $sortOrder = strtolower((string) $request->input('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';

        if ($sortBy !== 'recorded_by' && ! array_key_exists($sortBy, $allowedSorts)) {
            $sortBy = 'expense_date';
        }

        $query = Expense::with(['recordedBy', 'voidedBy', 'bisyarahPayment'])
            ->whereDate('expense_date', '>=', $startDate)
            ->whereDate('expense_date', '<=', $endDate);

        if ($category) {
            $query->where('category', $category->value);
        }

        if ($sortBy === 'recorded_by') {
            $query->leftJoin('users', 'expenses.recorded_by', '=', 'users.id')
                ->orderBy('users.name', $sortOrder)
                ->select('expenses.*');
        } else {
            $query->orderBy($allowedSorts[$sortBy], $sortOrder);
        }

        if ($sortBy !== 'expense_date') {
            $query->orderBy('expenses.expense_date', 'desc');
        }

        $query->orderBy('expenses.id', 'desc');

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
            'categoryBreakdown',
            'sortBy',
            'sortOrder'
        ));
    }

    /**
     * Simpan pengeluaran operasional baru.
     */
    public function store(Request $request, BookClosingService $bookClosing)
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

        $bookClosing->assertOpen($data['expense_date'], 'pengeluaran');

        $data['expense_code'] = Expense::generateCode();
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
    public function void(Request $request, Expense $expense, BookClosingService $bookClosing)
    {
        $data = $request->validate([
            'void_reason' => ['required', 'string', 'max:500'],
        ], [
            'void_reason.required' => 'Alasan pembatalan wajib diisi.',
        ]);

        if ($expense->isVoided()) {
            return back()->with('info', 'Pengeluaran ini sudah dibatalkan sebelumnya.');
        }

        if ($expense->isManagedByBisyarah()) {
            return back()->withErrors([
                'void_reason' => 'Kas keluar '.$expense->expense_code.' berasal dari modul Bisyarah. Batalkan lewat halaman Bisyarah Bulanan agar catatan guru ikut tersinkron.',
            ]);
        }

        $bookClosing->assertOpen($expense->expense_date, 'pembatalan pengeluaran');

        $expense->voided_at = now();
        $expense->voided_by = auth()->id();
        $expense->void_reason = $data['void_reason'];
        $expense->save();

        ActivityLogService::expenseVoided($expense, $data['void_reason'], auth()->id());

        return back()->with('success', 'Kas keluar '.$expense->expense_code.' berhasil dibatalkan.');
    }
}
