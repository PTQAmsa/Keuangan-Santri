<?php

namespace Tests\Feature;

use App\Enums\ActivityAction;
use App\Enums\ActivityModule;
use App\Enums\IncomeCategory;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\OtherIncome;
use App\Models\User;
use App\Services\BookClosingService;
use App\Services\CashFlowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OtherIncomeTest extends TestCase
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
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'category' => IncomeCategory::Donasi->value,
            'description' => 'Donasi pembangunan asrama putri',
            'amount' => 2500000,
            'income_date' => now()->toDateString(),
            'payment_method' => 'transfer',
            'source' => 'H. Mahmud (Alumni)',
            'notes' => 'Transfer via BSI.',
        ], $overrides);
    }

    public function test_navbar_shows_kas_masuk_with_cashier_and_other_income_tabs(): void
    {
        $bendahara = $this->userWithRole(UserRole::Bendahara);

        $cashier = $this->actingAs($bendahara)->get(route('admin.cashier'));
        $cashier->assertOk()
            ->assertSee('Kas Masuk')
            ->assertSee('Kasir Santri')
            ->assertSee('Pemasukan Lainnya')
            ->assertSee(route('admin.incomes'), false)
            ->assertDontSee('bg-gold-400 text-brand-900 font-bold', false);
        $this->assertMatchesRegularExpression(
            '/href="'.preg_quote(route('admin.cashier'), '/').'"\s+role="menuitem"\s+aria-current="page"/',
            $cashier->getContent(),
        );

        $incomes = $this->actingAs($bendahara)->get(route('admin.incomes'));
        $incomes->assertOk()
            ->assertSee('Pemasukan Lainnya')
            ->assertSee('Catat Pemasukan Baru');
        $this->assertMatchesRegularExpression(
            '/href="'.preg_quote(route('admin.incomes'), '/').'"\s+role="menuitem"\s+aria-current="page"/',
            $incomes->getContent(),
        );
    }

    public function test_bendahara_can_record_other_income_and_it_flows_into_cash_reports(): void
    {
        $bendahara = $this->userWithRole(UserRole::Bendahara);

        $this->actingAs($bendahara)
            ->post(route('admin.incomes.store'), $this->payload())
            ->assertRedirect(route('admin.incomes'))
            ->assertSessionHasNoErrors();

        $income = OtherIncome::sole();
        $this->assertSame('INC-'.now()->format('Ym').'-0001', $income->income_code);
        $this->assertSame(IncomeCategory::Donasi, $income->category);
        $this->assertSame('2500000.00', $income->amount);
        $this->assertSame($bendahara->id, $income->recorded_by);

        $log = ActivityLog::where('module', ActivityModule::Income->value)
            ->where('action', ActivityAction::Created->value)
            ->sole();
        $this->assertStringContainsString($income->income_code, $log->description);

        // Masuk ke arus kas sebagai kas masuk.
        $flow = app(CashFlowService::class)->build(now()->startOfMonth(), now()->endOfMonth());
        $entry = $flow['entries']->firstWhere('code', $income->income_code);
        $this->assertNotNull($entry);
        $this->assertSame('masuk', $entry['direction']);
        $this->assertSame(2500000.0, $entry['amount']);

        // Masuk ke dashboard sebagai pemasukan bulan ini.
        $dashboard = $this->actingAs($bendahara)->get(route('admin.dashboard'));
        $dashboard->assertOk();
        $this->assertSame(2500000.0, (float) $dashboard->viewData('masukLainnya'));
        $this->assertSame(
            (float) $dashboard->viewData('masukPutra') + (float) $dashboard->viewData('masukPutri') + 2500000.0,
            (float) $dashboard->viewData('totalMasukBulanIni'),
        );

        // Masuk ke laporan Kas Masuk, termasuk ekspor CSV.
        $report = $this->actingAs($bendahara)->get(route('admin.reports.cash', [
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
        ]));
        $report->assertOk()
            ->assertSee('Pemasukan Lainnya')
            ->assertSee($income->income_code)
            ->assertSee('Total Seluruh Kas Masuk');
        $this->assertSame(
            (float) $report->viewData('totalPenerimaan') + 2500000.0,
            (float) $report->viewData('totalKasMasuk'),
        );

        $csv = $this->actingAs($bendahara)->get(route('admin.reports.cash.csv', [
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
        ]));
        $csv->assertOk();
        $this->assertStringContainsString($income->income_code, $csv->streamedContent());
        $this->assertStringContainsString('TOTAL SELURUH KAS MASUK', $csv->streamedContent());
    }

    public function test_other_income_validation_requires_core_fields(): void
    {
        $bendahara = $this->userWithRole(UserRole::Bendahara);

        $this->actingAs($bendahara)
            ->post(route('admin.incomes.store'), [])
            ->assertSessionHasErrors(['category', 'description', 'amount', 'income_date', 'payment_method', 'source']);

        $this->assertSame(0, OtherIncome::count());
    }

    public function test_bendahara_can_void_other_income_and_it_leaves_cash_flow(): void
    {
        $bendahara = $this->userWithRole(UserRole::Bendahara);

        $this->actingAs($bendahara)->post(route('admin.incomes.store'), $this->payload());
        $income = OtherIncome::sole();

        $this->actingAs($bendahara)
            ->patch(route('admin.incomes.void', $income), [])
            ->assertSessionHasErrors('void_reason');

        $this->actingAs($bendahara)
            ->patch(route('admin.incomes.void', $income), ['void_reason' => 'Salah input nominal.'])
            ->assertRedirect();

        $income->refresh();
        $this->assertTrue($income->isVoided());
        $this->assertSame('Salah input nominal.', $income->void_reason);
        $this->assertSame($bendahara->id, $income->voided_by);

        $flow = app(CashFlowService::class)->build(now()->startOfMonth(), now()->endOfMonth());
        $this->assertNull($flow['entries']->firstWhere('code', $income->income_code));

        $this->assertSame(1, ActivityLog::where('module', ActivityModule::Income->value)
            ->where('action', ActivityAction::Cancelled->value)
            ->count());
    }

    public function test_other_income_is_locked_when_the_month_is_closed(): void
    {
        $bendahara = $this->userWithRole(UserRole::Bendahara);

        $this->actingAs($bendahara)->post(route('admin.incomes.store'), $this->payload());
        $income = OtherIncome::sole();

        app(BookClosingService::class)->close(now()->year, now()->month, $bendahara->id);

        $this->actingAs($bendahara)
            ->post(route('admin.incomes.store'), $this->payload(['description' => 'Infaq Jumat']))
            ->assertSessionHasErrors('period');

        $this->actingAs($bendahara)
            ->patch(route('admin.incomes.void', $income), ['void_reason' => 'Terlambat.'])
            ->assertSessionHasErrors('period');

        $this->assertSame(1, OtherIncome::count());
        $this->assertFalse($income->fresh()->isVoided());
    }

    public function test_pengasuh_cannot_manage_other_income(): void
    {
        $pengasuh = $this->userWithRole(UserRole::Pengasuh);

        $this->actingAs($pengasuh)->get(route('admin.incomes'))->assertOk()->assertSee('Mode lihat saja');
        $this->actingAs($pengasuh)->post(route('admin.incomes.store'), $this->payload())->assertForbidden();

        $this->assertSame(0, OtherIncome::count());
    }
}
