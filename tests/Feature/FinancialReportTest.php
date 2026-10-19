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
     * Test Laporan Kas Masuk dapat diakses dengan benar
     */
    public function test_cash_inflow_report_accessible_and_renders_data(): void
    {
        $response = $this->get(route('admin.reports.cash'));

        $response->assertStatus(200);
        $response->assertSee('Laporan Penerimaan Kas');
        $response->assertSee('Total Kas Masuk');
        $response->assertSee('Jumlah Transaksi');
    }

    /**
     * Test Filter Laporan Kas berdasarkan metode pembayaran
     */
    public function test_cash_inflow_report_filter_by_method(): void
    {
        $response = $this->get(route('admin.reports.cash', [
            'payment_method' => 'cash',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Laporan Penerimaan Kas');
    }

    /**
     * Test urutan bawaan laporan kas: tanggal terbaru paling atas
     */
    public function test_cash_inflow_default_order_is_newest_first(): void
    {
        $response = $this->get(route('admin.reports.cash', [
            'start_date' => '2026-07-01',
            'end_date' => '2026-10-31',
        ]));

        $response->assertStatus(200);
        $response->assertSeeInOrder([
            'KWT-202610-0001',
            'KWT-202607-0003',
            'KWT-202607-0002',
            'KWT-202607-0001',
        ]);
    }

    /**
     * Test kolom "Nominal Bayar" dapat diurutkan dari nilai terkecil
     */
    public function test_cash_inflow_can_sort_by_amount_ascending(): void
    {
        $response = $this->get(route('admin.reports.cash', [
            'start_date' => '2026-07-01',
            'end_date' => '2026-10-31',
            'sort_by' => 'amount_paid',
            'sort_order' => 'asc',
        ]));

        $response->assertStatus(200);
        $response->assertSeeInOrder([
            'KWT-202607-0001',
            'KWT-202607-0002',
            'KWT-202610-0001',
            'KWT-202607-0003',
        ]);
    }

    /**
     * Test setiap kolom tabel dapat diurutkan, termasuk kolom dari tabel relasi
     */
    public function test_cash_inflow_can_sort_by_each_column(): void
    {
        $columns = [
            'id',
            'receipt_number',
            'payment_date',
            'student_name',
            'category',
            'payment_method',
            'amount_paid',
        ];

        foreach ($columns as $column) {
            $response = $this->get(route('admin.reports.cash', [
                'start_date' => '2026-07-01',
                'end_date' => '2026-10-31',
                'sort_by' => $column,
                'sort_order' => 'asc',
            ]));

            $response->assertStatus(200);
        }
    }

    /**
     * Test kolom sortir yang tidak dikenal jatuh ke kolom bawaan (tanpa error)
     */
    public function test_cash_inflow_ignores_unknown_sort_column(): void
    {
        $response = $this->get(route('admin.reports.cash', [
            'start_date' => '2026-07-01',
            'end_date' => '2026-10-31',
            'sort_by' => 'drop_table_students',
            'sort_order' => 'asc',
        ]));

        $response->assertStatus(200);

        // Kolom tak dikenal diganti "payment_date", sehingga urutannya menaik menurut tanggal.
        $response->assertSeeInOrder([
            'KWT-202607-0001',
            'KWT-202607-0002',
            'KWT-202607-0003',
            'KWT-202610-0001',
        ]);
    }

    /**
     * Test Export CSV Laporan Penerimaan Kas
     */
    public function test_export_cash_inflow_csv_download(): void
    {
        $response = $this->get(route('admin.reports.cash.csv'));

        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->headers->get('content-type'), 'text/csv'));
    }

    /**
     * Test Unduh PDF Resmi Laporan Penerimaan Kas
     */
    public function test_export_cash_inflow_pdf_download(): void
    {
        $response = $this->get(route('admin.reports.cash.pdf'));

        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->headers->get('content-type'), 'application/pdf'));
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
    }

    /**
     * Test Export CSV Laporan Tunggakan Santri
     */
    public function test_export_arrears_csv_download(): void
    {
        $response = $this->get(route('admin.reports.arrears.csv'));

        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->headers->get('content-type'), 'text/csv'));
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
