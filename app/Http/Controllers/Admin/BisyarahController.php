<?php

namespace App\Http\Controllers\Admin;

use App\Models\BookClosing;
use App\Models\Teacher;
use App\Models\TeacherBisyarahPayment;
use App\Services\ActivityLogService;
use App\Services\BisyarahExpenseService;
use App\Services\BookClosingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Pencatatan bisyarah guru per bulan.
 *
 * Data guru cukup diisi sekali. Setiap bulan Bendahara memilih nama guru,
 * mengisi nominal, lalu memilih Transfer (dengan bank & rekening) atau Tunai.
 * Setiap catatan otomatis menjadi satu baris kas keluar kategori Bisyarah.
 */
class BisyarahController
{
    public function __construct(private readonly BisyarahExpenseService $expenses) {}

    public function index(Request $request)
    {
        $period = $this->resolvePeriod($request->input('period'));
        $year = (int) $period->year;
        $month = (int) $period->month;

        $payments = TeacherBisyarahPayment::with(['teacher', 'expense'])
            ->forPeriod($year, $month)
            ->get()
            ->keyBy('teacher_id');

        $teachers = Teacher::query()
            ->where('is_active', true)
            ->orWhereIn('id', $payments->pluck('teacher_id'))
            ->orderByDesc('is_active')
            ->orderBy('nama_lengkap')
            ->get();

        // Urutkan lewat klik judul kolom. Sebagian kolom berasal dari catatan
        // bisyarah bulan ini, jadi diurutkan setelah data diambil.
        $allowedSorts = ['teacher', 'whatsapp', 'recorded', 'amount', 'method', 'account'];
        $sortBy = (string) $request->input('sort_by', 'teacher');
        $sortOrder = strtolower((string) $request->input('sort_order', 'asc')) === 'desc' ? 'desc' : 'asc';

        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'teacher';
        }

        $teachers = $teachers
            ->sortBy(function (Teacher $teacher) use ($payments, $sortBy) {
                $payment = $payments->get($teacher->id);

                return match ($sortBy) {
                    'whatsapp' => (string) $teacher->no_whatsapp,
                    'recorded' => $payment ? 1 : 0,
                    'amount' => $payment ? (float) $payment->amount : -1.0,
                    'method' => $payment?->payment_method ?? '',
                    'account' => $payment?->bank_account_number ?? '',
                    default => mb_strtolower($teacher->nama_lengkap),
                };
            }, SORT_REGULAR, $sortOrder === 'desc')
            ->values();

        $activeTeacherCount = Teacher::active()->count();
        $activeRecordedCount = $teachers
            ->where('is_active', true)
            ->filter(fn (Teacher $teacher) => $payments->has($teacher->id))
            ->count();

        $closing = BookClosing::forPeriod($year, $month);
        $isLocked = $closing?->isClosed() ?? false;

        $totalAmount = (float) $payments->sum('amount');
        $totalTransfer = (float) $payments->where('payment_method', 'transfer')->sum('amount');
        $totalCash = (float) $payments->where('payment_method', 'cash')->sum('amount');

        // Rekening terakhir dipakai sebagai isian awal saat memilih guru,
        // supaya Bendahara tidak mengetik ulang setiap bulan.
        $lastTransferAccounts = TeacherBisyarahPayment::query()
            ->whereIn('teacher_id', $teachers->pluck('id'))
            ->where('payment_method', 'transfer')
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->orderByDesc('id')
            ->get()
            ->unique('teacher_id')
            ->keyBy('teacher_id');

        return view('admin.bisyarah', [
            'teachers' => $teachers,
            'payments' => $payments,
            'lastTransferAccounts' => $lastTransferAccounts,
            'period' => $period->format('Y-m'),
            'periodLabel' => $period->translatedFormat('F Y'),
            'activeTeacherCount' => $activeTeacherCount,
            'activeRecordedCount' => $activeRecordedCount,
            'recordedCount' => $payments->count(),
            'totalAmount' => $totalAmount,
            'totalTransfer' => $totalTransfer,
            'totalCash' => $totalCash,
            'closing' => $closing,
            'isLocked' => $isLocked,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
        ]);
    }

    public function store(Request $request, BookClosingService $bookClosing)
    {
        $data = $this->validated($request);
        $period = $this->resolvePeriod($data['period']);
        $periodLabel = $period->translatedFormat('F Y');

        $bookClosing->assertOpen($period, 'bisyarah guru');

        $duplicate = TeacherBisyarahPayment::query()
            ->where('teacher_id', $data['teacher_id'])
            ->where('period_year', $period->year)
            ->where('period_month', $period->month)
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'teacher_id' => 'Bisyarah guru ini untuk periode '.$periodLabel.' sudah dicatat. Gunakan tombol Ubah pada baris guru tersebut.',
            ]);
        }

        $teacher = Teacher::findOrFail($data['teacher_id']);

        $payment = DB::transaction(function () use ($teacher, $period, $data): TeacherBisyarahPayment {
            $payment = TeacherBisyarahPayment::create([
                'teacher_id' => $teacher->id,
                'period_year' => $period->year,
                'period_month' => $period->month,
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'],
                'bank_name' => $data['payment_method'] === 'transfer' ? $data['bank_name'] : null,
                'bank_account_number' => $data['payment_method'] === 'transfer' ? $data['bank_account_number'] : null,
                'bank_account_name' => $data['payment_method'] === 'transfer' ? ($data['bank_account_name'] ?? null) : null,
                'notes' => $data['notes'] ?? null,
                'recorded_by' => auth()->id(),
            ]);

            $this->expenses->sync($payment, auth()->id());

            ActivityLogService::bisyarahRecorded($payment);

            return $payment;
        });

        return redirect()
            ->route('admin.bisyarah', ['period' => $period->format('Y-m')])
            ->with('success', 'Bisyarah '.$teacher->nama_lengkap.' periode '.$periodLabel.' sebesar Rp '.number_format((float) $payment->amount, 0, ',', '.').' ('.$payment->paymentMethodLabel().') berhasil dicatat dan masuk kas keluar '.$payment->expense->expense_code.'.');
    }

    public function update(Request $request, TeacherBisyarahPayment $bisyarah, BookClosingService $bookClosing)
    {
        $data = $this->validated($request, false);
        $period = Carbon::create($bisyarah->period_year, $bisyarah->period_month, 1)->startOfMonth();

        $bookClosing->assertOpen($period, 'bisyarah guru');

        $changes = [];

        if ((float) $bisyarah->amount !== (float) $data['amount']) {
            $changes['Nominal Bisyarah'] = [
                'Rp '.number_format((float) $bisyarah->amount, 0, ',', '.'),
                'Rp '.number_format((float) $data['amount'], 0, ',', '.'),
            ];
        }

        if ($bisyarah->payment_method !== $data['payment_method']) {
            $changes['Metode'] = [$bisyarah->paymentMethodLabel(), $data['payment_method'] === 'cash' ? 'Tunai' : 'Transfer'];
        }

        if ($data['payment_method'] === 'transfer') {
            if (($data['bank_account_number'] ?? '') !== (string) $bisyarah->bank_account_number) {
                $changes['Nomor Rekening'] = [
                    $bisyarah->bank_account_number ?: '-',
                    $data['bank_account_number'] ?: '-',
                ];
            }
        } elseif ($bisyarah->bank_account_number !== null) {
            $changes['Rekening Tujuan'] = [
                trim(($bisyarah->bank_name ?? 'Bank').' '.$bisyarah->bank_account_number),
                'Dihapus karena pembayaran tunai',
            ];
        }

        if (($data['notes'] ?? null) !== $bisyarah->notes) {
            $changes['Catatan'] = [$bisyarah->notes ?: '-', $data['notes'] ?: '-'];
        }

        DB::transaction(function () use ($bisyarah, $data, $changes): void {
            $bisyarah->fill([
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'],
                'bank_name' => $data['payment_method'] === 'transfer' ? $data['bank_name'] : null,
                'bank_account_number' => $data['payment_method'] === 'transfer' ? $data['bank_account_number'] : null,
                'bank_account_name' => $data['payment_method'] === 'transfer' ? ($data['bank_account_name'] ?? null) : null,
                'notes' => $data['notes'] ?? null,
            ])->save();

            $this->expenses->sync($bisyarah, auth()->id());

            if ($changes !== []) {
                ActivityLogService::bisyarahUpdated($bisyarah, $changes);
            }
        });

        return redirect()
            ->route('admin.bisyarah', ['period' => $period->format('Y-m')])
            ->with('success', 'Bisyarah '.$bisyarah->teacher->nama_lengkap.' periode '.$bisyarah->periodLabel().' berhasil diperbarui. Kas keluar '.$bisyarah->expense->expense_code.' ikut disesuaikan.');
    }

    /**
     * Batalkan catatan bisyarah: kas keluarnya ditandai batal (tetap tersimpan
     * untuk audit), catatan gurunya dihapus agar bisa dicatat ulang.
     */
    public function destroy(Request $request, TeacherBisyarahPayment $bisyarah, BookClosingService $bookClosing)
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ], [
            'reason.required' => 'Alasan pembatalan bisyarah wajib diisi.',
        ], [
            'reason' => 'alasan',
        ]);

        $period = Carbon::create($bisyarah->period_year, $bisyarah->period_month, 1)->startOfMonth();

        $bookClosing->assertOpen($period, 'pembatalan bisyarah guru');

        $bisyarah->loadMissing('teacher');
        $teacherName = $bisyarah->teacher->nama_lengkap;
        $periodLabel = $bisyarah->periodLabel();

        DB::transaction(function () use ($bisyarah, $data): void {
            $expense = $this->expenses->cancel($bisyarah, $data['reason'], auth()->id());

            ActivityLogService::bisyarahCancelled($bisyarah, $data['reason'], $expense);
        });

        return redirect()
            ->route('admin.bisyarah', ['period' => $period->format('Y-m')])
            ->with('success', 'Bisyarah '.$teacherName.' periode '.$periodLabel.' dibatalkan beserta kas keluarnya.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, bool $withPeriod = true): array
    {
        $rules = [
            'amount' => ['required', 'numeric', 'min:1', 'max:999999999999'],
            'payment_method' => ['required', 'in:transfer,cash'],
            'bank_name' => ['required_if:payment_method,transfer', 'nullable', 'string', 'max:50'],
            'bank_account_number' => ['required_if:payment_method,transfer', 'nullable', 'string', 'max:40'],
            'bank_account_name' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];

        if ($withPeriod) {
            $rules['teacher_id'] = ['required', 'exists:teachers,id'];
            $rules['period'] = ['required', 'date_format:Y-m'];
        }

        return $request->validate($rules, [
            'teacher_id.required' => 'Pilih guru yang akan dibayar bisyarahnya.',
            'teacher_id.exists' => 'Data guru yang dipilih tidak ditemukan.',
            'period.required' => 'Periode bisyarah wajib dipilih.',
            'period.date_format' => 'Format periode tidak dikenali.',
            'amount.required' => 'Nominal bisyarah wajib diisi.',
            'amount.numeric' => 'Nominal bisyarah harus berupa angka.',
            'amount.min' => 'Nominal bisyarah harus lebih dari nol.',
            'payment_method.required' => 'Metode pembayaran bisyarah wajib dipilih.',
            'payment_method.in' => 'Metode pembayaran bisyarah tidak dikenali.',
            'bank_name.required_if' => 'Nama bank wajib diisi untuk pembayaran transfer.',
            'bank_account_number.required_if' => 'Nomor rekening wajib diisi untuk pembayaran transfer.',
        ], [
            'teacher_id' => 'guru',
            'period' => 'periode',
            'amount' => 'nominal bisyarah',
            'payment_method' => 'metode pembayaran',
            'bank_name' => 'nama bank',
            'bank_account_number' => 'nomor rekening',
            'bank_account_name' => 'nama pemilik rekening',
            'notes' => 'catatan',
        ]);
    }

    /**
     * Terima format YYYY-MM; bulan berjalan dipakai bila kosong/tidak valid.
     */
    private function resolvePeriod(?string $value): Carbon
    {
        if (is_string($value) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $value) === 1) {
            return Carbon::createFromFormat('Y-m', $value)->startOfMonth();
        }

        return now()->startOfMonth();
    }
}
