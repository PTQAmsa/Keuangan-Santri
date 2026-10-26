<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentCategory;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRevisionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function admin(): User
    {
        return User::where('role', UserRole::Admin->value)->firstOrFail();
    }

    private function bendahara(): User
    {
        return User::where('role', UserRole::Bendahara->value)->firstOrFail();
    }

    /**
     * Navigasi utama: Data Santri ada di kanan Laporan, halaman tagihan hanya
     * punya satu tombol aksi, dan teks deskripsi sudah diperbarui.
     */
    public function test_admin_navigation_and_invoice_page_actions_are_tidy(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.invoices'));

        $response->assertOk();
        $response->assertSeeInOrder(['Laporan', 'Data Santri'], false);
        $response->assertSee('Terbitkan Tagihan Baru');
        $response->assertSee('Iuran Kondisional)', false);
        $response->assertDontSee('Iuran Kondisional/Rihlah', false);
        $response->assertDontSee('fa-cash-register mr-1.5 text-gold-400"></i> Meja Kasir', false);
        $response->assertDontSee('Data Santri (Masuk / Keluar)', false);
    }

    /**
     * Tombol kembali yang diminta dihapus tidak boleh muncul lagi.
     */
    public function test_removed_back_buttons_are_gone(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.expenses'))
            ->assertOk()
            ->assertDontSee('Kembali ke Laporan Kas');

        $this->actingAs($admin)->get(route('admin.transactions'))
            ->assertOk()
            ->assertDontSee('Daftar Tagihan');

        $this->actingAs($admin)->get(route('admin.students'))
            ->assertOk()
            ->assertDontSee('Kembali ke Tagihan');
    }

    /**
     * Kasir memakai tiga dropdown bertingkat dan label metode baru.
     */
    public function test_cashier_uses_staged_student_selector_and_new_method_labels(): void
    {
        $student = Student::where('nis', '202601001')->firstOrFail();

        $response = $this->actingAs($this->bendahara())->get(route('admin.cashier', [
            'student_id' => $student->id,
        ]));

        $response->assertOk();
        $response->assertSee('1. Marhalah');
        $response->assertSee('2. Kelas');
        $response->assertSee('3. Nama Santri');
        $response->assertSee('Transfer Bank BSI (Rekening Pesantren)');
        $response->assertSee('Tunai / Cash');
        $response->assertDontSee('Transfer Bank (BSI / Mandiri / dll)');
        $response->assertDontSee('Tunai / Cash (Kasir Kantor)');
    }

    /**
     * Kas keluar: label Bisyarah, metode Tunai, dan seluruh judul kolom bisa diurutkan.
     */
    public function test_expense_page_has_bisyarah_label_and_sortable_headers(): void
    {
        $response = $this->actingAs($this->bendahara())->get(route('admin.expenses'));

        $response->assertOk();
        $response->assertSee('Bisyarah');
        $response->assertSee('Tunai');

        foreach (['expense_date', 'expense_code', 'category', 'description', 'amount', 'payment_method', 'recipient', 'recorded_by', 'status'] as $column) {
            $response->assertSee('sort_by='.$column, false);
        }

        // Urutan benar-benar berubah ketika parameter sortir dikirim.
        $sorted = $this->actingAs($this->bendahara())
            ->get(route('admin.expenses', [
                'sort_by' => 'expense_code',
                'sort_order' => 'asc',
                'start_date' => '2026-10-01',
                'end_date' => '2026-10-31',
            ]));
        $sorted->assertOk();
        $sorted->assertSeeInOrder(['EXP-202610-0001', 'EXP-202610-0002'], false);
    }

    /**
     * Kuota koreksi tagihan maksimal dua kali dan pesannya jelas.
     */
    public function test_invoice_can_only_be_edited_twice(): void
    {
        $admin = $this->admin();
        $invoice = Invoice::where('invoice_number', 'INV-202607-0002')->firstOrFail();

        $payload = fn (string $title) => [
            'title' => $title,
            'amount' => (float) $invoice->amount,
            'due_date' => $invoice->due_date->format('Y-m-d'),
        ];

        $this->actingAs($admin)->patch(route('admin.invoices.update', $invoice), $payload('Koreksi pertama'))
            ->assertSessionHasNoErrors();
        $this->actingAs($admin)->patch(route('admin.invoices.update', $invoice), $payload('Koreksi kedua'))
            ->assertSessionHasNoErrors();

        $invoice->refresh();
        $this->assertSame(2, $invoice->edit_count);
        $this->assertSame('Koreksi kedua', $invoice->title);

        $this->actingAs($admin)->patch(route('admin.invoices.update', $invoice), $payload('Koreksi ketiga'))
            ->assertSessionHasErrors('invoice');

        $this->assertSame('Koreksi kedua', $invoice->fresh()->title);
        $this->assertSame(2, $invoice->fresh()->edit_count);
    }

    /**
     * Laporan kas default memakai satu bulan penuh dan menampilkan rentang
     * tanggal pada keempat kartu ringkasan.
     */
    public function test_cash_report_defaults_to_full_month_and_shows_range_on_every_card(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.reports.cash'));

        $response->assertOk();
        $response->assertViewHas('endDate', now()->endOfMonth()->toDateString());

        $label = now()->startOfMonth()->format('d M').' - '.now()->endOfMonth()->format('d M Y');
        $this->assertSame(4, substr_count($response->getContent(), $label));
    }

    /**
     * Perbaikan nominal pembayaran langsung terlihat pada laporan kas.
     */
    public function test_payment_correction_stays_in_sync_with_cash_report(): void
    {
        $bendahara = $this->bendahara();
        $payment = Payment::where('payment_code', 'PAY-202607-0003')->firstOrFail();
        $beforeTotal = (float) Payment::whereNull('voided_at')->sum('amount_paid');

        $this->actingAs($bendahara)->patch(route('admin.transactions.update', $payment), [
            'amount_paid' => 1000000,
            'payment_method' => 'cash',
            'payment_date' => '2026-07-20',
            'received_from' => $payment->received_from,
            'notes' => 'Koreksi',
        ])->assertRedirect();

        $this->assertEquals($beforeTotal - 1500000, (float) Payment::whereNull('voided_at')->sum('amount_paid'));

        $julyTotal = (float) Payment::whereNull('voided_at')
            ->whereDate('payment_date', '>=', '2026-07-01')
            ->whereDate('payment_date', '<=', '2026-07-31')
            ->sum('amount_paid');

        $report = $this->actingAs($bendahara)->get(route('admin.reports.cash', [
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
        ]));

        $report->assertOk();
        $report->assertViewHas('totalPenerimaan', $julyTotal);
        $report->assertSee('Rp 1.000.000');
    }

    /**
     * Kuitansi PDF hanya menampilkan tanggal unduh, bukan tanggal terbit.
     */
    public function test_receipt_pdf_only_shows_download_date(): void
    {
        $payment = Payment::with(['invoice.category', 'student', 'receipt', 'recordedBy'])
            ->where('payment_code', 'PAY-202607-0001')
            ->firstOrFail();

        $html = view('pdf.receipt', [
            'receipt' => $payment->receipt,
            'payment' => $payment,
            'invoice' => $payment->invoice,
            'student' => $payment->student,
            'terbilang' => 'Dua Ratus Ribu Rupiah',
        ])->render();

        $issueDate = $payment->receipt->issued_at->translatedFormat('d F Y');

        $this->assertStringNotContainsString($issueDate, $html);
        $this->assertStringNotContainsString('Tanggal Terbit', $html);
        $this->assertStringContainsString('Diunduh:', $html);
        $this->assertStringContainsString(now()->format('d/m/Y'), $html);
    }

    /**
     * Panel wali: tanpa Beranda Utama di login, wakaf tanpa jatuh tempo,
     * serta Kartu Keuangan dan Profil tersedia di navbar.
     */
    public function test_guardian_portal_navigation_wakaf_due_date_and_profile(): void
    {
        $this->get(route('portal.login'))
            ->assertOk()
            ->assertDontSee('Beranda Utama');

        $student = Student::where('nis', '202601001')->firstOrFail();
        $student->must_change_pin = false;
        $student->save();

        // Sisakan satu tagihan wakaf aktif agar due date-nya bisa diperiksa.
        Invoice::where('student_id', $student->id)->whereIn('status', ['unpaid', 'partial'])->get()
            ->each(function (Invoice $invoice) {
                $invoice->status = 'paid';
                $invoice->paid_amount = $invoice->amount;
                $invoice->save();
            });

        $wakaf = Invoice::create([
            'invoice_number' => 'INV-TEST-WAKAF-PORTAL',
            'student_id' => $student->id,
            'payment_category_id' => PaymentCategory::where('code', 'WAKAF')->firstOrFail()->id,
            'title' => 'Wakaf Pembangunan Gedung',
            'amount' => 5000000,
            'paid_amount' => 0,
            'due_date' => '2026-12-31',
            'status' => 'unpaid',
        ]);

        $dashboard = $this->withSession(['portal_student_id' => $student->id])
            ->get(route('portal.dashboard'));

        $dashboard->assertOk();
        $dashboard->assertSee('Pembayaran wakaf berlaku selama ananda menjadi santri');
        $dashboard->assertDontSee('Jatuh Tempo: '.$wakaf->due_date->format('d M Y'));
        $dashboard->assertSee('Kartu Keuangan');
        $dashboard->assertSee('> Profil', false);
        $dashboard->assertDontSee('fa-lock mr-1"></i> Ganti PIN', false);

        $profile = $this->withSession(['portal_student_id' => $student->id])
            ->get(route('portal.profile'));

        $profile->assertOk();
        $profile->assertSee('Identitas Santri');
        $profile->assertSee('Data Wali Santri');
        $profile->assertSee('Ganti PIN');
        $profile->assertDontSee('Profil ini hanya dapat dilihat');
    }
}
