<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TableSortingTest extends TestCase
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

    private function makeTeacher(string $name, string $status): Teacher
    {
        return Teacher::create([
            'nama_lengkap' => $name,
            'kota_asal' => 'Bogor',
            'no_whatsapp' => '62812'.random_int(1000000, 9999999),
            'status' => $status,
            'is_active' => true,
        ]);
    }

    public function test_student_table_sorts_by_clicked_column(): void
    {
        $bendahara = $this->userWithRole(UserRole::Bendahara);
        $names = Student::where('status', 'aktif')->pluck('nama_lengkap');

        $asc = $this->actingAs($bendahara)->get(route('admin.students', ['status' => 'aktif', 'sort_by' => 'student', 'sort_order' => 'asc']));
        $asc->assertOk()
            ->assertSee('sort_by=student&amp;sort_order=desc', false)
            ->assertSee('aria-sort="ascending"', false)
            ->assertSeeInOrder([$names->sort()->first(), $names->sort()->last()]);

        $desc = $this->actingAs($bendahara)->get(route('admin.students', ['status' => 'aktif', 'sort_by' => 'student', 'sort_order' => 'desc']));
        $desc->assertOk()
            ->assertSee('aria-sort="descending"', false)
            ->assertSeeInOrder([$names->sort()->last(), $names->sort()->first()]);

        // Kolom tak dikenal jatuh ke urutan bawaan tanpa error.
        $this->actingAs($bendahara)->get(route('admin.students', ['sort_by' => 'hacker']))->assertOk();
        $this->actingAs($bendahara)->get(route('admin.students', ['sort_by' => 'sisa_tagihan', 'sort_order' => 'desc']))->assertOk();
    }

    public function test_teacher_and_bisyarah_tables_sort_by_name_and_status(): void
    {
        $bendahara = $this->userWithRole(UserRole::Bendahara);
        $this->makeTeacher('Ust. Zaid', 'staf');
        $this->makeTeacher('Ust. Amir', 'pengasuh');

        $this->actingAs($bendahara)->get(route('admin.teachers', ['sort_by' => 'teacher', 'sort_order' => 'desc']))
            ->assertOk()
            ->assertSeeInOrder(['Ust. Zaid', 'Ust. Amir']);

        $this->actingAs($bendahara)->get(route('admin.teachers', ['sort_by' => 'status', 'sort_order' => 'asc']))
            ->assertOk()
            ->assertSeeInOrder(['Ust. Amir', 'Ust. Zaid']);

        $this->actingAs($bendahara)->get(route('admin.bisyarah', ['sort_by' => 'teacher', 'sort_order' => 'desc']))
            ->assertOk()
            ->assertSee('aria-sort="descending"', false)
            ->assertSeeInOrder(['Ust. Zaid', 'Ust. Amir']);
    }

    public function test_account_log_and_closing_tables_are_sortable(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);

        $this->actingAs($admin)->get(route('admin.accounts', ['sort_by' => 'email', 'sort_order' => 'asc']))
            ->assertOk()
            ->assertSee('aria-sort="ascending"', false)
            ->assertSeeInOrder(['admin@ptqamsa.id', 'bendahara1@ptqamsa.id', 'bendahara2@ptqamsa.id']);

        $this->actingAs($admin)->get(route('admin.accounts', ['sort_by' => 'email', 'sort_order' => 'desc']))
            ->assertOk()
            ->assertSeeInOrder(['yayasan@ptqamsa.id', 'bendahara1@ptqamsa.id', 'admin@ptqamsa.id']);

        $this->actingAs($admin)->get(route('admin.logs', ['sort_by' => 'user', 'sort_order' => 'asc', 'start_date' => now()->subYear()->toDateString()]))
            ->assertOk()
            ->assertSee('sort_by=user&amp;sort_order=desc', false);

        $this->actingAs($admin)->get(route('admin.reports.closing', ['sort_by' => 'month', 'sort_order' => 'desc']))
            ->assertOk()
            ->assertSee('aria-sort="descending"', false)
            ->assertSeeInOrder(['Desember', 'Januari']);

        $this->actingAs($admin)->get(route('admin.reports.closing', ['sort_by' => 'in', 'sort_order' => 'desc']))
            ->assertOk();
    }

    public function test_back_to_top_button_and_client_side_sorting_are_present_everywhere(): void
    {
        // Halaman publik dulu, sebelum login pengurus (halaman login menolak pengguna yang sudah masuk).
        $this->get('/')->assertOk()->assertSee('id="back-to-top"', false);
        $this->get(route('admin.login'))->assertOk()->assertSee('id="back-to-top"', false);

        $bendahara = $this->userWithRole(UserRole::Bendahara);

        $this->actingAs($bendahara)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('id="back-to-top"', false)
            ->assertSee('table[data-sortable]', false);

        $student = Student::where('status', 'aktif')->firstOrFail();
        $student->forceFill(['must_change_pin' => false])->save();

        $this->withSession(['portal_student_id' => $student->id])
            ->get(route('portal.dashboard'))
            ->assertOk()
            ->assertSee('id="back-to-top"', false)
            ->assertSee('<table data-sortable', false)
            ->assertSee('data-nosort', false);
    }
}
