<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLayoutTest extends TestCase
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

    public function test_admin_pages_use_sidebar_shell_with_header_components(): void
    {
        $bendahara = $this->userWithRole(UserRole::Bendahara);

        $response = $this->actingAs($bendahara)->get(route('admin.cashier'));

        $response->assertOk()
            ->assertSee('id="app-sidebar"', false)
            ->assertSee('id="quick-search-input"', false)
            ->assertSee('Cari nama santri, NIS, no. kuitansi...')
            ->assertSee('fa-bell', false)
            ->assertSee('Pusat Informasi Kasir')
            ->assertSee('Ubah Sandi / Keamanan Akun')
            ->assertSee('Log Aktivitas Kasir')
            ->assertSee(route('admin.logout'), false)
            ->assertSee('bg-blue-600 text-white font-bold', false)
            ->assertSee('Data Guru &amp; Bisyarah', false)
            ->assertSee('Riwayat Transaksi')
            ->assertDontSee('Log Aktivitas Sistem');

        // Menu aktif menunjuk ke Kas Masuk, bukan ke menu lain.
        $this->assertStringContainsString(
            'href="'.route('admin.cashier').'" aria-current="page"',
            $response->getContent(),
        );
    }

    public function test_pengasuh_sidebar_is_read_only_and_admin_sees_admin_menu(): void
    {
        $pengasuh = $this->userWithRole(UserRole::Pengasuh);

        $this->actingAs($pengasuh)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Laporan Keuangan')
            ->assertDontSee('Kas Keluar')
            ->assertDontSee('Data Santri')
            ->assertDontSee('Akun Pengurus');

        $admin = $this->userWithRole(UserRole::Admin);

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Akun Pengurus')
            ->assertSee('Log Aktivitas Sistem');
    }

    public function test_portal_and_login_pages_keep_top_navbar(): void
    {
        $this->get(route('admin.login'))
            ->assertOk()
            ->assertDontSee('id="app-sidebar"', false)
            ->assertSee('Panel Bendahara & Kasir Pesantren', false);

        $student = Student::first();
        // Santri demo mungkin masih wajib ganti PIN; halaman tujuannya tetap memakai navbar portal.
        $this->withSession(['portal_student_id' => $student->id])
            ->followingRedirects()
            ->get(route('portal.dashboard'))
            ->assertOk()
            ->assertDontSee('id="app-sidebar"', false)
            ->assertSee('Kartu Keuangan');
    }

    public function test_quick_search_groups_students_receipts_and_teachers(): void
    {
        $bendahara = $this->userWithRole(UserRole::Bendahara);
        $student = Student::where('status', 'aktif')->firstOrFail();
        $payment = Payment::with('receipt')->whereNull('voided_at')->whereHas('receipt')->firstOrFail();
        $teacher = Teacher::create([
            'nama_lengkap' => 'Ust. Zainal Pencarian',
            'kota_asal' => 'Garut',
            'no_whatsapp' => '6281234567890',
            'status' => 'guru_tetap',
            'is_active' => true,
        ]);

        // Kurang dari 2 huruf: tidak mencari.
        $this->actingAs($bendahara)
            ->getJson(route('admin.search', ['q' => 'a']))
            ->assertOk()
            ->assertJson(['groups' => []]);

        // Nama santri.
        $byName = $this->actingAs($bendahara)
            ->getJson(route('admin.search', ['q' => mb_substr($student->nama_lengkap, 0, 6)]))
            ->assertOk()
            ->json();

        $this->assertSame('students', $byName['groups'][0]['key']);
        $titles = array_column($byName['groups'][0]['items'], 'title');
        $this->assertContains($student->nama_lengkap, $titles);
        $first = collect($byName['groups'][0]['items'])->firstWhere('title', $student->nama_lengkap);
        $this->assertSame(route('admin.cashier', ['student_id' => $student->id]), $first['url']);
        $this->assertStringContainsString('NIS '.$student->nis, $first['subtitle']);

        // NIS.
        $byNis = $this->actingAs($bendahara)
            ->getJson(route('admin.search', ['q' => $student->nis]))
            ->assertOk()
            ->json();
        $this->assertSame($student->nama_lengkap, $byNis['groups'][0]['items'][0]['title']);

        // Nomor kuitansi.
        $byReceipt = $this->actingAs($bendahara)
            ->getJson(route('admin.search', ['q' => $payment->receipt->receipt_number]))
            ->assertOk()
            ->json();
        $receipts = collect($byReceipt['groups'])->firstWhere('key', 'receipts');
        $this->assertNotNull($receipts);
        $this->assertSame($payment->receipt->receipt_number, $receipts['items'][0]['title']);
        $this->assertStringContainsString('Rp '.number_format((float) $payment->amount_paid, 0, ',', '.'), $receipts['items'][0]['meta']);
        $this->assertStringContainsString(route('admin.transactions'), $receipts['items'][0]['url']);

        // Nama guru beserta status bisyarah bulan ini.
        $byTeacher = $this->actingAs($bendahara)
            ->getJson(route('admin.search', ['q' => 'Zainal']))
            ->assertOk()
            ->json();
        $teachers = collect($byTeacher['groups'])->firstWhere('key', 'teachers');
        $this->assertNotNull($teachers);
        $this->assertSame($teacher->nama_lengkap, $teachers['items'][0]['title']);
        $this->assertStringContainsString('belum dicatat', $teachers['items'][0]['meta']);
    }

    public function test_quick_search_for_pengasuh_points_to_reports_and_hides_teachers(): void
    {
        $pengasuh = $this->userWithRole(UserRole::Pengasuh);
        $student = Student::where('status', 'aktif')->firstOrFail();

        // Tanpa login, pencarian diarahkan ke halaman login.
        $this->get(route('admin.search', ['q' => $student->nis]))->assertRedirect(route('admin.login'));

        Teacher::create([
            'nama_lengkap' => 'Ust. '.$student->nama_lengkap,
            'kota_asal' => 'Garut',
            'no_whatsapp' => '6281234567890',
            'status' => 'guru_tetap',
            'is_active' => true,
        ]);

        $result = $this->actingAs($pengasuh)
            ->getJson(route('admin.search', ['q' => $student->nis]))
            ->assertOk()
            ->json();

        $this->assertSame('students', $result['groups'][0]['key']);
        $this->assertStringContainsString(route('admin.reports.arrears'), $result['groups'][0]['items'][0]['url']);
        $this->assertNull(collect($result['groups'])->firstWhere('key', 'teachers'));
    }

    public function test_notification_center_counts_overdue_invoices(): void
    {
        $bendahara = $this->userWithRole(UserRole::Bendahara);

        $overdue = Invoice::whereIn('status', ['unpaid', 'partial'])
            ->whereDate('due_date', '<', now()->startOfDay())
            ->count();

        $response = $this->actingAs($bendahara)->get(route('admin.dashboard'));
        $response->assertOk();

        if ($overdue > 0) {
            $response->assertSee($overdue.' tagihan lewat jatuh tempo');
        } else {
            $response->assertDontSee('tagihan lewat jatuh tempo');
        }
    }
}
