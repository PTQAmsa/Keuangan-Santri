<?php

namespace App\Http\Controllers\Admin;

use App\Models\Invoice;
use App\Models\Payment;
use App\Services\ActivityLogService;
use App\Services\BookClosingService;
use App\Services\ReceiptService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Perbaikan & pembatalan transaksi keuangan.
 *
 * Hanya Administrator dan Bendahara yang boleh mengakses (lihat routes/web.php).
 * Semua aksi tercatat pada Log Aktivitas.
 */
class TransactionController
{
    /**
     * Halaman daftar transaksi (kuitansi) yang dapat dibatalkan / diperbaiki.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status', 'active');
        $kelompok = $request->input('kelompok');

        $allowedSorts = [
            'receipt_number',
            'payment_date',
            'student_name',
            'kelompok',
            'invoice_title',
            'amount_paid',
            'status',
        ];

        $sortBy = (string) $request->input('sort_by', 'payment_date');
        $sortOrder = strtolower((string) $request->input('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'payment_date';
        }

        $query = Payment::with(['student', 'invoice.category', 'receipt', 'recordedBy', 'voidedBy']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('payment_code', 'like', "%{$search}%")
                    ->orWhere('received_from', 'like', "%{$search}%")
                    ->orWhereHas('receipt', fn ($sub) => $sub->where('receipt_number', 'like', "%{$search}%"))
                    ->orWhereHas('student', function ($sub) use ($search) {
                        $sub->where('nama_lengkap', 'like', "%{$search}%")
                            ->orWhere('nis', 'like', "%{$search}%");
                    });
            });
        }

        if ($status === 'voided') {
            $query->whereNotNull('voided_at');
        } elseif ($status === 'active') {
            $query->whereNull('voided_at');
        }

        if ($kelompok === 'putra' || $kelompok === 'putri') {
            $query->whereHas('student', fn ($sub) => $sub->where('santri_group', $kelompok));
        }

        // Kolom relasi perlu join agar bisa diurutkan dari server.
        if ($sortBy === 'receipt_number') {
            $query->leftJoin('receipts', 'receipts.payment_id', '=', 'payments.id')
                ->orderByRaw('COALESCE(receipts.receipt_number, payments.payment_code) '.$sortOrder)
                ->select('payments.*');
        } elseif ($sortBy === 'student_name') {
            $query->leftJoin('students', 'payments.student_id', '=', 'students.id')
                ->orderBy('students.nama_lengkap', $sortOrder)
                ->select('payments.*');
        } elseif ($sortBy === 'kelompok') {
            $query->leftJoin('students', 'payments.student_id', '=', 'students.id')
                ->orderBy('students.santri_group', $sortOrder)
                ->select('payments.*');
        } elseif ($sortBy === 'invoice_title') {
            $query->leftJoin('invoices', 'payments.invoice_id', '=', 'invoices.id')
                ->orderBy('invoices.title', $sortOrder)
                ->select('payments.*');
        } elseif ($sortBy === 'status') {
            $query->orderBy('payments.voided_at', $sortOrder);
        } else {
            $query->orderBy('payments.'.$sortBy, $sortOrder);
        }

        // Urutan sekunder agar baris dengan nilai sama tetap konsisten.
        $query->orderBy('payments.id', 'desc');

        $payments = $query->paginate(20)->withQueryString();

        $jumlahAktif = Payment::whereNull('voided_at')->count();
        $jumlahDibatalkan = Payment::whereNotNull('voided_at')->count();

        $putraAktif = Payment::whereNull('voided_at')
            ->whereHas('student', fn ($q) => $q->where('santri_group', 'putra'))
            ->count();
        $putriAktif = Payment::whereNull('voided_at')
            ->whereHas('student', fn ($q) => $q->where('santri_group', 'putri'))
            ->count();
        $putraBatal = Payment::whereNotNull('voided_at')
            ->whereHas('student', fn ($q) => $q->where('santri_group', 'putra'))
            ->count();
        $putriBatal = Payment::whereNotNull('voided_at')
            ->whereHas('student', fn ($q) => $q->where('santri_group', 'putri'))
            ->count();

        return view('admin.transactions', compact(
            'payments',
            'search',
            'status',
            'kelompok',
            'jumlahAktif',
            'jumlahDibatalkan',
            'putraAktif',
            'putriAktif',
            'putraBatal',
            'putriBatal',
            'sortBy',
            'sortOrder'
        ));
    }

    /**
     * Batalkan kuitansi/pembayaran.
     */
    public function voidPayment(Request $request, Payment $payment, ReceiptService $receiptService, BookClosingService $bookClosing)
    {
        $data = $request->validate([
            'void_reason' => ['required', 'string', 'max:255'],
        ], [], [
            'void_reason' => 'alasan pembatalan',
        ]);

        if ($payment->isVoided()) {
            return back()->with('info', 'Kuitansi ini sudah dibatalkan sebelumnya.');
        }

        $bookClosing->assertOpen($payment->payment_date, 'pembatalan kuitansi');

        $receiptService->voidPayment($payment, $data['void_reason'], auth()->id());

        return back()->with('success', 'Kuitansi '.($payment->receipt->receipt_number ?? $payment->payment_code).' dibatalkan. Status tagihan telah dihitung ulang.');
    }

    /**
     * Perbaiki data pembayaran yang salah input.
     */
    public function updatePayment(Request $request, Payment $payment, ReceiptService $receiptService, BookClosingService $bookClosing)
    {
        if ($payment->isVoided()) {
            return back()->withErrors(['payment' => 'Kuitansi yang sudah dibatalkan tidak dapat diubah.']);
        }

        $bookClosing->assertOpen($payment->payment_date, 'perbaikan pembayaran');

        $data = $request->validate([
            'amount_paid' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', 'in:cash,transfer'],
            'payment_date' => ['required', 'date', 'before_or_equal:today'],
            'received_from' => ['required', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:255'],
        ], [], [
            'amount_paid' => 'nominal bayar',
            'payment_method' => 'metode bayar',
            'payment_date' => 'tanggal bayar',
            'received_from' => 'diterima dari',
        ]);

        // Tanggal tujuan juga tidak boleh masuk periode yang sudah ditutup.
        $bookClosing->assertOpen($data['payment_date'], 'perbaikan pembayaran');

        // Total pembayaran aktif tidak boleh melebihi nilai tagihan.
        $otherActive = (float) $payment->invoice->payments()
            ->whereNull('voided_at')
            ->whereKeyNot($payment->id)
            ->sum('amount_paid');

        if ($otherActive + (float) $data['amount_paid'] > (float) $payment->invoice->amount) {
            return back()->withErrors([
                'amount_paid' => 'Total pembayaran melebihi nilai tagihan (maksimal Rp '.number_format((float) $payment->invoice->amount - $otherActive, 0, ',', '.').').',
            ])->withInput();
        }

        $receiptService->updatePayment($payment, $data, auth()->id());

        return back()->with('success', 'Data pembayaran '.($payment->receipt->receipt_number ?? $payment->payment_code).' berhasil diperbaiki.');
    }

    /**
     * Ubah data tagihan (judul, nominal, jatuh tempo, catatan).
     */
    public function updateInvoice(Request $request, Invoice $invoice)
    {
        if ($invoice->isCancelled()) {
            return back()->withErrors(['invoice' => 'Tagihan yang sudah dibatalkan tidak dapat diubah.']);
        }

        if ($invoice->remainingEdits() <= 0) {
            return back()->withErrors([
                'invoice' => 'Tagihan '.$invoice->invoice_number.' sudah diubah '.Invoice::MAX_EDITS.' kali, jadi data tagihan tidak dapat diubah lagi.',
            ]);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'amount' => ['required', 'numeric', 'min:1000'],
            'due_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:255'],
        ], [], [
            'title' => 'uraian tagihan',
            'amount' => 'nominal tagihan',
            'due_date' => 'jatuh tempo',
        ]);

        if ((float) $data['amount'] < (float) $invoice->paid_amount) {
            return back()->withErrors([
                'amount' => 'Nominal tagihan tidak boleh lebih kecil dari total yang sudah dibayar (Rp '.number_format((float) $invoice->paid_amount, 0, ',', '.').').',
            ])->withInput();
        }

        $changes = [];

        if ($invoice->title !== $data['title']) {
            $changes['Uraian Tagihan'] = [$invoice->title, $data['title']];
        }

        if ((float) $invoice->amount !== (float) $data['amount']) {
            $changes['Nominal Tagihan'] = [
                'Rp '.number_format((float) $invoice->amount, 0, ',', '.'),
                'Rp '.number_format((float) $data['amount'], 0, ',', '.'),
            ];
        }

        $oldDueDate = $invoice->due_date?->format('d/m/Y') ?? '-';
        $newDueDate = Carbon::parse($data['due_date'])->format('d/m/Y');

        if ($oldDueDate !== $newDueDate) {
            $changes['Jatuh Tempo'] = [$oldDueDate, $newDueDate];
        }

        if ((string) $invoice->notes !== (string) ($data['notes'] ?? '')) {
            $changes['Catatan'] = [$invoice->notes ?: '-', $data['notes'] ?: '-'];
        }

        if ($changes === []) {
            return back()->with('info', 'Tidak ada perubahan pada tagihan ini.');
        }

        $invoice->fill($data);
        $invoice->edit_count = (int) $invoice->edit_count + 1;
        $invoice->save();
        $invoice->recalculateStatus();

        ActivityLogService::invoiceUpdated($invoice, $changes, auth()->id());

        $sisa = $invoice->remainingEdits();
        $pesanSisa = $sisa > 0
            ? ' Sisa kuota perubahan: '.$sisa.' kali.'
            : ' Kuota perubahan tagihan ini sudah habis.';

        return back()->with('success', 'Tagihan '.$invoice->invoice_number.' berhasil diperbarui.'.$pesanSisa);
    }

    /**
     * Batalkan tagihan (mis. tagihan salah terbit / santri batal mondok).
     */
    public function cancelInvoice(Request $request, Invoice $invoice)
    {
        $data = $request->validate([
            'cancel_reason' => ['required', 'string', 'max:255'],
        ], [], [
            'cancel_reason' => 'alasan pembatalan',
        ]);

        if ($invoice->isCancelled()) {
            return back()->with('info', 'Tagihan ini sudah dibatalkan sebelumnya.');
        }

        if ($invoice->hasActivePayments()) {
            return back()->withErrors([
                'invoice' => 'Tagihan ini sudah memiliki pembayaran aktif. Batalkan dulu kuitansinya sebelum membatalkan tagihan.',
            ]);
        }

        $invoice->status = 'cancelled';
        $invoice->save();

        ActivityLogService::invoiceCancelled($invoice, $data['cancel_reason'], auth()->id());

        return back()->with('success', 'Tagihan '.$invoice->invoice_number.' dibatalkan.');
    }

    /**
     * Hapus tagihan yang belum pernah dibayar sama sekali.
     */
    public function destroyInvoice(Invoice $invoice)
    {
        if ($invoice->payments()->exists()) {
            return back()->withErrors([
                'invoice' => 'Tagihan ini memiliki riwayat pembayaran, jadi tidak dapat dihapus. Gunakan pembatalan tagihan.',
            ]);
        }

        ActivityLogService::invoiceDeleted($invoice, auth()->id());

        $invoice->delete();

        return back()->with('success', 'Tagihan berhasil dihapus.');
    }
}
