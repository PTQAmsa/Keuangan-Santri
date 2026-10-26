<?php

namespace Tests\Feature;

use App\Enums\ActivityAction;
use App\Enums\ActivityModule;
use App\Enums\ExpenseCategory;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\BookClosing;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentCategory;
use App\Models\Student;
use App\Models\User;
use App\Services\BookClosingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BookClosingTest extends TestCase
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

    private function makeExpense(string $date, float $amount, string $description): Expense
    {
        return Expense::create([
            'expense_code' => 'EXP-T-'.Str::upper(Str::random(8)),
            'category' => ExpenseCategory::Operasional,
            'description' => $description,
            'amount' => $amount,
            'expense_date' => $date,
            'payment_method' => 'cash',
            'recorded_by' => $this->userWithRole(UserRole::Bendahara)->id,
        ]);
    }

    private function makePayment(string $date, float $amount): Payment
    {
        $student = Student::firstOrFail();
        $category = PaymentCategory::firstOrFail();

        $invoice = Invoice::create([
            'invoice_number' => 'INV-T-'.Str::upper(Str::random(8)),
            'student_id' => $student->id,
            'payment_category_id' => $category->id,
            'title' => 'Tagihan uji tutup buku',
            'amount' => $amount,
            'paid_amount' => $amount,
            'due_date' => $date,
            'status' => 'paid',
            'period_month' => (int) date('n', strtotime($date)),
            'period_year' => (int) date('Y', strtotime($date)),
        ]);

        return Payment::create([
            'payment_code' => 'PAY-T-'.Str::upper(Str::random(8)),
            'invoice_id' => $invoice->id,
            'student_id' => $student->id,
            'amount_paid' => $amount,
            'payment_date' => $date,
            'payment_method' => 'transfer',
            'received_from' => $student->nama_wali,
            'recorded_by' => $this->userWithRole(UserRole::Bendahara)->id,
        ]);
    }

    public function test_bendahara_closes_month_and_snapshot_matches_report(): void
    {
        $bendahara = $this->userWithRole(UserRole::Bendahara);
        $period = now()->subMonthNoOverflow();

        $this->makeExpense($period->copy()->day(5)->toDateString(), 250000, 'Operasional uji tutup buku');
        $this->makePayment($period->copy()->day(10)->toDateString(), 500000);

        $expected = app(BookClosingService::class)->summary((int) $period->year, (int) $period->month);

        $response = $this->actingAs($bendahara)->post(route('admin.reports.closing.store'), [
            'period_year' => $period->year,
            'period_month' => $period->month,
            'confirm_close' => 1,
        ]);

        $response->assertRedirect(route('admin.reports.closing', ['year' => $period->year]));

        $closing = BookClosing::forPeriod((int) $period->year, (int) $period->month);
        $this->assertNotNull($closing);
        $this->assertNotNull($closing->closed_at);
        $this->assertSame($bendahara->id, $closing->closed_by);
        $this->assertEqualsWithDelta($expected['total_in'], (float) $closing->total_in, 0.01);
        $this->assertEqualsWithDelta($expected['total_out'], (float) $closing->total_out, 0.01);
        $this->assertEqualsWithDelta($expected['closing_balance'], (float) $closing->closing_balance, 0.01);
        $this->assertSame($expected['payment_count'], $closing->payment_count);

        $this->actingAs($bendahara)->get(route('admin.reports.closing', ['year' => $period->year]))
            ->assertOk()
            ->assertSee('Tutup Buku')
            ->assertSee('Ditutup')
            ->assertSee('Khusus Administrator')
            ->assertSee('Rp '.number_format((float) $closing->closing_balance, 0, ',', '.'));

        $log = ActivityLog::where('module', ActivityModule::BookClosing->value)->sole();
        $this->assertSame(ActivityAction::Closed, $log->action);
        $this->assertStringContainsString($closing->periodLabel(), $log->description);
    }

    public function test_closed_month_rejects_new_expense_and_void(): void
    {
        $bendahara = $this->userWithRole(UserRole::Bendahara);
        $period = now()->subMonthNoOverflow();

        app(BookClosingService::class)->close((int) $period->year, (int) $period->month, $bendahara->id);

        $date = $period->copy()->day(12)->toDateString();
        $existing = $this->makeExpense($date, 100000, 'Pengeluaran lama sebelum tutup buku');

        $this->actingAs($bendahara)->post(route('admin.expenses.store'), [
            'category' => ExpenseCategory::Operasional->value,
            'description' => 'Pengeluaran setelah tutup buku',
            'amount' => 150000,
            'expense_date' => $date,
            'payment_method' => 'cash',
        ])->assertSessionHasErrors('period');

        $this->assertDatabaseMissing('expenses', ['description' => 'Pengeluaran setelah tutup buku']);

        $this->actingAs($bendahara)->patch(route('admin.expenses.void', $existing), [
            'void_reason' => 'Mau dibatalkan setelah tutup buku',
        ])->assertSessionHasErrors('period');

        $this->assertNull($existing->fresh()->voided_at);
    }

    public function test_closed_month_rejects_payment_correction_void_and_cashier_entry(): void
    {
        $bendahara = $this->userWithRole(UserRole::Bendahara);
        $period = now()->subMonthNoOverflow();
        $date = $period->copy()->day(15)->toDateString();

        $payment = $this->makePayment($date, 400000);

        app(BookClosingService::class)->close((int) $period->year, (int) $period->month, $bendahara->id);

        $this->actingAs($bendahara)->patch(route('admin.transactions.update', $payment), [
            'amount_paid' => 300000,
            'payment_method' => 'cash',
            'payment_date' => $date,
            'received_from' => $payment->received_from,
        ])->assertSessionHasErrors('period');

        $this->assertSame('400000.00', $payment->fresh()->amount_paid);

        $this->actingAs($bendahara)->patch(route('admin.transactions.void', $payment), [
            'void_reason' => 'Salah input setelah tutup buku',
        ])->assertSessionHasErrors('period');

        $this->assertNull($payment->fresh()->voided_at);
    }

    public function test_cashier_is_blocked_when_current_month_is_closed(): void
    {
        $bendahara = $this->userWithRole(UserRole::Bendahara);
        $invoice = Invoice::whereIn('status', ['unpaid', 'partial'])->firstOrFail();

        app(BookClosingService::class)->close((int) now()->year, (int) now()->month, $bendahara->id);

        $paymentsBefore = Payment::count();

        $this->actingAs($bendahara)->post(route('admin.cashier.store'), [
            'invoice_id' => $invoice->id,
            'amount_paid' => 100000,
            'payment_method' => 'cash',
            'received_from' => 'Wali Uji',
        ])->assertSessionHasErrors('period');

        $this->assertSame($paymentsBefore, Payment::count());
    }

    public function test_bendahara_cannot_reopen_but_admin_can_with_reason_and_log(): void
    {
        $bendahara = $this->userWithRole(UserRole::Bendahara);
        $admin = $this->userWithRole(UserRole::Admin);
        $period = now()->subMonthNoOverflow();

        $closing = app(BookClosingService::class)->close((int) $period->year, (int) $period->month, $admin->id);
        ActivityLog::query()->delete();

        $this->actingAs($bendahara)->patch(route('admin.reports.closing.reopen', $closing), [
            'reopen_reason' => 'Coba buka dari akun bendahara',
        ])->assertForbidden();

        $this->assertTrue($closing->fresh()->isClosed());

        $this->actingAs($admin)->patch(route('admin.reports.closing.reopen', $closing), [
            'reopen_reason' => 'Kuitansi salah input perlu dikoreksi setelah laporan diserahkan.',
        ])->assertRedirect(route('admin.reports.closing', ['year' => $period->year]));

        $closing->refresh();
        $this->assertFalse($closing->isClosed());
        $this->assertNotNull($closing->reopened_at);
        $this->assertSame($admin->id, $closing->reopened_by);
        $this->assertStringContainsString('salah input', $closing->reopen_reason);

        $log = ActivityLog::where('module', ActivityModule::BookClosing->value)->sole();
        $this->assertSame(ActivityAction::Reopened, $log->action);
        $this->assertSame($admin->id, $log->user_id);

        // Setelah dibuka, pengeluaran pada periode itu kembali diterima.
        $this->actingAs($bendahara)->post(route('admin.expenses.store'), [
            'category' => ExpenseCategory::Operasional->value,
            'description' => 'Koreksi setelah periode dibuka',
            'amount' => 50000,
            'expense_date' => $period->copy()->day(20)->toDateString(),
            'payment_method' => 'cash',
        ])->assertRedirect(route('admin.expenses'));

        $this->assertDatabaseHas('expenses', ['description' => 'Koreksi setelah periode dibuka']);
    }

    public function test_period_that_has_not_started_cannot_be_closed(): void
    {
        $next = now()->addMonthNoOverflow()->startOfMonth();

        $this->expectException(ValidationException::class);

        app(BookClosingService::class)->close((int) $next->year, (int) $next->month, null);
    }

    public function test_closing_requires_confirmation_checkbox(): void
    {
        $bendahara = $this->userWithRole(UserRole::Bendahara);
        $period = now()->subMonthNoOverflow();

        $this->actingAs($bendahara)->post(route('admin.reports.closing.store'), [
            'period_year' => $period->year,
            'period_month' => $period->month,
        ])->assertSessionHasErrors('confirm_close');

        $this->assertNull(BookClosing::forPeriod((int) $period->year, (int) $period->month));
    }
}
