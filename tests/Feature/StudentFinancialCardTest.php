<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Student;
use App\Models\User;
use App\Services\StudentFinancialCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentFinancialCardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function student(): Student
    {
        return Student::where('nis', '202601001')->firstOrFail();
    }

    private function userWithRole(UserRole $role): User
    {
        return User::where('role', $role->value)->firstOrFail();
    }

    public function test_bendahara_can_view_student_financial_card(): void
    {
        $response = $this->actingAs($this->userWithRole(UserRole::Bendahara))
            ->get(route('admin.students.card', $this->student()));

        $response->assertOk();
        $response->assertSee('Kartu Keuangan Santri');
        $response->assertSee('Muhammad Salman');
        $response->assertSee('INV-202601-0001');
        $response->assertSee('KWT-202607-0001');
        $response->assertSee('Buku Kas Santri');
        $response->assertSee('Riwayat Pembayaran');
    }

    public function test_admin_can_download_student_financial_card_pdf(): void
    {
        $response = $this->actingAs($this->userWithRole(UserRole::Admin))
            ->get(route('admin.students.card.pdf', $this->student()));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_pengasuh_cannot_open_student_financial_card(): void
    {
        $this->actingAs($this->userWithRole(UserRole::Pengasuh))
            ->get(route('admin.students.card', $this->student()))
            ->assertForbidden();
    }

    public function test_ledger_running_balance_matches_total_arrears(): void
    {
        $data = app(StudentFinancialCardService::class)->build($this->student());

        $this->assertNotEmpty($data['ledger']);
        $this->assertEqualsWithDelta(
            $data['totalTunggakan'],
            $data['ledger']->last()['balance'],
            0.01
        );
        $this->assertTrue($data['ledger']->every(fn (array $entry) => $entry['balance'] >= 0));
    }

    public function test_wali_can_view_own_financial_card_after_login(): void
    {
        $this->post(route('portal.login.post'), [
            'nis' => '202601001',
            'identifier' => '085692253516',
        ])->assertRedirect(route('portal.dashboard'));

        // Login via WhatsApp wajib membuat PIN baru dulu.
        $this->post(route('portal.change-pin.post'), [
            'pin' => '654321',
            'pin_confirmation' => '654321',
        ])->assertRedirect(route('portal.dashboard'));

        $response = $this->get(route('portal.card'));

        $response->assertOk();
        $response->assertSee('Muhammad Salman');
        $response->assertSee('INV-202601-0001');
        $response->assertSee('KWT-202607-0001');
        $response->assertDontSee('Zaid Abdullah Al-Qurthubi');
    }

    public function test_wali_can_download_own_financial_card_pdf_after_login(): void
    {
        $this->post(route('portal.login.post'), [
            'nis' => '202601001',
            'identifier' => '085692253516',
        ]);

        $this->post(route('portal.change-pin.post'), [
            'pin' => '654321',
            'pin_confirmation' => '654321',
        ]);

        $response = $this->get(route('portal.card.pdf'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_wali_must_change_pin_before_opening_financial_card(): void
    {
        $this->post(route('portal.login.post'), [
            'nis' => '202601001',
            'identifier' => '085692253516',
        ]);

        $this->get(route('portal.card'))
            ->assertRedirect(route('portal.change-pin'));
    }
}
