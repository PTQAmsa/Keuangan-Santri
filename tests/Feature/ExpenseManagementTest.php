<?php

namespace Tests\Feature;

use App\Enums\ActivityAction;
use App\Enums\ActivityModule;
use App\Enums\ExpenseCategory;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseManagementTest extends TestCase
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

    public function test_bendahara_can_view_expenses_page_and_demo_data(): void
    {
        $bendahara = $this->userWithRole(UserRole::Bendahara);

        $response = $this->actingAs($bendahara)->get(route('admin.expenses'));

        $response->assertOk();
        $response->assertSee('Kas Keluar');
        $response->assertSee('Konsumsi rapat wali santri');
        $response->assertSee('EXP-202610-0001');
        $response->assertSee('Rp 1.250.000');
        $response->assertSee('EXP-202610-0002');
        $response->assertSee('Dibatalkan');
        $response->assertDontSee('EXP-202607-0001');

        $rangeResponse = $this->actingAs($bendahara)->get(route('admin.expenses', [
            'start_date' => '2026-07-01',
            'end_date' => '2026-10-31',
        ]));

        $rangeResponse->assertOk();
        $rangeResponse->assertSee('Pembelian alat kebersihan asrama');
        $rangeResponse->assertSee('EXP-202607-0001');

        $rangeTotal = Expense::notVoided()
            ->whereDate('expense_date', '>=', '2026-07-01')
            ->whereDate('expense_date', '<=', '2026-10-31')
            ->sum('amount');

        $rangeResponse->assertSee('Rp '.number_format((float) $rangeTotal, 0, ',', '.'));
    }

    public function test_bendahara_can_create_expense_and_it_is_logged(): void
    {
        $bendahara = $this->userWithRole(UserRole::Bendahara);

        $response = $this->actingAs($bendahara)->post(route('admin.expenses.store'), [
            'category' => ExpenseCategory::Operasional->value,
            'description' => 'Pembelian lampu penerangan asrama',
            'amount' => 275000,
            'expense_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'recipient' => 'Toko Listrik Terang',
            'notes' => 'Lampu LED 12 buah.',
        ]);

        $response->assertRedirect(route('admin.expenses'));

        $expense = Expense::where('description', 'Pembelian lampu penerangan asrama')->firstOrFail();
        $this->assertSame(ExpenseCategory::Operasional, $expense->category);
        $this->assertSame($bendahara->id, $expense->recorded_by);
        $this->assertStringStartsWith('EXP-', $expense->expense_code);

        $log = ActivityLog::sole();
        $this->assertSame(ActivityModule::Expense, $log->module);
        $this->assertSame(ActivityAction::Created, $log->action);
        $this->assertStringContainsString($expense->expense_code, $log->description);
        $this->assertSame($bendahara->id, $log->user_id);
    }

    public function test_expense_validation_rejects_empty_data(): void
    {
        $this->actingAs($this->userWithRole(UserRole::Bendahara))
            ->post(route('admin.expenses.store'), [])
            ->assertSessionHasErrors([
                'category',
                'description',
                'amount',
                'expense_date',
                'payment_method',
            ]);

        $this->assertSame(0, Expense::where('description', '')->count());
    }

    public function test_bendahara_can_void_expense_and_it_is_logged(): void
    {
        $bendahara = $this->userWithRole(UserRole::Bendahara);
        $expense = Expense::where('expense_code', 'EXP-202607-0001')->firstOrFail();

        $this->actingAs($bendahara)
            ->patch(route('admin.expenses.void', $expense), [
                'void_reason' => 'Nota ganda, sudah dicatat pada transaksi lain.',
            ])
            ->assertRedirect();

        $expense->refresh();
        $this->assertTrue($expense->isVoided());
        $this->assertSame($bendahara->id, $expense->voided_by);
        $this->assertSame('Nota ganda, sudah dicatat pada transaksi lain.', $expense->void_reason);

        $log = ActivityLog::sole();
        $this->assertSame(ActivityModule::Expense, $log->module);
        $this->assertSame(ActivityAction::Cancelled, $log->action);
        $this->assertStringContainsString($expense->expense_code, $log->description);
    }

    public function test_pengasuh_can_view_expenses_but_cannot_record(): void
    {
        $pengasuh = $this->userWithRole(UserRole::Pengasuh);

        $this->actingAs($pengasuh)
            ->get(route('admin.expenses'))
            ->assertOk()
            ->assertSee('readonly-mode', false);

        $this->actingAs($pengasuh)
            ->post(route('admin.expenses.store'), [
                'category' => ExpenseCategory::Operasional->value,
                'description' => 'Coba catat',
                'amount' => 10000,
                'expense_date' => now()->toDateString(),
                'payment_method' => 'cash',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('expenses', ['description' => 'Coba catat']);
    }
}
