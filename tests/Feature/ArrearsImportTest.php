<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Student;
use App\Services\ArrearsImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArrearsImportTest extends TestCase
{
    use RefreshDatabase;

    private string $fixture = __DIR__.'/../Fixtures/tunggakan-awal.csv';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function importedInvoices()
    {
        return Invoice::where('notes', 'like', ArrearsImportService::IMPORT_NOTE.'%')->orderBy('id')->get();
    }

    public function test_csv_rows_become_unpaid_invoices_for_the_remaining_amount_only(): void
    {
        $this->artisan('tagihan:tunggakan-awal', ['file' => $this->fixture])
            ->expectsOutputToContain('3 tagihan tunggakan awal dibuat, 4 baris dilewati. Total tunggakan Rp 4.700.000.')
            ->expectsOutputToContain('Baris 5 (NIS 202601002) dilewati: Sisa tagihan nol, sudah lunas')
            ->expectsOutputToContain('Baris 6 (NIS 999999) dilewati: NIS tidak ditemukan')
            ->expectsOutputToContain('Baris 7 (NIS 202601003) dilewati: Periode bulan wajib diisi')
            ->expectsOutputToContain('Baris 8 (NIS 202601003) dilewati: Jenis tagihan tidak dikenali: Les Renang')
            ->assertSuccessful();

        $invoices = $this->importedInvoices();
        $this->assertCount(3, $invoices);

        $salman = Student::where('nis', '202601001')->firstOrFail();

        $agustus = $invoices[0];
        $this->assertSame($salman->id, $agustus->student_id);
        $this->assertSame('SPP', $agustus->category->code);
        $this->assertSame(8, $agustus->period_month);
        $this->assertSame(2026, $agustus->period_year);
        $this->assertSame(1350000.0, (float) $agustus->amount);
        $this->assertSame('unpaid', $agustus->status);
        $this->assertSame('2026-08-10', $agustus->due_date->toDateString());
        $this->assertStringStartsWith('INV-SPP-202608-', $agustus->invoice_number);
        $this->assertStringContainsString('Agustus 2026', $agustus->title);

        $september = $invoices[1];
        $this->assertSame(850000.0, (float) $september->amount);
        $this->assertSame(0.0, (float) $september->paid_amount);
        $this->assertSame('2026-09-15', $september->due_date->toDateString());
        $this->assertStringContainsString('Tagihan semula Rp 1.350.000, sudah dibayar Rp 500.000', $september->notes);

        $wakaf = $invoices[2];
        $this->assertSame('WAKAF', $wakaf->category->code);
        $this->assertNull($wakaf->period_month);
        $this->assertSame(2500000.0, (float) $wakaf->amount);
        $this->assertSame('Wakaf cicilan kedua', $wakaf->title);

        $this->assertDatabaseHas('activity_logs', [
            'module' => 'invoice',
            'description' => 'Mengimpor 3 tunggakan awal dari berkas tunggakan-awal.csv.',
        ]);
    }

    public function test_running_the_same_file_twice_does_not_duplicate_invoices(): void
    {
        $this->artisan('tagihan:tunggakan-awal', ['file' => $this->fixture])->assertSuccessful();

        $this->artisan('tagihan:tunggakan-awal', ['file' => $this->fixture])
            ->expectsOutputToContain('0 tagihan tunggakan awal dibuat, 7 baris dilewati')
            ->expectsOutputToContain('Sudah ada tagihan')
            ->assertSuccessful();

        $this->assertCount(3, $this->importedInvoices());
    }

    public function test_missing_file_or_required_column_fails_without_creating_invoices(): void
    {
        $before = Invoice::count();

        $this->artisan('tagihan:tunggakan-awal', ['file' => __DIR__.'/../Fixtures/tidak-ada.csv'])
            ->expectsOutputToContain('Berkas tidak ditemukan')
            ->assertFailed();

        $invalid = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($invalid, "NAMA,NOMINAL\nSalman,1000\n");

        $this->artisan('tagihan:tunggakan-awal', ['file' => $invalid])
            ->expectsOutputToContain('Kolom NIS tidak ditemukan')
            ->assertFailed();

        unlink($invalid);

        $this->assertSame($before, Invoice::count());
    }
}
