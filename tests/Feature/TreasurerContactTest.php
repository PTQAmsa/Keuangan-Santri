<?php

namespace Tests\Feature;

use App\Services\TreasurerContactService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TreasurerContactTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_contacts_are_parsed_from_configuration_with_the_first_as_primary(): void
    {
        config(['app.treasurer_contacts' => 'Usth. A:0812-3456-7890; Usth. B : 62811222333 ;rusak;:kosong']);

        $contacts = TreasurerContactService::all();

        $this->assertCount(2, $contacts);
        $this->assertSame(['name' => 'Usth. A', 'phone' => '6281234567890', 'display' => '+62 812-3456-7890'], $contacts[0]);
        $this->assertSame('6281122233', substr($contacts[1]['phone'], 0, 10));
        $this->assertSame('https://wa.me/6281234567890?text=Halo', TreasurerContactService::primaryUrl('Halo'));

        config(['app.treasurer_contacts' => '']);
        $this->assertSame([], TreasurerContactService::all());
        $this->assertNull(TreasurerContactService::primaryUrl('Halo'));
    }

    public function test_portal_and_footer_use_the_configured_treasurer_numbers(): void
    {
        config(['app.treasurer_contacts' => 'Usth. Yuni:082236465949;Usth. Revina:083189063900']);

        $this->get(route('portal.login'))
            ->assertOk()
            ->assertSee('https://wa.me/6282236465949?text=', false)
            ->assertSee('Hubungi Bendahara')
            ->assertDontSee('6285692253516');

        $this->get(route('portal.forgot-pin'))
            ->assertOk()
            ->assertSee('https://wa.me/6282236465949?text=', false);

        $this->get('/')
            ->assertOk()
            ->assertSee('+62 822-3646-5949')
            ->assertSee('+62 831-8906-3900')
            ->assertSee('Usth. Revina');
    }

    public function test_contact_links_are_hidden_when_no_number_is_configured(): void
    {
        config(['app.treasurer_contacts' => '']);

        $this->get(route('portal.login'))
            ->assertOk()
            ->assertDontSee('Hubungi Bendahara')
            ->assertDontSee('wa.me');
    }
}
