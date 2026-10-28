<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->actingAs(User::where('email', 'admin@ptqamsa.id')->firstOrFail());
    }

    /**
     * Test Laporan Tunggakan Santri dapat diakses
     */
    public function test_arrears_report_accessible_and_renders_data(): void
    {
        $response = $this->get(route('admin.reports.arrears'));

        $response->assertStatus(200);
        $response->assertSee('Laporan Rekapitulasi Tunggakan');
        $response->assertSee('Total Nilai Tunggakan');
        $response->assertSee('Santri Menunggak');
        $response->assertSee('sort_by=student_name', false);
    }

    /**
     * Test setiap kolom tabel tunggakan dapat diurutkan.
     */
    public function test_arrears_report_can_sort_by_each_table_column(): void
    {
        foreach (['student_name', 'wali', 'detail', 'total_tunggakan'] as $column) {
            $this->get(route('admin.reports.arrears', [
                'sort_by' => $column,
                'sort_order' => 'asc',
            ]))->assertOk();
        }
    }

    /**
     * Test Export CSV Laporan Tunggakan Santri
     */
    public function test_export_arrears_csv_download(): void
    {
        $response = $this->get(route('admin.reports.arrears.csv'));

        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->headers->get('content-type'), 'text/csv'));
        $this->assertStringContainsString(
            'Tanggal Unduh: '.now()->translatedFormat('d F Y'),
            $response->streamedContent()
        );
    }

    /**
     * Test Unduh PDF Resmi Laporan Tunggakan Santri
     */
    public function test_export_arrears_pdf_download(): void
    {
        $response = $this->get(route('admin.reports.arrears.pdf'));

        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->headers->get('content-type'), 'application/pdf'));
    }
}
