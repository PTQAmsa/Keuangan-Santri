<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
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

    /**
     * Test 1: Tamu yang membuka panel admin diarahkan ke halaman login pengurus
     */
    public function test_guest_is_redirected_to_login_from_dashboard(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('admin.login'));
    }

    /**
     * Test 2: Pengurus dapat login dengan email dan kata sandi yang benar
     */
    public function test_pengurus_can_login_with_valid_credentials(): void
    {
        $response = $this->post(route('admin.login.post'), [
            'email' => 'bendahara1@ptqamsa.id',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs(User::where('email', 'bendahara1@ptqamsa.id')->first());
    }

    /**
     * Test 3: Login ditolak bila kata sandi salah
     */
    public function test_login_is_rejected_with_wrong_password(): void
    {
        $response = $this->from(route('admin.login'))->post(route('admin.login.post'), [
            'email' => 'bendahara1@ptqamsa.id',
            'password' => 'salah-total',
        ]);

        $response->assertRedirect(route('admin.login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /**
     * Test 4: Validasi input login berjalan
     */
    public function test_login_requires_valid_email_and_password(): void
    {
        $response = $this->from(route('admin.login'))->post(route('admin.login.post'), [
            'email' => 'bukan-email',
            'password' => '',
        ]);

        $response->assertSessionHasErrors(['email', 'password']);
        $this->assertGuest();
    }

    /**
     * Test 5: Bendahara boleh membuka halaman kasir
     */
    public function test_bendahara_can_open_cashier_page(): void
    {
        $response = $this->actingAs($this->userWithRole(UserRole::Bendahara))
            ->get(route('admin.cashier'));

        $response->assertOk();
    }

    /**
     * Test 6: Pengasuh (read-only) boleh melihat halaman kasir dalam mode lihat saja
     */
    public function test_pengasuh_can_view_cashier_page_in_read_only_mode(): void
    {
        $response = $this->actingAs($this->userWithRole(UserRole::Pengasuh))
            ->get(route('admin.cashier'));

        $response->assertOk()
            ->assertSee('readonly-mode', false);
    }

    /**
     * Test 7: Pengasuh ditolak saat mencoba menerbitkan tagihan SPP massal
     */
    public function test_pengasuh_cannot_generate_monthly_spp(): void
    {
        $response = $this->actingAs($this->userWithRole(UserRole::Pengasuh))
            ->post(route('admin.generate-spp'), ['month' => 12, 'year' => 2026]);

        $response->assertForbidden();
    }

    /**
     * Test 8: Pengasuh tetap boleh melihat dashboard & laporan
     */
    public function test_pengasuh_can_view_dashboard_and_reports(): void
    {
        $pengasuh = $this->userWithRole(UserRole::Pengasuh);

        $this->actingAs($pengasuh)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($pengasuh)->get(route('admin.reports.cash'))->assertOk();
        $this->actingAs($pengasuh)->get(route('admin.reports.arrears'))->assertOk();
    }

    /**
     * Test 9: Pengurus dapat logout dari panel admin
     */
    public function test_pengurus_can_logout(): void
    {
        $response = $this->actingAs($this->userWithRole(UserRole::Admin))
            ->post(route('admin.logout'));

        $response->assertRedirect(route('admin.login'));
        $this->assertGuest();
    }
}
