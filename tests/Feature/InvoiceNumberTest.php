<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\PaymentCategory;
use App\Models\Student;
use App\Models\User;
use App\Services\InvoiceNumberService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceNumberTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Penerbitan tagihan massal dua kali berturut-turut tidak menghasilkan nomor kembar.
     */
    public function test_batch_invoice_numbers_are_unique_across_repeated_runs(): void
    {
        $category = PaymentCategory::where('code', 'BUKU')->firstOrFail();
        $admin = User::where('email', 'admin@ptqamsa.id')->firstOrFail();
        $activeStudents = Student::where('status', 'aktif')->count();

        $payload = [
            'payment_category_id' => $category->id,
            'target_type' => 'all',
            'title' => 'Uji Nomor Tagihan',
            'amount' => 75000,
            'due_date' => now()->addDays(14)->toDateString(),
        ];

        $this->actingAs($admin)->post(route('admin.invoices.store'), $payload)->assertSessionHas('success');
        $this->actingAs($admin)->post(route('admin.invoices.store'), $payload)->assertSessionHas('success');

        $numbers = Invoice::where('title', 'Uji Nomor Tagihan')->pluck('invoice_number');

        $this->assertCount($activeStudents * 2, $numbers);
        $this->assertCount($numbers->count(), $numbers->unique());

        foreach ($numbers as $number) {
            $this->assertMatchesRegularExpression('/^INV-BUKU-\d{6}-\d{4}$/', $number);
        }
    }

    /**
     * Generator melewati nomor lama, termasuk format lama yang memakai angka acak.
     */
    public function test_generator_skips_numbers_already_used(): void
    {
        $student = Student::firstOrFail();
        $category = PaymentCategory::where('code', 'WAKAF')->firstOrFail();

        foreach (['INV-WAKAF-202610-0001', 'INV-WAKAF-202610-0002-731'] as $number) {
            Invoice::create([
                'invoice_number' => $number,
                'student_id' => $student->id,
                'payment_category_id' => $category->id,
                'title' => 'Tagihan Lama',
                'amount' => 100000,
                'paid_amount' => 0,
                'due_date' => '2026-10-31',
                'status' => 'unpaid',
            ]);
        }

        $numbers = app(InvoiceNumberService::class)->generate('WAKAF', 2, Carbon::parse('2026-10-20'));

        $this->assertSame(['INV-WAKAF-202610-0003', 'INV-WAKAF-202610-0004'], $numbers);
    }

    /**
     * Nomor yang sudah terpakai pada periode berjalan tidak dipakai ulang oleh aksi massal.
     */
    public function test_generated_number_skips_existing_number_for_current_period(): void
    {
        $category = PaymentCategory::where('code', 'BUKU')->firstOrFail();
        $student = Student::firstOrFail();

        // Nomor pertama yang "seharusnya" dipakai untuk periode ini sudah ada.
        Invoice::create([
            'invoice_number' => 'INV-BUKU-'.now()->format('Ym').'-0001',
            'student_id' => $student->id,
            'payment_category_id' => $category->id,
            'title' => 'Tagihan Lama',
            'amount' => 100000,
            'paid_amount' => 0,
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'unpaid',
        ]);

        $this->actingAs(User::where('email', 'admin@ptqamsa.id')->firstOrFail())
            ->post(route('admin.invoices.store'), [
                'payment_category_id' => $category->id,
                'target_type' => 'single',
                'student_id' => $student->id,
                'title' => 'Tagihan Baru',
                'amount' => 50000,
                'due_date' => now()->addDays(14)->toDateString(),
            ])
            ->assertSessionHas('success');

        $newInvoice = Invoice::where('title', 'Tagihan Baru')->firstOrFail();

        $this->assertSame('INV-BUKU-'.now()->format('Ym').'-0002', $newInvoice->invoice_number);
    }
}
