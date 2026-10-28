<?php

namespace App\Http\Controllers\Admin;

use App\Enums\IncomeCategory;
use App\Enums\TransferChannel;
use App\Models\OtherIncome;
use App\Services\ActivityLogService;
use App\Services\BookClosingService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Pemasukan lainnya (kas masuk non-santri): donasi, infaq, hibah, unit usaha,
 * sewa, dan sebagainya. Pembayaran santri tetap lewat Kasir.
 */
class OtherIncomeController
{
    public function index(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());
        $category = IncomeCategory::tryFrom((string) $request->input('category'));

        $allowedSorts = [
            'income_date' => 'other_incomes.income_date',
            'income_code' => 'other_incomes.income_code',
            'category' => 'other_incomes.category',
            'description' => 'other_incomes.description',
            'amount' => 'other_incomes.amount',
            'payment_method' => 'other_incomes.payment_method',
            'source' => 'other_incomes.source',
            'status' => 'other_incomes.voided_at',
        ];

        $sortBy = (string) $request->input('sort_by', 'income_date');
        $sortOrder = strtolower((string) $request->input('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';

        if ($sortBy !== 'recorded_by' && ! array_key_exists($sortBy, $allowedSorts)) {
            $sortBy = 'income_date';
        }

        $query = OtherIncome::with(['recordedBy', 'voidedBy'])
            ->whereDate('income_date', '>=', $startDate)
            ->whereDate('income_date', '<=', $endDate);

        if ($category) {
            $query->where('category', $category->value);
        }

        if ($sortBy === 'recorded_by') {
            $query->leftJoin('users', 'other_incomes.recorded_by', '=', 'users.id')
                ->orderBy('users.name', $sortOrder)
                ->select('other_incomes.*');
        } else {
            $query->orderBy($allowedSorts[$sortBy], $sortOrder);
        }

        if ($sortBy !== 'income_date') {
            $query->orderBy('other_incomes.income_date', 'desc');
        }

        $query->orderBy('other_incomes.id', 'desc');

        $incomes = $query->get();
        $activeIncomes = $incomes->whereNull('voided_at');

        $totalPemasukan = (float) $activeIncomes->sum(fn (OtherIncome $income) => (float) $income->amount);
        $totalTransaksi = $activeIncomes->count();
        $totalCash = (float) $activeIncomes->where('payment_method', 'cash')->sum(fn (OtherIncome $income) => (float) $income->amount);
        $totalTransfer = (float) $activeIncomes->where('payment_method', 'transfer')->sum(fn (OtherIncome $income) => (float) $income->amount);

        $categoryBreakdown = $activeIncomes
            ->groupBy(fn (OtherIncome $income) => $income->category->label())
            ->map(fn ($group) => [
                'count' => $group->count(),
                'total' => (float) $group->sum(fn (OtherIncome $income) => (float) $income->amount),
            ])
            ->sortByDesc('total');

        $categories = IncomeCategory::cases();

        return view('admin.incomes', compact(
            'incomes',
            'startDate',
            'endDate',
            'category',
            'categories',
            'totalPemasukan',
            'totalTransaksi',
            'totalCash',
            'totalTransfer',
            'categoryBreakdown',
            'sortBy',
            'sortOrder'
        ));
    }

    public function store(Request $request, BookClosingService $bookClosing)
    {
        $data = $request->validate([
            'category' => ['required', Rule::enum(IncomeCategory::class)],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:1', 'max:999999999999'],
            'income_date' => ['required', 'date'],
            'payment_method' => ['required', 'in:cash,transfer'],
            'transfer_channel' => ['required_if:payment_method,transfer', 'nullable', Rule::enum(TransferChannel::class)],
            'transfer_channel_other' => ['required_if:transfer_channel,'.TransferChannel::Lainnya->value, 'nullable', 'string', 'max:50'],
            'source' => ['required', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'category.required' => 'Jenis pemasukan wajib dipilih.',
            'description.required' => 'Uraian pemasukan wajib diisi.',
            'amount.required' => 'Nominal pemasukan wajib diisi.',
            'amount.min' => 'Nominal pemasukan harus lebih dari nol.',
            'income_date.required' => 'Tanggal pemasukan wajib diisi.',
            'payment_method.required' => 'Metode penerimaan wajib dipilih.',
            'transfer_channel.required_if' => 'Pilih lewat mana transfernya masuk: bank atau dompet digital.',
            'transfer_channel.Illuminate\Validation\Rules\Enum' => 'Saluran transfer tidak dikenali.',
            'transfer_channel_other.required_if' => 'Tuliskan nama bank atau dompet digital lainnya.',
            'source.required' => 'Sumber / pemberi wajib diisi.',
        ], [
            'category' => 'jenis pemasukan',
            'description' => 'uraian',
            'amount' => 'nominal',
            'income_date' => 'tanggal',
            'payment_method' => 'metode',
            'transfer_channel' => 'saluran transfer',
            'transfer_channel_other' => 'nama saluran lainnya',
            'source' => 'sumber',
            'notes' => 'catatan',
        ]);

        $bookClosing->assertOpen($data['income_date'], 'pemasukan');

        $data['transfer_channel'] = $data['payment_method'] === 'transfer'
            ? TransferChannel::resolveName($data['transfer_channel'] ?? null, $data['transfer_channel_other'] ?? null)
            : null;
        unset($data['transfer_channel_other']);

        $data['income_code'] = OtherIncome::generateCode();
        $data['recorded_by'] = auth()->id();

        $income = OtherIncome::create($data);

        ActivityLogService::incomeRecorded($income, auth()->id());

        return redirect()
            ->route('admin.incomes')
            ->with('success', 'Pemasukan '.$income->income_code.' sebesar Rp '.number_format((float) $income->amount, 0, ',', '.').' ('.$income->paymentMethodLabel().') berhasil dicatat.');
    }

    /**
     * Batalkan pemasukan yang salah input. Data tetap tersimpan untuk audit.
     */
    public function void(Request $request, OtherIncome $income, BookClosingService $bookClosing)
    {
        $data = $request->validate([
            'void_reason' => ['required', 'string', 'max:500'],
        ], [
            'void_reason.required' => 'Alasan pembatalan wajib diisi.',
        ]);

        if ($income->isVoided()) {
            return back()->with('info', 'Pemasukan ini sudah dibatalkan sebelumnya.');
        }

        $bookClosing->assertOpen($income->income_date, 'pembatalan pemasukan');

        $income->voided_at = now();
        $income->voided_by = auth()->id();
        $income->void_reason = $data['void_reason'];
        $income->save();

        ActivityLogService::incomeVoided($income, $data['void_reason'], auth()->id());

        return back()->with('success', 'Pemasukan '.$income->income_code.' berhasil dibatalkan.');
    }
}
