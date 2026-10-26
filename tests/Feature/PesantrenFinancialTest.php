<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentCategory;
use App\Models\Receipt;
use App\Models\Student;
use App\Models\User;
use App\Services\ReceiptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PesantrenFinancialTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Test 1: Wali santri dapat login ke portal menggunakan NIS dan No WA
     */
    public function test_wali_santri_can_login_to_portal_with_nis_and_whatsapp(): void
    {
        $response = $this->post(route('portal.login.post'), [
            'nis' => '202601001',
            'identifier' => '085692253516',
        ]);

        $response->assertRedirect(route('portal.dashboard'));
        $this->assertEquals(1, session('portal_student_id'));

        // Login via WhatsApp wajib membuat PIN baru terlebih dahulu.
        $this->get(route('portal.dashboard'))->assertRedirect(route('portal.change-pin'));
        $this->post(route('portal.change-pin.post'), [
            'pin' => '654321',
            'pin_confirmation' => '654321',
        ])->assertRedirect(route('portal.dashboard'));

        // Cek akses dashboard santri
        $dashboardResponse = $this->get(route('portal.dashboard'));
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee('Muhammad Salman');
        $dashboardResponse->assertSee('Total Tagihan Belum Lunas');
    }

    /**
     * Test 2: Tagihan Wakaf dapat dicicil 2 tahap (Partial -> Paid)
     */
    public function test_wakaf_installment_payment_lifecycle(): void
    {
        $student = Student::where('nis', '202601001')->first();
        $wakafCategory = PaymentCategory::where('code', 'WAKAF')->first();

        // Buat tagihan wakaf baru Rp 5.000.000
        $invoice = Invoice::create([
            'invoice_number' => 'INV-TEST-WAKAF',
            'student_id' => $student->id,
            'payment_category_id' => $wakafCategory->id,
            'title' => 'Wakaf Pembangunan Uji Coba',
            'amount' => 5000000,
            'paid_amount' => 0,
            'due_date' => now()->addMonths(6),
            'status' => 'unpaid',
        ]);

        $receiptService = app(ReceiptService::class);

        // Cicilan 1: Bayar Rp 2.500.000
        $payment1 = $receiptService->recordPayment($invoice, 2500000, 'transfer', 'Cicilan 1');

        $invoice->refresh();
        $this->assertEquals('partial', $invoice->status);
        $this->assertEquals(2500000, $invoice->remaining_amount);
        $this->assertNotNull($payment1->receipt);
        $this->assertNotEmpty($payment1->receipt->receipt_number);

        // Cicilan 2: Bayar Pelunasan Rp 2.500.000
        $payment2 = $receiptService->recordPayment($invoice, 2500000, 'transfer', 'Pelunasan Cicilan 2');

        $invoice->refresh();
        $this->assertEquals('paid', $invoice->status);
        $this->assertEquals(0, $invoice->remaining_amount);
        $this->assertNotNull($payment2->receipt);
    }

    /**
     * Test 3: Auto-generate SPP Bulanan menghitung diskon beasiswa secara tepat
     */
    public function test_batch_spp_generation_applies_scholarship_discount(): void
    {
        $response = $this->actingAs(User::where('email', 'admin@ptqamsa.id')->firstOrFail())
            ->post(route('admin.generate-spp'), [
                'month' => 12,
                'year' => 2026,
            ]);

        $response->assertSessionHas('success');

        // Santri 1 (Reguler): SPP Rp 1.350.000
        $student1 = Student::where('nis', '202601001')->first();
        $inv1 = Invoice::where('student_id', $student1->id)->where('period_month', 12)->first();
        $this->assertNotNull($inv1);
        $this->assertEquals(1350000, $inv1->amount);

        // Santri 2 (Beasiswa 50% Yatim/Dhuafa): SPP Rp 675.000
        $student2 = Student::where('nis', '202601002')->first();
        $inv2 = Invoice::where('student_id', $student2->id)->where('period_month', 12)->first();
        $this->assertNotNull($inv2);
        $this->assertEquals(675000, $inv2->amount);
    }

    /**
     * Test 4: Download PDF Kuitansi Resmi berhasil
     */
    public function test_receipt_pdf_download(): void
    {
        $receipt = Receipt::first();
        $response = $this->actingAs(User::where('email', 'admin@ptqamsa.id')->firstOrFail())
            ->get(route('receipt.download', $receipt->id));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    /**
     * Test 6: Form kasir mewajibkan isian manual "Telah Diterima Dari"
     */
    public function test_cashier_requires_received_from_field(): void
    {
        $invoice = Invoice::where('status', 'unpaid')->firstOrFail();
        $paymentsBefore = Payment::count();

        $response = $this->actingAs(User::where('email', 'bendahara1@ptqamsa.id')->firstOrFail())
            ->post(route('admin.cashier.store'), [
                'invoice_id' => $invoice->id,
                'amount_paid' => 100000,
                'payment_method' => 'cash',
            ]);

        $response->assertSessionHasErrors('received_from');
        $this->assertSame($paymentsBefore, Payment::count());
    }

    /**
     * Test 7: Kuitansi mencetak "Telah Diterima Dari" yang diisi petugas
     */
    public function test_receipt_prints_manual_received_from(): void
    {
        $invoice = Invoice::where('invoice_number', 'INV-202701-0005')->firstOrFail();
        $bendahara1 = User::where('email', 'bendahara1@ptqamsa.id')->firstOrFail();

        $payment = app(ReceiptService::class)->recordPayment(
            invoice: $invoice,
            amountPaid: 675000,
            method: 'cash',
            notes: 'Lunas tunai',
            adminId: $bendahara1->id,
            receivedFrom: 'Bpk. Abdurrahman (Ayah Kandung)',
        );

        $html = view('pdf.receipt', [
            'receipt' => $payment->receipt,
            'payment' => $payment,
            'invoice' => $invoice,
            'student' => $invoice->student,
            'terbilang' => ReceiptService::terbilang((int) $payment->amount_paid).' Rupiah',
        ])->render();

        $this->assertStringContainsString('Bpk. Abdurrahman (Ayah Kandung)', $html);
        $this->assertStringContainsString('SPP Bulanan, Makan &amp; Laundry - Januari 2027', $html);
        $this->assertStringNotContainsString('Potongan 50%', $html);
        $this->assertStringNotContainsString('Penerima Beasiswa', $html);
        $this->assertStringContainsString("Satuan Pendidikan Muadalah Mu'allimien | Putra-Putri", $html);
        $this->assertStringNotContainsString('Sistem Keuangan PTQ Amsa001', $html);
        $this->assertStringContainsString('Bendahara 1,', $html);
        $this->assertStringContainsString('Usth. Yuni Permatasari', $html);
        $this->assertLessThan(
            strpos($html, 'Usth. Yuni Permatasari'),
            strpos($html, 'Bendahara 1,'),
            'Jabatan Bendahara 1 harus tercetak di atas nama penanda tangan.'
        );
        $this->assertStringNotContainsString('Usth. Yuni Permatasari (Bendahara 1)', $html);
        $this->assertStringContainsString('Diunduh:', $html);
        $this->assertStringContainsString(now()->format('d/m/Y'), $html);
    }

    /**
     * Test 7b: Nama penanda tangan kuitansi tidak menyertakan jabatan.
     */
    public function test_receipt_signature_name_omits_position(): void
    {
        $bendahara1 = User::where('email', 'bendahara1@ptqamsa.id')->firstOrFail();
        $bendahara2 = User::where('email', 'bendahara2@ptqamsa.id')->firstOrFail();

        $this->assertSame('Usth. Yuni Permatasari', $bendahara1->receiptName());
        $this->assertSame('Usth. Revina', $bendahara2->receiptName());
        $this->assertSame('Bendahara 1', $bendahara1->receiptPosition());
        $this->assertSame('Bendahara 2', $bendahara2->receiptPosition());
        $this->assertStringNotContainsString('Bendahara 1', $bendahara1->receiptName());
        $this->assertStringNotContainsString('Bendahara 2', $bendahara2->receiptName());
    }

    /**
     * Test 8: Keterangan pembayaran di kuitansi dipersingkat
     */
    public function test_receipt_title_shortens_invoice_description(): void
    {
        // SPP tanpa diskon: hanya nama pos yang diseragamkan
        $this->assertSame(
            'SPP Bulanan, Makan & Laundry - Oktober 2026',
            Invoice::where('invoice_number', 'INV-202610-0001')->firstOrFail()->receipt_title
        );

        // SPP dengan potongan: keterangan potongan dibuang
        $this->assertSame(
            'SPP Bulanan, Makan & Laundry - Januari 2027',
            Invoice::where('invoice_number', 'INV-202701-0005')->firstOrFail()->receipt_title
        );

        // Pos non-SPP tidak diubah
        $this->assertSame(
            'Wakaf Pembangunan Gedung (Total Rp 5.000.000)',
            Invoice::where('invoice_number', 'INV-202607-0003')->firstOrFail()->receipt_title
        );
    }
}
