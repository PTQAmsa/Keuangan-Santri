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

        // Menu utama Kas Masuk terbuka dan submenu Kasir Santri tersorot.
        $this->assertMatchesRegularExpression(
            '/href="'.preg_quote(route('admin.cashier'), '/').'"\s+role="menuitem"\s+aria-current="page"/',
            $response->getContent(),
        );
        $this->assertStringContainsString('data-submenu class="is-open"', $response->getContent());
    }

    public function test_pengasuh_sees_all_bendahara_menus_read_only_and_admin_sees_admin_menu(): void
    {
        $pengasuh = $this->userWithRole(UserRole::Pengasuh);

        $this->actingAs($pengasuh)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Mode lihat saja')
            ->assertSee('readonly-mode', false)
            ->assertSee('Laporan Keuangan')
            ->assertSee('Kas Keluar')
            ->assertSee('Data Santri')
            ->assertSee('Bisyarah Bulanan')
            ->assertDontSee('Tambah Santri Baru')
            ->assertDontSee('Buat Tagihan Baru')
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

    public function test_quick_search_requires_login_and_works_for_pengasuh(): void
    {
        $pengasuh = $this->userWithRole(UserRole::Pengasuh);
        $student = Student::where('status', 'aktif')->firstOrFail();

        // Tanpa login, pencarian diarahkan ke halaman login.
        $this->get(route('admin.search', ['q' => $student->nis]))->assertRedirect(route('admin.login'));

        $result = $this->actingAs($pengasuh)
            ->getJson(route('admin.search', ['q' => $student->nis]))
            ->assertOk()
            ->json();

        $this->assertSame('students', $result['groups'][0]['key']);
        $this->assertSame(route('admin.cashier', ['student_id' => $student->id]), $result['groups'][0]['items'][0]['url']);

        // Halaman tujuan terbuka dalam mode lihat saja.
        $this->actingAs($pengasuh)
            ->get($result['groups'][0]['items'][0]['url'])
            ->assertOk()
            ->assertSee('Mode lihat saja');
    }

    public function test_sidebar_submenus_open_target_pages_and_modals(): void
    {
        $bendahara = $this->userWithRole(UserRole::Bendahara);

        $dashboard = $this->actingAs($bendahara)->get(route('admin.dashboard'));
        $dashboard->assertOk()
            ->assertSee('data-submenu-toggle', false)
            ->assertSee('Kasir Santri')
            ->assertSee('Pemasukan Lainnya')
            ->assertSee('Buat Tagihan Baru')
            ->assertSee('Terbitkan SPP Bulanan')
            ->assertSee('Transaksi Dibatalkan')
            ->assertSee('Catat Pengeluaran')
            ->assertSee('Arus Kas')
            ->assertSee('Rekap Tunggakan')
            ->assertSee('Tutup Buku')
            ->assertSee('Tambah Santri Baru')
            ->assertSee('Tambah Guru')
            ->assertSee('Bisyarah Bulanan')
            ->assertSee('id="generate-spp"', false);

        // Submenu Laporan tersorot saat halaman arus kas dibuka.
        $cashFlow = $this->actingAs($bendahara)->get(route('admin.reports.cash-flow'));
        $cashFlow->assertOk();
        $this->assertMatchesRegularExpression(
            '/href="'.preg_quote(route('admin.reports.cash-flow'), '/').'"\s+role="menuitem"\s+aria-current="page"/',
            $cashFlow->getContent(),
        );

        // Tautan "Tambah ..." membuka modal secara otomatis.
        $this->actingAs($bendahara)->get(route('admin.students', ['tambah' => 1]))
            ->assertOk()
            ->assertSee("document.addEventListener('DOMContentLoaded', openStudentModal);", false);

        $this->actingAs($bendahara)->get(route('admin.invoices', ['tambah' => 1]))
            ->assertOk()
            ->assertSee("document.addEventListener('DOMContentLoaded', openInvoiceModal);", false);

        $this->actingAs($bendahara)->get(route('admin.teachers', ['tambah' => 1]))
            ->assertOk()
            ->assertSee('const context = "add";', false);

        $this->actingAs($bendahara)->get(route('admin.expenses'))
            ->assertOk()
            ->assertSee('id="catat"', false);
    }

    public function test_landing_page_has_new_navbar_hero_and_quick_check(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Masuk Portal Wali Santri')
            ->assertSee('Login Pengurus / Bendahara')
            ->assertSee('Sistem Keuangan &amp; Tagihan Santri Terpadu', false)
            ->assertSee('Masukkan NIS / No. WhatsApp Santri')
            ->assertSee('Cek Tagihan / Masuk')
            ->assertSee('Total Paket Iuran Bulanan')
            ->assertSee('Rp 1.350.000')
            ->assertSee('Skema Cicilan &amp; Beasiswa', false)
            ->assertSee('Kuitansi Digital PDF')
            ->assertSee('Notifikasi WhatsApp Gateway')
            ->assertSee('+62 856-9225-3516')
            ->assertSee('www.ptqamsa.id')
            ->assertDontSee('id="app-sidebar"', false);

        // Isian cepat diteruskan ke portal: NIS ke kolom NIS, nomor WA ke kolom identitas.
        $this->get(route('portal.login', ['q' => 'ABC123']))
            ->assertOk()
            ->assertSee('name="nis" id="nis" value="ABC123"', false);

        $this->get(route('portal.login', ['q' => '081234567890']))
            ->assertOk()
            ->assertSee('name="identifier" id="identifier" value="081234567890"', false);
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
