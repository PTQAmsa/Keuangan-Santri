<?php

namespace Tests\Feature;

use Database\Seeders\DemoAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoModeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_demo_credentials_are_hidden_when_demo_mode_is_disabled(): void
    {
        config(['app.demo_mode' => false]);

        $this->get(route('admin.login'))
            ->assertOk()
            ->assertDontSee('Akun Pengurus untuk Uji Coba')
            ->assertDontSee('admin@ptqamsa.id')
            ->assertSee('name="password" id="password" value=""', false);

        $this->get(route('portal.login'))
            ->assertOk()
            ->assertDontSee('Akun Santri untuk Uji Coba')
            ->assertSee('name="nis" id="nis" value=""', false)
            ->assertSee('name="identifier" id="identifier" value=""', false);
    }

    public function test_demo_credentials_are_shown_when_demo_mode_is_enabled(): void
    {
        config(['app.demo_mode' => true]);

        $this->get(route('admin.login'))
            ->assertOk()
            ->assertSee('Akun Pengurus untuk Uji Coba')
            ->assertSee('admin@ptqamsa.id')
            ->assertSee('value="password"', false);

        $this->get(route('portal.login'))
            ->assertOk()
            ->assertSee('Akun Santri untuk Uji Coba')
            ->assertSee('UJI00001')
            ->assertSee('100001');
    }

    public function test_seeded_test_accounts_can_log_in_to_the_portal_with_pin_only(): void
    {
        Storage::fake('local');

        $this->seed(DemoAccountSeeder::class);

        $this->post(route('portal.login.post'), [
            'nis' => 'UJI00001',
            'identifier' => '100001',
        ])->assertRedirect(route('portal.dashboard'));

        $this->get(route('portal.dashboard'))
            ->assertOk()
            ->assertSee('Santri Uji Coba Putra')
            ->assertSee('Uji Coba');

        // Akun uji coba tidak ikut terhapus saat data riil menggantikan dummy.
        $this->artisan('santri:import', ['file' => __DIR__.'/../Fixtures/santri-impor.csv', '--hapus-dummy' => true])->assertSuccessful();
        $this->assertDatabaseHas('students', ['nis' => 'UJI00001']);
        $this->assertDatabaseHas('students', ['nis' => 'UJI00002']);
    }
}
