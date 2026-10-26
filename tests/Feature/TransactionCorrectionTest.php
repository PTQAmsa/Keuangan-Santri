<?php

namespace Tests\Feature;

use App\Enums\ActivityAction;
use App\Enums\ActivityModule;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionCorrectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        ActivityLog::query()->delete();
    }

    private function userWithRole(UserRole $role): User
    {
        return User::where('role', $role->value)->firstOrFail();
    }

    /**
     * Test 1: Bendahara membatalkan kuitansi → status tagihan dihitung ulang & tercatat di log.
     */
    public function test_bendahara_can_void_payment_and_invoice_is_recalculated(): void
    {
        $bendahara = $this->userWithRole(UserRole::Bendahara);
        $payment = Payment::where('payment_code', 'PAY-202607-0003')->firstOrFail();
        $invoice = $payment->invoice;

        $this->assertSame('partial', $invoice->status);

        $this->actingAs($bendahara)
            ->patch(route('admin.transactions.void', $payment), [
                'void_reason' => 'Salah input nominal, seharusnya Rp 500.000',
            ])
            ->assertRedirect();

        $payment->refresh();
        $this->assertNotNull($payment->voided_at);
        $this->assertSame($bendahara->id, $payment->voided_by);
        $this->assertSame('Salah input nominal, seharusnya Rp 500.000', $payment->void_reason);

        $invoice->refresh();
        $this->assertEquals(0, (float) $invoice->paid_amount);
        $this->assertSame('unpaid', $invoice->status);

        $log = ActivityLog::sole();
        $this->assertSame($bendahara->id, $log->user_id);
        $this->assertSame(ActivityModule::Payment, $log->module);
        $this->assertSame(ActivityAction::Cancelled, $log->action);
        $this->assertSame('Dicicil → Belum Bayar', $log->properties['Status Tagihan']);
        $this->assertStringContainsString('Membatalkan kuitansi', $log->description);
    }

    /**
     * Test 2: Kuitansi yang dibatalkan tidak dihitung pada Laporan Kas.
     */
    public function test_voided_payment_is_excluded_from_cash_report(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        $payment = Payment::where('payment_code', 'PAY-202610-0001')->firstOrFail();
        $receiptNumber = $payment->receipt->receipt_number;

        $this->actingAs($admin)
            ->patch(route('admin.transactions.void', $payment), ['void_reason' => 'Dobel input'])
            ->assertRedirect();

        $report = $this->actingAs($admin)->get(route('admin.reports.cash', [
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]));

        $report->assertOk();
        // Kuitansi yang dibatalkan tidak lagi masuk rincian transaksi maupun total kas.
        $report->assertDontSee('Rp 1.350.000');
        $report->assertSee('Rp 3.700.000');
        $report->assertSee('3 <span class="text-xs font-normal text-gray-500">Kuitansi</span>', false);
    }

    /**
     * Test 3: Kuitansi yang sudah dibatalkan tidak dapat dibatalkan dua kali.
     */
    public function test_payment_cannot_be_voided_twice(): void
    {
        $bendahara = $this->userWithRole(UserRole::Bendahara);
        $payment = Payment::where('payment_code', 'PAY-202607-0001')->firstOrFail();

        $this->actingAs($bendahara)
            ->patch(route('admin.transactions.void', $payment), ['void_reason' => 'Koreksi'])
            ->assertRedirect();

        $this->actingAs($bendahara)
            ->patch(route('admin.transactions.void', $payment), ['void_reason' => 'Koreksi ulang'])
            ->assertRedirect()
            ->assertSessionHas('info');

        $this->assertSame(1, ActivityLog::where('action', ActivityAction::Cancelled)->count());
    }

    /**
     * Test 4: Perbaikan nominal pembayaran menghitung ulang tagihan & tercatat di log.
     */
    public function test_payment_amount_correction_recalculates_invoice_and_is_logged(): void
    {
        $bendahara = $this->userWithRole(UserRole::Bendahara);
        $payment = Payment::where('payment_code', 'PAY-202607-0003')->firstOrFail();

        $this->actingAs($bendahara)
            ->patch(route('admin.transactions.update', $payment), [
                'amount_paid' => 1000000,
                'payment_method' => 'cash',
                'payment_date' => '2026-07-20',
                'received_from' => 'Bpk. Bambang Sutrisno (Ayah Kandung)',
                'notes' => 'Koreksi cicilan',
            ])
            ->assertRedirect();

        $this->assertEquals(1000000, (float) $payment->fresh()->amount_paid);
        $this->assertEquals(1000000, (float) $payment->invoice->fresh()->paid_amount);
        $this->assertSame('partial', $payment->invoice->fresh()->status);

        $log = ActivityLog::sole();
        $this->assertSame(ActivityAction::Updated, $log->action);
        $this->assertArrayHasKey('Nominal Bayar', $log->properties);
        $this->assertSame('Rp 2.500.000 → Rp 1.000.000', $log->properties['Nominal Bayar']);
        $this->assertSame('Transfer → Tunai', $log->properties['Metode Bayar']);
    }

    /**
     * Test 5: Administrator dapat mengubah nominal tagihan dan statusnya menyesuaikan.
     */
    public function test_admin_can_edit_invoice_and_status_is_recalculated(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        $invoice = Invoice::where('invoice_number', 'INV-202607-0003')->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('admin.invoices.update', $invoice), [
                'title' => 'Wakaf Pembangunan Gedung (Koreksi)',
                'amount' => 4000000,
                'due_date' => '2026-12-31',
                'notes' => 'Nominal dikoreksi',
            ])
            ->assertRedirect();

        $invoice->refresh();
        $this->assertSame('Wakaf Pembangunan Gedung (Koreksi)', $invoice->title);
        $this->assertEquals(4000000, (float) $invoice->amount);
        $this->assertEquals(2500000, (float) $invoice->paid_amount);
        $this->assertSame('partial', $invoice->status);
        $this->assertEquals(1500000, $invoice->remaining_amount);

        $log = ActivityLog::sole();
        $this->assertSame(ActivityModule::Invoice, $log->module);
        $this->assertSame(ActivityAction::Updated, $log->action);
        $this->assertSame('Rp 5.000.000 → Rp 4.000.000', $log->properties['Nominal Tagihan']);
    }

    /**
     * Test 6: Nominal tagihan tidak boleh lebih kecil dari total yang sudah dibayar.
     */
    public function test_invoice_amount_cannot_be_lower_than_paid_amount(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        $invoice = Invoice::where('invoice_number', 'INV-202607-0003')->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('admin.invoices.update', $invoice), [
                'title' => $invoice->title,
                'amount' => 1000000,
                'due_date' => '2026-12-31',
            ])
            ->assertSessionHasErrors('amount');

        $this->assertEquals(5000000, (float) $invoice->fresh()->amount);
        $this->assertSame(0, ActivityLog::count());
    }

    /**
     * Test 7: Tagihan yang sudah dibayar tidak boleh dibatalkan sebelum kuitansinya dibatalkan.
     */
    public function test_invoice_with_active_payment_cannot_be_cancelled(): void
    {
        $bendahara = $this->userWithRole(UserRole::Bendahara);
        $invoice = Invoice::where('invoice_number', 'INV-202601-0001')->firstOrFail();

        $this->actingAs($bendahara)
            ->patch(route('admin.invoices.cancel', $invoice), ['cancel_reason' => 'Salah terbit'])
            ->assertSessionHasErrors('invoice');

        $this->assertSame('paid', $invoice->fresh()->status);

        // Setelah kuitansinya dibatalkan, tagihan boleh dibatalkan.
        $payment = $invoice->payments()->firstOrFail();

        $this->actingAs($bendahara)->patch(route('admin.transactions.void', $payment), ['void_reason' => 'Koreksi']);
        $this->actingAs($bendahara)->patch(route('admin.invoices.cancel', $invoice), ['cancel_reason' => 'Salah terbit']);

        $this->assertSame('cancelled', $invoice->fresh()->status);
        $this->assertSame(ActivityAction::Cancelled, ActivityLog::latest('id')->first()->action);
    }

    /**
     * Test 8: Tagihan tanpa pembayaran dapat dihapus permanen dan tercatat di log.
     */
    public function test_unpaid_invoice_can_be_deleted_and_is_logged(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        $invoice = Invoice::where('invoice_number', 'INV-202611-0001')->firstOrFail();

        $this->actingAs($admin)
            ->delete(route('admin.invoices.destroy', $invoice))
            ->assertRedirect();

        $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);

        $log = ActivityLog::sole();
        $this->assertSame(ActivityAction::Deleted, $log->action);
        $this->assertSame('INV-202611-0001', $log->properties['Nomor Tagihan']);
    }

    /**
     * Test 9: Tagihan yang memiliki riwayat pembayaran tidak dapat dihapus.
     */
    public function test_invoice_with_payment_history_cannot_be_deleted(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        $invoice = Invoice::where('invoice_number', 'INV-202601-0001')->firstOrFail();

        $this->actingAs($admin)
            ->delete(route('admin.invoices.destroy', $invoice))
            ->assertSessionHasErrors('invoice');

        $this->assertDatabaseHas('invoices', ['id' => $invoice->id]);
    }

    /**
     * Test 10: Pengasuh (read-only) tidak boleh mengakses koreksi/pembatalan transaksi.
     */
    public function test_pengasuh_cannot_access_transaction_correction(): void
    {
        $pengasuh = $this->userWithRole(UserRole::Pengasuh);
        $payment = Payment::firstOrFail();

        $this->actingAs($pengasuh)->get(route('admin.transactions'))->assertOk()->assertSee('Mode lihat saja');
        $this->actingAs($pengasuh)
            ->patch(route('admin.transactions.void', $payment), ['void_reason' => 'Coba batalkan'])
            ->assertForbidden();
        $this->actingAs($pengasuh)
            ->patch(route('admin.invoices.update', $payment->invoice), [
                'title' => 'Coba ubah',
                'amount' => 1000,
                'due_date' => '2026-12-31',
            ])
            ->assertForbidden();

        $this->assertNull($payment->fresh()->voided_at);
    }

    /**
     * Test 11: Kuitansi PDF yang dibatalkan memuat tanda air DIBATALKAN.
     */
    public function test_voided_receipt_pdf_contains_cancelled_watermark(): void
    {
        $bendahara = $this->userWithRole(UserRole::Bendahara);
        $payment = Payment::where('payment_code', 'PAY-202607-0001')->firstOrFail();

        $this->actingAs($bendahara)
            ->patch(route('admin.transactions.void', $payment), ['void_reason' => 'Koreksi administrasi'])
            ->assertRedirect();

        $payment->load(['invoice', 'student', 'receipt']);
        $receipt = $payment->receipt;

        $html = view('pdf.receipt', [
            'receipt' => $receipt,
            'payment' => $payment->fresh(['invoice', 'student']),
            'invoice' => $payment->invoice,
            'student' => $payment->student,
            'terbilang' => 'Dua Ratus Ribu Rupiah',
        ])->render();

        $this->assertStringContainsString('DIBATALKAN', $html);
        $this->assertStringContainsString('Koreksi administrasi', $html);
    }

    /**
     * Test 12: Bendahara dapat membuka halaman Koreksi Transaksi.
     */
    public function test_bendahara_can_open_transaction_correction_page(): void
    {
        $response = $this->actingAs($this->userWithRole(UserRole::Bendahara))
            ->get(route('admin.transactions'));

        $response->assertOk();
        $response->assertSee('Koreksi &amp; Pembatalan Transaksi', false);
        $response->assertSee('KWT-202607-0001');
        $response->assertSee('sort_by=receipt_number', false);
    }

    public function test_transaction_page_can_sort_by_each_table_column(): void
    {
        $columns = [
            'receipt_number',
            'payment_date',
            'student_name',
            'kelompok',
            'invoice_title',
            'amount_paid',
            'status',
        ];

        foreach ($columns as $column) {
            $this->actingAs($this->userWithRole(UserRole::Bendahara))
                ->get(route('admin.transactions', [
                    'sort_by' => $column,
                    'sort_order' => 'asc',
                ]))
                ->assertOk();
        }
    }

    public function test_transaction_page_sorts_by_nominal_ascending(): void
    {
        $expected = Payment::with('receipt')
            ->whereNull('voided_at')
            ->orderBy('amount_paid')
            ->orderByDesc('id')
            ->limit(2)
            ->get()
            ->map(fn (Payment $payment) => $payment->receipt?->receipt_number ?? $payment->payment_code)
            ->all();

        $this->actingAs($this->userWithRole(UserRole::Bendahara))
            ->get(route('admin.transactions', [
                'sort_by' => 'amount_paid',
                'sort_order' => 'asc',
            ]))
            ->assertOk()
            ->assertSeeInOrder($expected);
    }
}
