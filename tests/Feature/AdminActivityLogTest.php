<?php

namespace Tests\Feature;

use App\Enums\ActivityAction;
use App\Enums\ActivityModule;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\PaymentCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminActivityLogTest extends TestCase
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
     * Test tamu diarahkan ke login saat membuka log aktivitas
     */
    public function test_guest_is_redirected_to_login_from_activity_log(): void
    {
        $this->get(route('admin.logs'))->assertRedirect(route('admin.login'));
    }

    /**
     * Test Bendahara tidak boleh membuka log aktivitas
     */
    public function test_bendahara_cannot_open_activity_log(): void
    {
        $this->actingAs($this->userWithRole(UserRole::Bendahara))
            ->get(route('admin.logs'))
            ->assertForbidden();
    }

    /**
     * Test Pengasuh tidak boleh membuka log aktivitas
     */
    public function test_pengasuh_cannot_open_activity_log(): void
    {
        $this->actingAs($this->userWithRole(UserRole::Pengasuh))
            ->get(route('admin.logs'))
            ->assertForbidden();
    }

    /**
     * Test Administrator dapat membuka log aktivitas
     */
    public function test_admin_can_open_activity_log(): void
    {
        $response = $this->actingAs($this->userWithRole(UserRole::Admin))->get(route('admin.logs'));

        $response->assertStatus(200);
        $response->assertSee('Log Aktivitas Sistem');
    }

    /**
     * Test pencatatan pembayaran menulis log berisi pelaku dan perubahan status tagihan
     */
    public function test_recording_payment_writes_activity_log_with_actor_and_status_change(): void
    {
        $bendahara = $this->userWithRole(UserRole::Bendahara);
        $invoice = Invoice::where('status', 'partial')->firstOrFail();

        $this->actingAs($bendahara)->post(route('admin.cashier.store'), [
            'invoice_id' => $invoice->id,
            'amount_paid' => (float) $invoice->remaining_amount,
            'payment_method' => 'cash',
            'received_from' => 'Bpk. Bambang Sutrisno (Ayah Kandung)',
            'notes' => 'Pelunasan sisa tagihan',
        ])->assertRedirect();

        $log = ActivityLog::sole();

        $this->assertSame($bendahara->id, $log->user_id);
        $this->assertSame($invoice->student_id, $log->student_id);
        $this->assertSame(ActivityModule::Payment, $log->module);
        $this->assertSame(ActivityAction::Created, $log->action);
        $this->assertStringContainsString($invoice->invoice_number, $log->description);
        $this->assertStringContainsString('Lunas', $log->properties['Status Tagihan']);
        $this->assertSame('Bpk. Bambang Sutrisno (Ayah Kandung)', $log->properties['Diterima Dari']);
        $this->assertNotNull($log->created_at);
    }

    /**
     * Test login pengurus tercatat sebagai aktivitas
     */
    public function test_admin_login_writes_activity_log(): void
    {
        $user = User::where('email', 'bendahara2@ptqamsa.id')->firstOrFail();

        $this->post(route('admin.login.post'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $log = ActivityLog::sole();

        $this->assertSame(ActivityModule::Auth, $log->module);
        $this->assertSame(ActivityAction::Login, $log->action);
        $this->assertSame($user->id, $log->user_id);
    }

    /**
     * Test generate SPP massal dicatat sebagai satu baris ringkasan
     */
    public function test_generating_monthly_spp_writes_one_summary_log(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);

        $this->actingAs($admin)->post(route('admin.generate-spp'), [
            'month' => 2,
            'year' => 2027,
        ])->assertRedirect();

        $createdInvoices = Invoice::where('period_month', 2)->where('period_year', 2027)->count();

        $this->assertGreaterThan(1, $createdInvoices);

        $log = ActivityLog::sole();

        $this->assertSame(ActivityModule::Invoice, $log->module);
        $this->assertSame($admin->id, $log->user_id);
        $this->assertStringContainsString('2027', $log->description);
        $this->assertStringContainsString((string) $createdInvoices, $log->properties['Jumlah Tagihan Diterbitkan']);
    }

    /**
     * Test penerbitan tagihan ke satu kelas dicatat sebagai satu baris ringkasan
     */
    public function test_creating_invoice_for_class_writes_one_summary_log(): void
    {
        $category = PaymentCategory::where('code', 'BUKU')->firstOrFail();

        $this->actingAs($this->userWithRole(UserRole::Bendahara))->post(route('admin.invoices.store'), [
            'payment_category_id' => $category->id,
            'target_type' => 'class',
            'target_class' => '7A',
            'title' => 'Uang Kitab Semester Ganjil',
            'amount' => 450000,
            'due_date' => '2027-01-15',
        ])->assertRedirect();

        $createdInvoices = Invoice::where('title', 'Uang Kitab Semester Ganjil')->count();

        $this->assertGreaterThan(1, $createdInvoices);

        $log = ActivityLog::sole();

        $this->assertStringContainsString('Kelas 7A', $log->description);
        $this->assertStringContainsString((string) $createdInvoices, $log->properties['Jumlah Tagihan']);
    }

    /**
     * Test log aktivitas dapat difilter berdasarkan modul dan kata kunci
     */
    public function test_activity_log_can_be_filtered_by_module_and_search(): void
    {
        ActivityLog::create([
            'module' => ActivityModule::Payment,
            'action' => ActivityAction::Created,
            'description' => 'Mencatat pembayaran uji filter.',
        ]);
        ActivityLog::create([
            'module' => ActivityModule::Auth,
            'action' => ActivityAction::Login,
            'description' => 'Login ke panel admin uji filter.',
        ]);

        $admin = $this->userWithRole(UserRole::Admin);

        $this->actingAs($admin)
            ->get(route('admin.logs', ['module' => ActivityModule::Auth->value]))
            ->assertStatus(200)
            ->assertSee('Login ke panel admin uji filter.')
            ->assertDontSee('Mencatat pembayaran uji filter.');

        $this->actingAs($admin)
            ->get(route('admin.logs', ['search' => 'pembayaran uji']))
            ->assertStatus(200)
            ->assertSee('Mencatat pembayaran uji filter.')
            ->assertDontSee('Login ke panel admin uji filter.');
    }
}
