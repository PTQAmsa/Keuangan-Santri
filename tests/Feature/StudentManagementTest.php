<?php

namespace Tests\Feature;

use App\Enums\ActivityAction;
use App\Enums\ActivityModule;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        // Kosongkan log bawaan seeder agar setiap test hanya menghitung aksinya sendiri.
        ActivityLog::query()->delete();
    }

    private function userWithRole(UserRole $role): User
    {
        return User::where('role', $role->value)->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'nis' => '202601099',
            'nama_lengkap' => 'Ahmad Fauzan',
            'jenis_kelamin' => 'L',
            'santri_group' => 'putra',
            'kelas' => '7C',
            'tahun_masuk' => 2026,
            'status' => 'aktif',
            'nama_wali' => 'Bpk. Ahmad Subandi',
            'no_whatsapp_wali' => '081234567890',
            'hubungan_wali' => 'Ayah Kandung',
            'pin_portal' => '654321',
            'diskon_beasiswa' => 0,
        ], $overrides);
    }

    /**
     * Test 1: Bendahara dapat menambah santri baru dan aksinya tercatat di log
     */
    public function test_bendahara_can_add_new_student_and_it_is_logged(): void
    {
        $bendahara = $this->userWithRole(UserRole::Bendahara);

        $response = $this->actingAs($bendahara)
            ->post(route('admin.students.store'), $this->validPayload());

        $response->assertRedirect(route('admin.students'));
        $response->assertSessionHas('success');

        $student = Student::where('nis', '202601099')->firstOrFail();
        $this->assertSame('Ahmad Fauzan', $student->nama_lengkap);
        $this->assertSame('aktif', $student->status);
        $this->assertFalse($student->bebas_wakaf);

        $log = ActivityLog::sole();
        $this->assertSame($bendahara->id, $log->user_id);
        $this->assertSame(ActivityModule::Student, $log->module);
        $this->assertSame(ActivityAction::Created, $log->action);
        $this->assertSame($student->id, $log->student_id);
        $this->assertStringContainsString('Ahmad Fauzan', $log->description);
        $this->assertSame('202601099', $log->properties['NIS']);
        $this->assertSame('Aktif', $log->properties['Status']);
    }

    /**
     * Test 2: Administrator dapat menonaktifkan santri (santri keluar) dan tercatat di log
     */
    public function test_admin_can_deactivate_student_and_it_is_logged(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        $student = Student::where('status', 'aktif')->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('admin.students.toggle', $student))
            ->assertRedirect();

        $this->assertSame('nonaktif', $student->fresh()->status);

        $log = ActivityLog::sole();
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame(ActivityModule::Student, $log->module);
        $this->assertSame(ActivityAction::Updated, $log->action);
        $this->assertSame('Aktif → Nonaktif (Keluar)', $log->properties['Status']);
        $this->assertStringContainsString('Nonaktif', $log->description);
    }

    /**
     * Test 3: Santri nonaktif dapat diaktifkan kembali
     */
    public function test_deactivated_student_can_be_activated_again(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        $student = Student::where('status', 'aktif')->firstOrFail();
        $student->update(['status' => 'nonaktif']);

        $this->actingAs($admin)
            ->patch(route('admin.students.toggle', $student))
            ->assertRedirect();

        $this->assertSame('aktif', $student->fresh()->status);
        $this->assertSame('Nonaktif (Keluar) → Aktif', ActivityLog::sole()->properties['Status']);
    }

    /**
     * Test 4: Santri nonaktif tidak lagi dibuatkan tagihan SPP bulanan
     */
    public function test_inactive_student_does_not_receive_monthly_spp(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        $student = Student::where('status', 'aktif')->firstOrFail();
        $student->update(['status' => 'nonaktif']);

        $this->actingAs($admin)
            ->post(route('admin.generate-spp'), ['month' => 5, 'year' => 2029])
            ->assertRedirect();

        $this->assertDatabaseMissing('invoices', [
            'student_id' => $student->id,
            'period_month' => 5,
            'period_year' => 2029,
        ]);

        // Santri lain yang masih aktif tetap mendapat tagihan.
        $this->assertDatabaseHas('invoices', [
            'student_id' => Student::where('status', 'aktif')->firstOrFail()->id,
            'period_month' => 5,
            'period_year' => 2029,
        ]);
    }

    /**
     * Test 5: Pengasuh (read-only) tidak boleh mengelola data santri
     */
    public function test_pengasuh_cannot_manage_students(): void
    {
        $pengasuh = $this->userWithRole(UserRole::Pengasuh);

        $this->actingAs($pengasuh)
            ->get(route('admin.students'))
            ->assertForbidden();

        $this->actingAs($pengasuh)
            ->post(route('admin.students.store'), $this->validPayload())
            ->assertForbidden();

        $this->actingAs($pengasuh)
            ->patch(route('admin.students.toggle', Student::firstOrFail()))
            ->assertForbidden();
    }

    /**
     * Test 6: Bendahara dapat membuka halaman data santri
     */
    public function test_bendahara_can_open_student_page(): void
    {
        $response = $this->actingAs($this->userWithRole(UserRole::Bendahara))
            ->get(route('admin.students'));

        $response->assertOk();
        $response->assertSee('Data Santri');
        $response->assertSee('Tambah Santri Baru');
    }

    /**
     * Test 7: NIS ganda ditolak
     */
    public function test_duplicate_nis_is_rejected(): void
    {
        $this->actingAs($this->userWithRole(UserRole::Bendahara))
            ->post(route('admin.students.store'), $this->validPayload(['nis' => '202601001']))
            ->assertSessionHasErrors('nis');

        $this->assertSame(1, Student::where('nis', '202601001')->count());
        $this->assertSame(0, ActivityLog::count());
    }
}
