<?php

namespace Tests\Feature;

use App\Enums\ExpenseCategory;
use App\Models\Expense;
use App\Models\OpeningBalance;
use App\Models\User;
use App\Services\BookClosingService;
use App\Services\CashFlowService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OpeningBalanceImportTest extends TestCase
{
    use RefreshDatabase;

    private string $fixture = __DIR__.'/../Fixtures/saldo-awal.csv';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_csv_rows_become_opening_balances_per_cash_and_bank_account(): void
    {
        $this->artisan('kas:saldo-awal', ['file' => $this->fixture])
            ->expectsOutputToContain('2 saldo awal dicatat, 1 baris dilewati, 0 saldo lama dihapus')
            ->expectsOutputToContain('Baris 4 dilewati: Nominal kosong atau nol')
            ->expectsOutputToContain('Total saldo awal: Rp 17.500.000')
            ->assertSuccessful();

        $this->assertDatabaseCount('opening_balances', 2);

        $cash = OpeningBalance::where('payment_method', 'cash')->firstOrFail();
        $this->assertSame(2500000.0, (float) $cash->amount);
        $this->assertSame('2026-11-01', $cash->effective_date->toDateString());
        $this->assertSame('Kas Tunai', $cash->placeLabel());

        $bank = OpeningBalance::where('payment_method', 'transfer')->firstOrFail();
        $this->assertSame(15000000.0, (float) $bank->amount);
        $this->assertSame('BSI', $bank->bank_name);
        $this->assertSame('1202020212', $bank->bank_account_number);
        $this->assertSame('BSI 1202020212', $bank->placeLabel());
    }

    public function test_second_import_is_refused_unless_replacement_is_requested(): void
    {
        $this->artisan('kas:saldo-awal', ['file' => $this->fixture])->assertSuccessful();

        $this->artisan('kas:saldo-awal', ['file' => $this->fixture])
            ->expectsOutputToContain('Saldo awal sudah pernah diisi')
            ->assertFailed();

        $this->assertDatabaseCount('opening_balances', 2);

        $this->artisan('kas:saldo-awal', ['file' => $this->fixture, '--ganti' => true])
            ->expectsOutputToContain('2 saldo awal dicatat, 1 baris dilewati, 2 saldo lama dihapus')
            ->assertSuccessful();

        $this->assertDatabaseCount('opening_balances', 2);
    }

    public function test_opening_balance_counts_as_starting_balance_not_as_cash_inflow(): void
    {
        $flow = app(CashFlowService::class);
        $baseNovember = $flow->build(Carbon::parse('2026-11-01'), Carbon::parse('2026-11-30'));
        $baseOktober = $flow->build(Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31'));

        $this->artisan('kas:saldo-awal', ['file' => $this->fixture])->assertSuccessful();

        $admin = User::where('email', 'admin@ptqamsa.id')->firstOrFail();

        Expense::create([
            'expense_code' => 'EXP-TEST-SALDO',
            'category' => ExpenseCategory::Operasional,
            'description' => 'Belanja setelah saldo awal',
            'amount' => 1000000,
            'expense_date' => '2026-11-05',
            'payment_method' => 'cash',
            'recipient' => 'Toko',
            'recorded_by' => $admin->id,
        ]);

        $november = $flow->build(Carbon::parse('2026-11-01'), Carbon::parse('2026-11-30'));
        $this->assertSame($baseNovember['openingBalance'] + 17500000.0, $november['openingBalance']);
        $this->assertSame($baseNovember['closingBalance'] + 17500000.0 - 1000000.0, $november['closingBalance']);
        $this->assertSame($baseNovember['totalMasuk'], $november['totalMasuk']);

        // Bulan sebelum sistem dipakai belum mengenal saldo awal.
        $oktober = $flow->build(Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31'));
        $this->assertSame($baseOktober['openingBalance'], $oktober['openingBalance']);
        $this->assertSame($baseOktober['closingBalance'], $oktober['closingBalance']);

        // Bulan berikutnya mewarisi saldo akhir November.
        $desember = app(BookClosingService::class)->summary(2026, 12);
        $this->assertSame($baseNovember['closingBalance'] + 16500000.0, $desember['opening_balance']);
    }

    public function test_template_option_writes_an_example_file(): void
    {
        Storage::fake('local');

        $this->artisan('kas:saldo-awal', ['--template' => true])->assertSuccessful();
        $this->artisan('tagihan:tunggakan-awal', ['--template' => true])->assertSuccessful();

        Storage::disk('local')->assertExists('import/template-saldo-awal.csv');
        Storage::disk('local')->assertExists('import/template-tunggakan-awal.csv');
        $this->assertStringStartsWith('TANGGAL,JENIS,NOMINAL', Storage::disk('local')->get('import/template-saldo-awal.csv'));
        $this->assertStringStartsWith('NIS,NAMA,JENIS TAGIHAN', Storage::disk('local')->get('import/template-tunggakan-awal.csv'));
    }
}
