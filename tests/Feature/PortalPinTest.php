<?php

namespace Tests\Feature;

use App\Enums\ActivityAction;
use App\Enums\ActivityModule;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PortalPinTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        ActivityLog::query()->delete();
    }

    private function student(): Student
    {
        return Student::where('nis', '202601001')->firstOrFail();
    }

    /**
     * Test 1: PIN bawaan disimpan sebagai hash dan login pertama wajib ganti PIN.
     */
    public function test_first_login_with_default_pin_is_forced_to_change_pin(): void
    {
        $student = $this->student();
        $this->assertNotSame('123456', $student->pin_portal);
        $this->assertTrue(Hash::check('123456', $student->pin_portal));

        $this->post(route('portal.login.post'), [
            'nis' => '202601001',
            'identifier' => '123456',
        ])->assertRedirect(route('portal.dashboard'));

        $this->get(route('portal.dashboard'))
            ->assertRedirect(route('portal.change-pin'));

        $this->get(route('portal.change-pin'))
            ->assertOk()
            ->assertSee('wajib diganti')
            ->assertSee('PIN Lama');
    }

    /**
     * Test 2: Login memakai nomor WhatsApp mewajibkan wali membuat PIN baru.
     */
    public function test_login_with_whatsapp_forces_pin_change(): void
    {
        $this->post(route('portal.login.post'), [
            'nis' => '202601001',
            'identifier' => '085692253516',
        ])->assertRedirect(route('portal.dashboard'));

        $this->assertTrue($this->student()->fresh()->must_change_pin);

        $this->get(route('portal.dashboard'))
            ->assertRedirect(route('portal.change-pin'));

        $this->get(route('portal.change-pin'))
            ->assertOk()
            ->assertSee('nomor WhatsApp wali')
            ->assertSee('tidak perlu memasukkan PIN lama');
    }

    /**
     * Test 3: Wali dapat mengganti PIN, dan PIN lama tidak berlaku lagi.
     */
    public function test_wali_can_change_pin_and_old_pin_stops_working(): void
    {
        $this->post(route('portal.login.post'), [
            'nis' => '202601001',
            'identifier' => '123456',
        ])->assertRedirect(route('portal.dashboard'));

        $this->post(route('portal.change-pin.post'), [
            'current_pin' => '123456',
            'pin' => '654321',
            'pin_confirmation' => '654321',
        ])->assertRedirect(route('portal.dashboard'));

        $student = $this->student()->fresh();
        $this->assertTrue(Hash::check('654321', $student->pin_portal));
        $this->assertFalse(Hash::check('123456', $student->pin_portal));
        $this->assertFalse($student->must_change_pin);
        $this->assertNotNull($student->pin_changed_at);

        $log = ActivityLog::sole();
        $this->assertNull($log->user_id);
        $this->assertSame(ActivityModule::Student, $log->module);
        $this->assertSame(ActivityAction::Updated, $log->action);
        $this->assertStringContainsString('mengganti PIN portal', $log->description);

        // Logout lalu coba login dengan PIN lama.
        $this->get(route('portal.logout'));

        $this->post(route('portal.login.post'), [
            'nis' => '202601001',
            'identifier' => '123456',
        ])->assertSessionHasErrors('identifier');

        // PIN baru berhasil dan dashboard dapat dibuka.
        $this->post(route('portal.login.post'), [
            'nis' => '202601001',
            'identifier' => '654321',
        ])->assertRedirect(route('portal.dashboard'));

        $this->get(route('portal.dashboard'))->assertOk();
    }

    /**
     * Test 4: PIN baru harus 6 angka, tidak sama dengan PIN lama, dan bukan 123456.
     */
    public function test_new_pin_must_be_six_digits_and_different(): void
    {
        $this->post(route('portal.login.post'), [
            'nis' => '202601001',
            'identifier' => '123456',
        ]);

        $this->post(route('portal.change-pin.post'), [
            'current_pin' => '123456',
            'pin' => '12345',
            'pin_confirmation' => '12345',
        ])->assertSessionHasErrors('pin');

        $this->post(route('portal.change-pin.post'), [
            'current_pin' => '123456',
            'pin' => '123456',
            'pin_confirmation' => '123456',
        ])->assertSessionHasErrors('pin');

        $this->assertTrue(Hash::check('123456', $this->student()->pin_portal));
    }

    /**
     * Test 5: PIN lama yang salah ditolak saat mengganti PIN.
     */
    public function test_wrong_current_pin_is_rejected(): void
    {
        $this->post(route('portal.login.post'), [
            'nis' => '202601001',
            'identifier' => '123456',
        ]);

        $this->post(route('portal.change-pin.post'), [
            'current_pin' => '999999',
            'pin' => '654321',
            'pin_confirmation' => '654321',
        ])->assertSessionHasErrors('current_pin');

        $this->assertTrue(Hash::check('123456', $this->student()->pin_portal));
    }

    /**
     * Test 6: Percobaan login berulang dibatasi (anti brute force).
     */
    public function test_repeated_failed_login_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('portal.login.post'), [
                'nis' => '202601001',
                'identifier' => '000000',
            ])->assertSessionHasErrors('identifier');
        }

        $this->post(route('portal.login.post'), [
            'nis' => '202601001',
            'identifier' => '123456',
        ])->assertSessionHasErrors('identifier');

        $this->assertNull(session('portal_student_id'));

        // Setelah jeda, percobaan dengan PIN benar tetap dapat diterima.
        $this->travel(61)->seconds();

        $this->post(route('portal.login.post'), [
            'nis' => '202601001',
            'identifier' => '123456',
        ])->assertRedirect(route('portal.dashboard'));
    }

    /**
     * Test 7: Wali dapat mengajukan permintaan reset PIN, terlihat oleh petugas.
     */
    public function test_wali_can_request_pin_reset_and_officer_can_reset_it(): void
    {
        $this->post(route('portal.forgot-pin.post'), [
            'nis' => '202601001',
            'nama_wali' => 'Bpk. Bambang Sutrisno',
        ])->assertRedirect(route('portal.login'));

        $this->assertNotNull($this->student()->fresh()->pin_reset_requested_at);

        $bendahara = User::where('role', UserRole::Bendahara->value)->firstOrFail();
        $student = $this->student();

        $this->actingAs($bendahara)
            ->post(route('admin.students.reset-pin', $student))
            ->assertRedirect();

        $student->refresh();
        $this->assertTrue(Hash::check('123456', $student->pin_portal));
        $this->assertNotSame('123456', $student->pin_portal);
        $this->assertTrue($student->must_change_pin);
        $this->assertNull($student->pin_reset_requested_at);

        $log = ActivityLog::sole();
        $this->assertSame($bendahara->id, $log->user_id);
        $this->assertSame(ActivityModule::Student, $log->module);
        $this->assertStringContainsString('Mereset PIN portal', $log->description);
    }

    /**
     * Test 8: Nomor WA harus diketik lengkap — potongan digit terakhir tidak boleh lolos.
     */
    public function test_partial_whatsapp_number_cannot_be_used_to_login(): void
    {
        foreach (['6', '3516', '253516'] as $partial) {
            $this->post(route('portal.login.post'), [
                'nis' => '202601001',
                'identifier' => $partial,
            ])->assertSessionHasErrors('identifier');
        }

        $this->assertNull(session('portal_student_id'));

        // Nomor lengkap tetap dapat digunakan.
        $this->post(route('portal.login.post'), [
            'nis' => '202601001',
            'identifier' => '085692253516',
        ])->assertRedirect(route('portal.dashboard'));

        $this->get(route('portal.dashboard'))
            ->assertRedirect(route('portal.change-pin'));
    }

    /**
     * Test 9: Santri nonaktif tidak dapat masuk ke portal.
     */
    public function test_inactive_student_cannot_login(): void
    {
        $this->student()->update(['status' => 'nonaktif']);

        $this->post(route('portal.login.post'), [
            'nis' => '202601001',
            'identifier' => '123456',
        ])->assertSessionHasErrors('nis');

        $this->assertNull(session('portal_student_id'));
    }

    /**
     * Test 10: Login via WhatsApp dapat membuat PIN baru tanpa PIN lama.
     */
    public function test_login_via_whatsapp_can_create_pin_without_current_pin(): void
    {
        $this->post(route('portal.login.post'), [
            'nis' => '202601001',
            'identifier' => '085692253516',
        ])->assertRedirect(route('portal.dashboard'));

        $this->post(route('portal.change-pin.post'), [
            'pin' => '654321',
            'pin_confirmation' => '654321',
        ])->assertRedirect(route('portal.dashboard'));

        $student = $this->student()->fresh();
        $this->assertTrue(Hash::check('654321', $student->pin_portal));
        $this->assertFalse($student->must_change_pin);
        $this->assertNull(session('portal_login_via_wa'));

        $this->get(route('portal.dashboard'))->assertOk();
    }
}
