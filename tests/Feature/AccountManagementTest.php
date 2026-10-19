<?php

namespace Tests\Feature;

use App\Enums\ActivityAction;
use App\Enums\ActivityModule;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountManagementTest extends TestCase
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
     * Test 1: Administrator dapat menambah akun pengurus baru & tercatat di log.
     */
    public function test_admin_can_create_pengurus_account_and_it_is_logged(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);

        $this->actingAs($admin)
            ->post(route('admin.accounts.store'), [
                'name' => 'Usth. Fatimah',
                'email' => 'fatimah@ptqamsa.id',
                'jabatan' => 'Bendahara 3',
                'role' => 'bendahara',
                'password' => 'rahasia123',
                'password_confirmation' => 'rahasia123',
            ])
            ->assertRedirect();

        $user = User::where('email', 'fatimah@ptqamsa.id')->firstOrFail();
        $this->assertSame('Usth. Fatimah', $user->name);
        $this->assertSame(UserRole::Bendahara, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertTrue(Hash::check('rahasia123', $user->password));

        $log = ActivityLog::sole();
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame(ActivityModule::Auth, $log->module);
        $this->assertSame(ActivityAction::Created, $log->action);
        $this->assertStringContainsString('Usth. Fatimah', $log->description);
    }

    /**
     * Test 2: Hanya Administrator yang dapat membuka halaman kelola akun.
     */
    public function test_only_admin_can_manage_accounts(): void
    {
        $bendahara = $this->userWithRole(UserRole::Bendahara);
        $pengasuh = $this->userWithRole(UserRole::Pengasuh);

        $this->actingAs($bendahara)->get(route('admin.accounts'))->assertForbidden();
        $this->actingAs($pengasuh)->get(route('admin.accounts'))->assertForbidden();

        $this->actingAs($bendahara)
            ->post(route('admin.accounts.store'), [
                'name' => 'Penyusup',
                'email' => 'penyusup@ptqamsa.id',
                'role' => 'admin',
                'password' => 'rahasia123',
                'password_confirmation' => 'rahasia123',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'penyusup@ptqamsa.id']);
    }

    /**
     * Test 3: Administrator dapat mereset sandi pengurus.
     */
    public function test_admin_can_reset_pengurus_password(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        $bendahara = $this->userWithRole(UserRole::Bendahara);

        $this->actingAs($admin)
            ->patch(route('admin.accounts.reset-password', $bendahara), [
                'password' => 'sandiBaru2026',
                'password_confirmation' => 'sandiBaru2026',
            ])
            ->assertRedirect();

        $this->assertTrue(Hash::check('sandiBaru2026', $bendahara->fresh()->password));

        $log = ActivityLog::sole();
        $this->assertSame(ActivityAction::Updated, $log->action);
        $this->assertStringContainsString('Mereset kata sandi', $log->description);
    }

    /**
     * Test 4: Pengurus dapat mengganti sandinya sendiri dengan verifikasi sandi lama.
     */
    public function test_user_can_change_own_password(): void
    {
        $bendahara = $this->userWithRole(UserRole::Bendahara);

        // Sandi lama salah → ditolak.
        $this->actingAs($bendahara)
            ->patch(route('admin.password.update'), [
                'current_password' => 'salah',
                'password' => 'sandiBaru2026',
                'password_confirmation' => 'sandiBaru2026',
            ])
            ->assertSessionHasErrors('current_password');

        $this->actingAs($bendahara)
            ->patch(route('admin.password.update'), [
                'current_password' => 'password',
                'password' => 'sandiBaru2026',
                'password_confirmation' => 'sandiBaru2026',
            ])
            ->assertRedirect();

        $this->assertTrue(Hash::check('sandiBaru2026', $bendahara->fresh()->password));
        $this->assertSame(ActivityAction::Updated, ActivityLog::sole()->action);
    }

    /**
     * Test 5: Akun yang dinonaktifkan tidak dapat login.
     */
    public function test_deactivated_account_cannot_login(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        $bendahara = $this->userWithRole(UserRole::Bendahara);

        $this->actingAs($admin)
            ->patch(route('admin.accounts.status', $bendahara))
            ->assertRedirect();

        $this->assertFalse($bendahara->fresh()->is_active);

        $this->post(route('admin.logout'));
        $this->post(route('admin.login.post'), [
            'email' => $bendahara->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    /**
     * Test 6: Administrator tidak dapat menonaktifkan akunnya sendiri.
     */
    public function test_admin_cannot_deactivate_own_account(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);

        $this->actingAs($admin)
            ->patch(route('admin.accounts.status', $admin))
            ->assertSessionHasErrors('account');

        $this->assertTrue($admin->fresh()->is_active);
    }

    /**
     * Test 7: Semua peran dapat membuka halaman ganti sandi sendiri.
     */
    public function test_all_roles_can_open_change_password_page(): void
    {
        foreach ([UserRole::Admin, UserRole::Bendahara, UserRole::Pengasuh] as $role) {
            $this->actingAs($this->userWithRole($role))
                ->get(route('admin.password.edit'))
                ->assertOk()
                ->assertSee('Ganti Kata Sandi');
        }
    }

    /**
     * Test 8: Halaman kelola akun & halaman tagihan dapat dirender tanpa galat.
     */
    public function test_account_and_invoice_pages_render(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);

        $this->actingAs($admin)->get(route('admin.accounts'))
            ->assertOk()
            ->assertSee('Kelola Akun Pengurus')
            ->assertSee('Reset Sandi');

        $this->actingAs($admin)->get(route('admin.invoices'))
            ->assertOk()
            ->assertSee('Ubah')
            ->assertSee('Batalkan');
    }
}
