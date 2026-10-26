<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use App\Services\StudentFinancialCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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

    private function loginAsWali(): void
    {
        $this->post(route('portal.login.post'), [
            'nis' => '202601001',
            'identifier' => '085692253516',
        ]);

        // Login via WhatsApp wajib membuat PIN baru dulu.
        $this->post(route('portal.change-pin.post'), [
            'pin' => '654321',
            'pin_confirmation' => '654321',
        ]);
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

    public function test_card_offers_one_pdf_per_table_and_puts_buku_kas_last(): void
    {
        $student = $this->student();
        $html = $this->actingAs($this->userWithRole(UserRole::Bendahara))
            ->get(route('admin.students.card', $student))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(route('admin.students.card.invoices.pdf', $student), $html);
        $this->assertStringContainsString(route('admin.students.card.payments.pdf', $student), $html);
        $this->assertStringContainsString('Unduh Daftar Tagihan (PDF)', $html);
        $this->assertStringContainsString('Unduh Riwayat Pembayaran (PDF)', $html);
        $this->assertStringContainsString('Daftar Tagihan & Sisa Kewajiban', $html);
        $this->assertStringContainsString('bukan bukti pembayaran', $html);
        $this->assertStringContainsString('adalah bukti pembayaran', $html);

        $posTagihan = strpos($html, '> Daftar Tagihan');
        $posPembayaran = strpos($html, '> Riwayat Pembayaran & Kuitansi');
        $posBukuKas = strpos($html, '> Buku Kas Santri');

        $this->assertNotFalse($posTagihan);
        $this->assertNotFalse($posPembayaran);
        $this->assertNotFalse($posBukuKas);
        $this->assertLessThan($posPembayaran, $posTagihan);
        $this->assertLessThan($posBukuKas, $posPembayaran, 'Buku kas harus berada di urutan paling bawah.');
    }

    public function test_admin_can_download_invoice_and_payment_pdfs(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);

        foreach (['admin.students.card.invoices.pdf', 'admin.students.card.payments.pdf'] as $routeName) {
            $this->actingAs($admin)
                ->get(route($routeName, $this->student()))
                ->assertOk()
                ->assertHeader('content-type', 'application/pdf');
        }
    }

    public function test_pengasuh_can_open_student_financial_card_read_only(): void
    {
        $this->actingAs($this->userWithRole(UserRole::Pengasuh))
            ->get(route('admin.students.card', $this->student()))
            ->assertOk()
            ->assertSee('readonly-mode', false);
    }

    public function test_ledger_running_balance_matches_visible_arrears(): void
    {
        $data = app(StudentFinancialCardService::class)->build($this->student());

        $this->assertNotEmpty($data['ledger']);
        $this->assertEqualsWithDelta(
            $data['totalTunggakanBulanBerjalan'],
            $data['ledger']->last()['balance'],
            0.01
        );
        $this->assertTrue($data['ledger']->every(fn (array $entry) => $entry['balance'] >= 0));
    }

    public function test_arrears_shown_to_wali_stop_at_current_month_except_wakaf(): void
    {
        $data = app(StudentFinancialCardService::class)->build(
            $this->student(),
            Carbon::parse('2026-10-20')
        );

        // Tagihan November 2026–Januari 2027 belum boleh terasa membebani,
        // sementara sisa wakaf Rp 2.500.000 tetap dihitung.
        $this->assertEqualsWithDelta(2_500_000, $data['totalTunggakanBulanBerjalan'], 0.01);
        $this->assertEqualsWithDelta(6_550_000, $data['totalTunggakan'], 0.01);
        $this->assertSame('Oktober 2026', $data['batasTunggakanLabel']);
    }

    public function test_invoice_pdf_is_limited_to_current_semester_and_month(): void
    {
        $document = app(StudentFinancialCardService::class)->buildInvoicesDocument(
            $this->student(),
            Carbon::parse('2026-10-20')
        );

        $numbers = $document['invoices']->pluck('invoice_number');

        $this->assertSame('Semester Ganjil 2026/2027', $document['semester']['label']);
        $this->assertTrue($numbers->contains('INV-202607-0003'), 'Wakaf di semester ini harus ikut.');
        $this->assertFalse($numbers->contains('INV-202611-0001'), 'Tagihan bulan depan tidak boleh masuk PDF.');
        $this->assertFalse($numbers->contains('INV-202612-0001'));
        $this->assertFalse($numbers->contains('INV-202701-0001'), 'Tagihan semester berikutnya tidak boleh masuk PDF.');
        $this->assertEqualsWithDelta(2_500_000, $document['totalTunggakan'], 0.01);
    }

    public function test_payment_pdf_is_limited_to_current_semester(): void
    {
        $student = $this->student();
        $bendahara = $this->userWithRole(UserRole::Bendahara);

        $oldInvoice = Invoice::create([
            'invoice_number' => 'INV-202512-9999',
            'student_id' => $student->id,
            'payment_category_id' => $student->invoices()->firstOrFail()->payment_category_id,
            'title' => 'Tagihan Lama Semester Lalu',
            'amount' => 100_000,
            'paid_amount' => 100_000,
            'due_date' => '2025-12-10',
            'status' => 'paid',
            'period_month' => 12,
            'period_year' => 2025,
        ]);

        Payment::create([
            'payment_code' => 'PAY-202512-9999',
            'invoice_id' => $oldInvoice->id,
            'student_id' => $student->id,
            'amount_paid' => 100_000,
            'payment_date' => '2025-12-15',
            'payment_method' => 'cash',
            'received_from' => 'Wali Santri',
            'recorded_by' => $bendahara->id,
        ]);

        $document = app(StudentFinancialCardService::class)->buildPaymentsDocument(
            $student,
            Carbon::parse('2026-10-20')
        );

        $codes = $document['payments']->pluck('payment_code');

        $this->assertTrue($codes->contains('PAY-202610-0001'));
        $this->assertFalse($codes->contains('PAY-202512-9999'), 'Pembayaran semester lalu tidak boleh masuk PDF.');
        $this->assertSame('Semester Ganjil 2026/2027', $document['semester']['label']);
    }

    public function test_invoice_and_payment_pdf_documents_share_period_and_cross_reference(): void
    {
        $student = $this->student();
        $service = app(StudentFinancialCardService::class);
        $asOf = Carbon::parse('2026-10-20');

        $invoiceDocument = $service->buildInvoicesDocument($student, $asOf);
        $paymentDocument = $service->buildPaymentsDocument($student, $asOf);

        $this->assertSame('2026-10-31', $invoiceDocument['periodEnd']->toDateString());
        $this->assertSame(
            $invoiceDocument['periodEnd']->toDateString(),
            $paymentDocument['periodEnd']->toDateString(),
            'Kedua PDF harus memakai batas tanggal yang sama.'
        );

        $invoiceHtml = view('pdf.student-document', $invoiceDocument)->render();
        $paymentHtml = view('pdf.student-document', $paymentDocument)->render();

        $this->assertStringContainsString('Daftar Tagihan &amp; Sisa Kewajiban', $invoiceHtml);
        $this->assertStringContainsString('Dokumen ini adalah daftar tagihan, bukan bukti pembayaran.', $invoiceHtml);
        $this->assertStringContainsString('Kuitansi: KWT-202607-0001', $invoiceHtml);

        $this->assertStringContainsString('Dokumen ini adalah bukti pembayaran.', $paymentHtml);
        $this->assertStringContainsString('INV-202601-0001', $paymentHtml);
    }

    public function test_all_pdf_templates_use_the_same_official_tagline(): void
    {
        $templates = glob(resource_path('views/pdf/*.blade.php'));

        $this->assertNotEmpty($templates);

        foreach ($templates as $template) {
            $content = file_get_contents($template);
            $name = basename($template);

            $this->assertStringContainsString(
                "Satuan Pendidikan Muadalah Mu'allimien | Putra-Putri",
                $content,
                "Kop PDF {$name} belum memakai tagline resmi."
            );
            $this->assertStringNotContainsString(
                'Program 6 Tahun',
                $content,
                "Kop PDF {$name} masih memakai tagline lama."
            );
        }
    }

    public function test_wali_can_view_own_financial_card_after_login(): void
    {
        $this->loginAsWali();

        $response = $this->get(route('portal.card'));

        $response->assertOk();
        $response->assertSee('Muhammad Salman');
        $response->assertSee('INV-202601-0001');
        $response->assertSee('KWT-202607-0001');
        $response->assertDontSee('Zaid Abdullah Al-Qurthubi');
    }

    public function test_wali_can_download_own_invoice_and_payment_pdfs_after_login(): void
    {
        $this->loginAsWali();

        foreach (['portal.card.invoices.pdf', 'portal.card.payments.pdf'] as $routeName) {
            $this->get(route($routeName))
                ->assertOk()
                ->assertHeader('content-type', 'application/pdf');
        }
    }

    public function test_portal_dashboard_hides_future_month_arrears_except_wakaf(): void
    {
        $this->loginAsWali();

        $response = $this->get(route('portal.dashboard'));

        $response->assertOk();
        $response->assertViewHas('totalTunggakan', fn ($value) => abs($value - 2_500_000) < 0.01);
        $response->assertViewHas('activeInvoices', function ($invoices) {
            return $invoices->count() === 1
                && str_contains((string) $invoices->first()->title, 'Wakaf');
        });
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
