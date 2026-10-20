<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertSee('202601001')
            ->assertSee('085692253516');
    }
}
