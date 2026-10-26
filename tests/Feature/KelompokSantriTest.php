<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pemisahan santri putra/putri pada data santri, tagihan,
 * transaksi, laporan, dan dashboard (kas tetap satu atap).
 */
class KelompokSantriTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function userWithRole(UserRole $role): User
    {
        return User::where('role', $role->value)->firstOrFail();
    }

    public function test_student_page_shows_group_summary_and_filters_by_kelompok(): void
    {
        $response = $this->actingAs($this->userWithRole(UserRole::Bendahara))
            ->get(route('admin.students', ['kelompok' => 'putri']));

        $response->assertOk();
        $response->assertSee('Santri Putra');
        $response->assertSee('Santri Putri');
        $response->assertSee('Aisyah Khumaira');
        $response->assertSee('Aulia Syifarani');
        $response->assertDontSee('Zaid Abdullah Al-Qurthubi');
    }

    public function test_invoice_recap_can_filter_by_kelas(): void
    {
        $response = $this->actingAs($this->userWithRole(UserRole::Bendahara))
            ->get(route('admin.invoices', ['kelas' => '7A']));

        $response->assertOk();
        $response->assertSee('NIS: 202601002');
        $response->assertDontSee('NIS: 202601004');
    }

    public function test_invoice_recap_can_filter_by_kelompok(): void
    {
        $response = $this->actingAs($this->userWithRole(UserRole::Bendahara))
            ->get(route('admin.invoices', ['kelompok' => 'putra']));

        $response->assertOk();
        $response->assertSee('NIS: 202601002');
        $response->assertDontSee('NIS: 202601003');
    }

    public function test_transaction_page_shows_marhalah_column_and_filter(): void
    {
        $response = $this->actingAs($this->userWithRole(UserRole::Bendahara))
            ->get(route('admin.transactions'));

        $response->assertOk();
        $response->assertSee('Marhalah');
    }

    public function test_cash_report_shows_marhalah_column_and_filter(): void
    {
        $response = $this->actingAs($this->userWithRole(UserRole::Bendahara))
            ->get(route('admin.reports.cash', [
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
            ]));

        $response->assertOk();
        $response->assertSee('Marhalah');
    }

    public function test_wali_relation_lainnya_requires_detail(): void
    {
        $response = $this->actingAs($this->userWithRole(UserRole::Bendahara))
            ->post(route('admin.students.store'), [
                'nis' => '202601098',
                'nama_lengkap' => 'Husain bin Ali',
                'jenis_kelamin' => 'L',
                'santri_group' => 'putra',
                'kelas' => '7A',
                'tahun_masuk' => 2026,
                'status' => 'aktif',
                'nama_wali' => 'Bpk. Ali',
                'no_whatsapp_wali' => '081234567890',
                'hubungan_wali' => 'Lainnya',
                'pin_portal' => '654321',
                'diskon_beasiswa' => 0,
            ]);

        $response->assertSessionHasErrors('hubungan_wali_detail');
        $this->assertDatabaseMissing('students', ['nis' => '202601098']);

        $response = $this->actingAs($this->userWithRole(UserRole::Bendahara))
            ->post(route('admin.students.store'), [
                'nis' => '202601098',
                'nama_lengkap' => 'Husain bin Ali',
                'jenis_kelamin' => 'L',
                'santri_group' => 'putra',
                'kelas' => '7A',
                'tahun_masuk' => 2026,
                'status' => 'aktif',
                'nama_wali' => 'Bpk. Ali',
                'no_whatsapp_wali' => '081234567890',
                'hubungan_wali' => 'Lainnya',
                'hubungan_wali_detail' => 'Kakek dari pihak ayah',
                'pin_portal' => '654321',
                'diskon_beasiswa' => 0,
            ]);

        $response->assertRedirect(route('admin.students'));
        $this->assertDatabaseHas('students', [
            'nis' => '202601098',
            'hubungan_wali' => 'Lainnya',
            'hubungan_wali_detail' => 'Kakek dari pihak ayah',
        ]);
    }

    public function test_dashboard_splits_income_and_arrears_by_group(): void
    {
        $response = $this->actingAs($this->userWithRole(UserRole::Bendahara))
            ->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Pemasukan Bulan Ini');
        $response->assertSee('Tunggakan Santri Bulan Ini');
        $response->assertSee('Putra');
        $response->assertSee('Putri');

        $this->assertEqualsWithDelta(
            (float) $response->viewData('totalMasukBulanIni'),
            (float) $response->viewData('masukPutra') + (float) $response->viewData('masukPutri'),
            0.01,
            'Pemasukan putra + putri harus sama dengan pemasukan keseluruhan.'
        );

        $this->assertEqualsWithDelta(
            (float) $response->viewData('totalTunggakan'),
            (float) $response->viewData('tunggakanPutra') + (float) $response->viewData('tunggakanPutri'),
            0.01,
            'Tunggakan putra + putri harus sama dengan tunggakan keseluruhan.'
        );
    }

    public function test_new_student_can_be_saved_as_putri_with_expanded_wali_relation(): void
    {
        $response = $this->actingAs($this->userWithRole(UserRole::Bendahara))
            ->post(route('admin.students.store'), [
                'nis' => '202601099',
                'nama_lengkap' => 'Fatimah Az-Zahra',
                'jenis_kelamin' => 'P',
                'santri_group' => 'putri',
                'kelas' => '7 Putri',
                'tahun_masuk' => 2026,
                'status' => 'aktif',
                'nama_wali' => 'Ibu Khadijah',
                'no_whatsapp_wali' => '081234567890',
                'hubungan_wali' => 'Ibu Angkat',
                'pin_portal' => '654321',
                'diskon_beasiswa' => 0,
            ]);

        $response->assertRedirect(route('admin.students'));

        $this->assertDatabaseHas('students', [
            'nis' => '202601099',
            'santri_group' => 'putri',
            'hubungan_wali' => 'Ibu Angkat',
        ]);
    }
}
