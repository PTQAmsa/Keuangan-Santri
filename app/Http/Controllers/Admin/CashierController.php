<?php

namespace App\Http\Controllers\Admin;

use App\Models\Invoice;
use App\Models\OtherIncome;
use App\Models\Payment;
use App\Models\PaymentCategory;
use App\Models\Student;
use App\Services\ActivityLogService;
use App\Services\BookClosingService;
use App\Services\InvoiceNumberService;
use App\Services\ReceiptService;
use App\Services\StudentFinancialCardService;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CashierController
{
    /**
     * Dashboard Ringkasan Keuangan Pesantren
     */
    public function index(StudentFinancialCardService $cardService)
    {
        $totalSantri = Student::where('status', 'aktif')->count();
        $totalPutra = Student::where('status', 'aktif')->where('santri_group', 'putra')->count();
        $totalPutri = Student::where('status', 'aktif')->where('santri_group', 'putri')->count();

        // Pemasukan bulan berjalan, dipisah keseluruhan / putra / putri (kas tetap satu atap).
        $masukBulanIni = fn () => Payment::query()
            ->whereNull('voided_at')
            ->whereYear('payment_date', now()->year)
            ->whereMonth('payment_date', now()->month);

        $masukSantri = (float) $masukBulanIni()->sum('amount_paid');
        $masukPutra = $masukBulanIni()
            ->whereHas('student', fn ($q) => $q->where('santri_group', 'putra'))
            ->sum('amount_paid');
        $masukPutri = $masukBulanIni()
            ->whereHas('student', fn ($q) => $q->where('santri_group', 'putri'))
            ->sum('amount_paid');

        // Pemasukan lain (donasi, infaq, unit usaha, dsb.) ikut dihitung agar
        // angka dashboard sama dengan laporan arus kas.
        $masukLainnya = (float) OtherIncome::query()
            ->whereNull('voided_at')
            ->whereYear('income_date', now()->year)
            ->whereMonth('income_date', now()->month)
            ->sum('amount');

        $totalMasukBulanIni = $masukSantri + $masukLainnya;

        // Tunggakan aktif sampai bulan berjalan, dipisah keseluruhan / putra / putri.
        // Tagihan bulan-bulan mendatang tidak dihitung agar angka card tetap
        // mencerminkan kewajiban yang sudah jatuh tempo pada bulan ini.
        $invoicesAktif = $cardService->limitArrearsToCurrentMonth(
            Invoice::with('student')
                ->whereIn('status', ['unpaid', 'partial'])
                ->get(),
            now()
        );

        $totalTunggakan = $invoicesAktif->sum(fn ($inv) => $inv->remaining_amount);
        $tunggakanPutra = $invoicesAktif
            ->filter(fn ($inv) => $inv->student?->santri_group === 'putra')
            ->sum(fn ($inv) => $inv->remaining_amount);
        $tunggakanPutri = $invoicesAktif
            ->filter(fn ($inv) => $inv->student?->santri_group === 'putri')
            ->sum(fn ($inv) => $inv->remaining_amount);

        $recentPayments = Payment::with(['student', 'invoice', 'receipt'])
            ->whereNull('voided_at')
            ->latest('payment_date')
            ->latest('id')
            ->take(8)
            ->get();
        $recentInvoices = Invoice::with(['student', 'category'])->whereIn('status', ['unpaid', 'partial'])->latest()->take(6)->get();

        return view('admin.dashboard', compact(
            'totalSantri',
            'totalPutra',
            'totalPutri',
            'totalMasukBulanIni',
            'masukPutra',
            'masukPutri',
            'masukLainnya',
            'totalTunggakan',
            'tunggakanPutra',
            'tunggakanPutri',
            'recentPayments',
            'recentInvoices'
        ));
    }

    /**
     * Halaman Kasir Pembayaran
     */
    public function createPayment(Request $request)
    {
        $students = Student::where('status', 'aktif')->orderBy('nama_lengkap')->get();

        // Daftar kelas dipisah per marhalah agar dropdown bertingkat tetap relevan.
        $classesByKelompok = $students
            ->groupBy(fn (Student $student) => $student->santri_group === 'putri' ? 'putri' : 'putra')
            ->map(fn ($group) => $group->pluck('kelas')->filter()->unique()->sort()->values())
            ->toArray();

        $selectedStudent = null;
        $unpaidInvoices = collect();

        if ($request->filled('student_id')) {
            $selectedStudent = Student::where('status', 'aktif')->find($request->student_id);
            if ($selectedStudent) {
                $unpaidInvoices = $selectedStudent->invoices()->whereIn('status', ['unpaid', 'partial'])->get();
            }
        }

        return view('admin.cashier', compact(
            'students',
            'classesByKelompok',
            'selectedStudent',
            'unpaidInvoices'
        ));
    }

    /**
     * Simpan Pembayaran Kasir
     */
    public function storePayment(Request $request, ReceiptService $receiptService, WhatsAppService $waService, BookClosingService $bookClosing)
    {
        $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'amount_paid' => 'required|numeric|min:1000',
            'payment_method' => 'required|in:cash,transfer',
            'received_from' => 'required|string|max:150',
            'notes' => 'nullable|string|max:255',
        ]);

        // Pembayaran selalu bertanggal hari ini; tolak bila bulan ini sudah ditutup.
        $bookClosing->assertOpen(now(), 'pembayaran');

        $invoice = Invoice::findOrFail($request->invoice_id);

        if ($request->amount_paid > $invoice->remaining_amount) {
            return back()->withErrors(['amount_paid' => 'Nominal bayar melebihi sisa tagihan (Rp '.number_format($invoice->remaining_amount, 0, ',', '.').').'])->withInput();
        }

        $payment = $receiptService->recordPayment(
            invoice: $invoice,
            amountPaid: (float) $request->amount_paid,
            method: $request->payment_method,
            notes: $request->notes,
            adminId: auth()->id(),
            receivedFrom: $request->received_from,
        );

        $waMessage = $waService->formatPaymentSuccessMessage($payment);
        $encodedWa = urlencode($waMessage);
        $cleanPhone = preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $payment->student->no_whatsapp_wali));
        $waDirectUrl = "https://wa.me/{$cleanPhone}?text={$encodedWa}";

        // Bila gateway sudah diaktifkan, kuitansi juga masuk outbox otomatis.
        // Kegagalan notifikasi tidak boleh membatalkan pencatatan pembayaran.
        if (config('services.whatsapp.enabled')) {
            try {
                $waService->queuePaymentReceipt($payment, auth()->id());
            } catch (\Throwable $e) {
                Log::warning('Gagal mengantrekan WhatsApp kuitansi: '.$e->getMessage());
            }
        }

        return redirect()->route('admin.cashier', ['student_id' => $invoice->student_id])
            ->with('success', 'Pembayaran Rp '.number_format($payment->amount_paid, 0, ',', '.')." berhasil dicatat! Kuitansi: {$payment->receipt->receipt_number}")
            ->with('wa_direct_url', $waDirectUrl)
            ->with('receipt_id', $payment->receipt->id);
    }

    /**
     * Batch Generate Tagihan SPP Bulanan untuk Semua Santri Aktif
     */
    public function generateMonthlySPP(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2025|max:2030',
        ]);

        $sppCategory = PaymentCategory::where('code', 'SPP')->firstOrFail();
        $activeStudents = Student::where('status', 'aktif')->get();

        $monthName = Carbon::createFromDate($request->year, $request->month, 1)->translatedFormat('F');
        $generatedCount = 0;
        $generatedTotal = 0.0;

        foreach ($activeStudents as $student) {
            // Cek apakah santri sudah memiliki tagihan untuk bulan & tahun ini
            $exists = Invoice::where('student_id', $student->id)
                ->where('payment_category_id', $sppCategory->id)
                ->where('period_month', $request->month)
                ->where('period_year', $request->year)
                ->exists();

            if (! $exists) {
                // Hitung potongan beasiswa jika ada
                $baseAmount = (float) $sppCategory->default_amount;
                $finalAmount = $baseAmount;
                $notes = null;

                if ($student->diskon_beasiswa > 0) {
                    $discountAmount = ($baseAmount * $student->diskon_beasiswa) / 100;
                    $finalAmount = $baseAmount - $discountAmount;
                    $notes = "Beasiswa/Keringanan {$student->diskon_beasiswa}% (Potongan Rp ".number_format($discountAmount, 0, ',', '.').')';
                }

                $invoiceNum = 'INV-'.$request->year.str_pad($request->month, 2, '0', STR_PAD_LEFT).'-'.str_pad($student->id, 4, '0', STR_PAD_LEFT);

                Invoice::create([
                    'invoice_number' => $invoiceNum,
                    'student_id' => $student->id,
                    'payment_category_id' => $sppCategory->id,
                    'title' => "SPP, Makan & Laundry - {$monthName} {$request->year}",
                    'amount' => $finalAmount,
                    'paid_amount' => 0,
                    'due_date' => "{$request->year}-".str_pad($request->month, 2, '0', STR_PAD_LEFT).'-10',
                    'status' => 'unpaid',
                    'period_month' => $request->month,
                    'period_year' => $request->year,
                    'notes' => $notes,
                ]);

                $generatedCount++;
                $generatedTotal += $finalAmount;
            }
        }

        // Satu baris log untuk satu aksi massal penerbitan SPP.
        if ($generatedCount > 0) {
            ActivityLogService::monthlySppGenerated(
                categoryName: $sppCategory->name,
                periodLabel: "{$monthName} {$request->year}",
                invoiceCount: $generatedCount,
                totalAmount: $generatedTotal,
            );
        }

        return back()->with('success', "Berhasil menerbitkan {$generatedCount} tagihan SPP untuk periode {$monthName} {$request->year}!");
    }

    /**
     * Simpan Pembuatan Tagihan Baru (Uang Kitab, Iuran Kondisional / Rihlah / Wisuda, dll)
     */
    public function storeInvoice(Request $request, InvoiceNumberService $invoiceNumbers)
    {
        $request->validate([
            'payment_category_id' => 'required|exists:payment_categories,id',
            'target_type' => 'required|in:single,class,all,putra,putri',
            'student_id' => 'required_if:target_type,single|nullable|exists:students,id',
            'target_class' => 'required_if:target_type,class|nullable|string',
            'title' => 'required|string|max:150',
            'amount' => 'required|numeric|min:1000',
            'due_date' => 'required|date',
            'notes' => 'nullable|string|max:255',
        ]);

        $category = PaymentCategory::findOrFail($request->payment_category_id);
        $amount = (float) $request->amount;
        $dueDate = $request->due_date;
        $title = $request->title;
        $notes = $request->notes;

        // Ambil santri target
        if ($request->target_type === 'single') {
            $targetStudents = Student::where('id', $request->student_id)->where('status', 'aktif')->get();
        } elseif ($request->target_type === 'class') {
            $targetStudents = Student::where('kelas', $request->target_class)->where('status', 'aktif')->get();
        } elseif (in_array($request->target_type, ['putra', 'putri'], true)) {
            $targetStudents = Student::where('santri_group', $request->target_type)->where('status', 'aktif')->get();
        } else {
            $targetStudents = Student::where('status', 'aktif')->get();
        }

        if ($targetStudents->isEmpty()) {
            return back()->withErrors(['target_type' => 'Tidak ditemukan santri aktif untuk kriteria target yang dipilih.'])->withInput();
        }

        $prefix = strtoupper($category->code ?? 'TAG');
        $attempts = 0;

        do {
            try {
                // Nomor dihitung dari data terbaru, lalu seluruh tagihan disimpan
                // dalam satu transaksi agar tidak ada batch yang setengah jadi.
                $numbers = $invoiceNumbers->generate($prefix, $targetStudents->count());

                DB::transaction(function () use ($targetStudents, $numbers, $category, $title, $amount, $dueDate, $notes): void {
                    foreach ($targetStudents->values() as $index => $student) {
                        Invoice::create([
                            'invoice_number' => $numbers[$index],
                            'student_id' => $student->id,
                            'payment_category_id' => $category->id,
                            'title' => $title,
                            'amount' => $amount,
                            'paid_amount' => 0,
                            'due_date' => $dueDate,
                            'status' => 'unpaid',
                            'notes' => $notes,
                        ]);
                    }
                });

                break;
            } catch (UniqueConstraintViolationException $exception) {
                // Nomor sempat dipakai request lain; hitung ulang urutan lalu coba lagi.
                if (++$attempts >= 3) {
                    throw $exception;
                }
            }
        } while (true);

        $createdCount = $targetStudents->count();

        $targetLabel = match ($request->target_type) {
            'single' => 'santri '.$targetStudents->first()->nama_lengkap.' (NIS '.$targetStudents->first()->nis.')',
            'class' => 'Kelas '.$request->target_class.' ('.$targetStudents->count().' santri)',
            'putra' => 'Marhalah Putra ('.$targetStudents->count().' santri)',
            'putri' => 'Marhalah Putri ('.$targetStudents->count().' santri)',
            default => 'Semua santri aktif ('.$targetStudents->count().' santri)',
        };

        // Satu baris log untuk satu aksi penerbitan tagihan (termasuk aksi massal).
        ActivityLogService::invoiceCreated(
            categoryName: $category->name,
            title: $title,
            amount: $amount,
            dueDate: $dueDate,
            targetLabel: $targetLabel,
            students: $targetStudents,
        );

        return redirect()->route('admin.invoices')
            ->with('success', "Berhasil menerbitkan {$createdCount} tagihan \"{$title}\" sebesar Rp ".number_format($amount, 0, ',', '.').' per santri! Data langsung aktif di sistem dan masuk ke Portal Wali Santri.');
    }

    /**
     * Daftar Semua Tagihan (Invoices) dengan Fitur Sorting Kolom & Filter
     */
    public function invoices(Request $request)
    {
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        // Pastikan kolom dan arah sort valid
        $allowedSorts = [
            'created_at' => 'created_at',
            'invoice_number' => 'invoice_number',
            'title' => 'title',
            'amount' => 'amount',
            'remaining_amount' => 'amount', // di-sort berdasar amount
            'due_date' => 'due_date',
            'status' => 'status',
            'student_name' => 'student_name',
        ];

        if (! array_key_exists($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }
        $sortOrder = strtolower($sortOrder) === 'asc' ? 'asc' : 'desc';

        $query = Invoice::with(['student', 'category']);

        // Filter status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter kategori
        if ($request->filled('category_id')) {
            $query->where('payment_category_id', $request->category_id);
        }

        // Filter kelompok (putra/putri)
        if ($request->filled('kelompok')) {
            $query->whereHas('student', function ($sub) use ($request) {
                $sub->where('santri_group', $request->kelompok);
            });
        }

        // Filter kelas — untuk memetakan siapa yang sudah/belum bayar per kelas
        if ($request->filled('kelas')) {
            $query->whereHas('student', function ($sub) use ($request) {
                $sub->where('kelas', $request->kelas);
            });
        }

        // Search nama atau nomor tagihan
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereLike('invoice_number', "%{$search}%")
                    ->orWhereLike('title', "%{$search}%")
                    ->orWhereHas('student', function ($sub) use ($search) {
                        $sub->whereLike('nama_lengkap', "%{$search}%")
                            ->orWhereLike('nis', "%{$search}%");
                    });
            });
        }

        // Sorting Logic
        if ($sortBy === 'student_name') {
            $query->join('students', 'invoices.student_id', '=', 'students.id')
                ->orderBy('students.nama_lengkap', $sortOrder)
                ->select('invoices.*');
        } else {
            $query->orderBy($allowedSorts[$sortBy], $sortOrder);
        }

        $invoices = $query->paginate(15)->appends($request->query());
        $categories = PaymentCategory::where('is_active', true)->orderBy('name')->get();
        $classes = Student::where('status', 'aktif')->select('kelas')->distinct()->pluck('kelas')->filter();
        $students = Student::where('status', 'aktif')->orderBy('nama_lengkap')->get();

        return view('admin.invoices', compact(
            'invoices',
            'categories',
            'classes',
            'students',
            'sortBy',
            'sortOrder'
        ));
    }
}
