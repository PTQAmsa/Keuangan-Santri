<?php

namespace Tests\Feature;

use App\Enums\ExpenseCategory;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\PaymentCategory;
use App\Models\Student;
use App\Models\User;
use App\Services\CashFlowService;
use App\Services\ReceiptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashFlowReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Laporan menggabungkan kas masuk & keluar, lalu menghitung saldo berjalan.
     */
    public function test_cash_flow_combines_inflow_and_outflow_with_running_balance(): void
    {
        $admin = User::where('email', 'admin@ptqamsa.id')->firstOrFail();
        $student = Student::firstOrFail();
        $category = PaymentCategory::firstOrFail();

        $invoice = Invoice::create([
            'invoice_number' => 'INV-TEST-ARUS-KAS',
            'student_id' => $student->id,
            'payment_category_id' => $category->id,
            'title' => 'Tagihan Uji Arus Kas',
            'amount' => 250000,
            'paid_amount' => 0,
            'due_date' => now()->addWeek()->toDateString(),
            'status' => 'unpaid',
        ]);

        $payment = app(ReceiptService::class)->recordPayment($invoice, 250000, 'cash', null, $admin->id);

        $expense = Expense::create([
            'expense_code' => 'EXP-TEST-0001',
            'category' => ExpenseCategory::Operasional,
            'description' => 'Uji kas keluar arus kas',
            'amount' => 100000,
            'expense_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'recipient' => 'Petugas Kebersihan',
            'recorded_by' => $admin->id,
        ]);

        $flow = app(CashFlowService::class)->build(now()->startOfDay(), now()->endOfDay());

        $this->assertEqualsWithDelta(
            $flow['openingBalance'] + $flow['totalMasuk'] - $flow['totalKeluar'],
            $flow['closingBalance'],
            0.01,
        );

        $this->assertSame($flow['closingBalance'], $flow['entries']->last()['running_balance']);

        $codes = $flow['entries']->pluck('code');

        $this->assertTrue($codes->contains($payment->receipt->receipt_number));
        $this->assertTrue($codes->contains('EXP-TEST-0001'));

        $incomeEntry = $flow['entries']->firstWhere('code', $payment->receipt->receipt_number);
        $expenseEntry = $flow['entries']->firstWhere('code', 'EXP-TEST-0001');

        $this->assertSame('masuk', $incomeEntry['direction']);
        $this->assertSame(250000.0, $incomeEntry['amount']);
        $this->assertSame('keluar', $expenseEntry['direction']);
        $this->assertSame(100000.0, $expenseEntry['amount']);
        $this->assertLessThan($incomeEntry['running_balance'], $expenseEntry['running_balance']);
    }

    /**
     * Pengeluaran yang dibatalkan tidak boleh mengurangi saldo kas.
     */
    public function test_voided_expense_is_excluded_from_cash_flow(): void
    {
        $admin = User::where('email', 'admin@ptqamsa.id')->firstOrFail();

        $expense = Expense::create([
            'expense_code' => 'EXP-TEST-0002',
            'category' => ExpenseCategory::Konsumsi,
            'description' => 'Pengeluaran keliru input',
            'amount' => 90000,
            'expense_date' => now()->toDateString(),
            'payment_method' => 'transfer',
            'recorded_by' => $admin->id,
        ]);

        $before = app(CashFlowService::class)->build(now()->startOfDay(), now()->endOfDay());

        $expense->voided_at = now();
        $expense->voided_by = $admin->id;
        $expense->void_reason = 'Salah input';
        $expense->save();

        $after = app(CashFlowService::class)->build(now()->startOfDay(), now()->endOfDay());

        $this->assertFalse($after['entries']->pluck('code')->contains('EXP-TEST-0002'));
        $this->assertSame($before['totalKeluar'] - 90000, $after['totalKeluar']);
        $this->assertSame($before['closingBalance'] + 90000, $after['closingBalance']);
    }

    /**
     * Halaman laporan arus kas dapat dibuka pengurus dan tertutup untuk tamu.
     */
    public function test_cash_flow_page_is_available_for_staff_only(): void
    {
        $this->get(route('admin.reports.cash-flow'))->assertRedirect(route('admin.login'));

        $response = $this->actingAs(User::where('email', 'bendahara1@ptqamsa.id')->firstOrFail())
            ->get(route('admin.reports.cash-flow'));

        $response->assertOk();
        $response->assertSee('Laporan Arus Kas');
        $response->assertSee('Buku Kas Periode');
        $response->assertSee('EXP-202610-0001');
        $response->assertSee('Rp 1.250.000');
        $response->assertSee('sort_by=code', false);
    }

    public function test_cash_flow_page_can_sort_by_each_table_column(): void
    {
        $columns = [
            'date',
            'code',
            'description',
            'party',
            'method',
            'masuk',
            'keluar',
            'saldo',
        ];

        $bendahara = User::where('email', 'bendahara1@ptqamsa.id')->firstOrFail();

        foreach ($columns as $column) {
            $this->actingAs($bendahara)
                ->get(route('admin.reports.cash-flow', [
                    'sort_by' => $column,
                    'sort_order' => 'asc',
                ]))
                ->assertOk();
        }
    }
}
