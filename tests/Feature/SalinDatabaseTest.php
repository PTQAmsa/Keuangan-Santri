<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SalinDatabaseTest extends TestCase
{
    use RefreshDatabase;

    private string $targetFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->targetFile = tempnam(sys_get_temp_dir(), 'tujuan');
        config(['database.connections.tujuan' => ['driver' => 'sqlite', 'database' => $this->targetFile, 'prefix' => '', 'foreign_key_constraints' => true]]);
    }

    protected function tearDown(): void
    {
        DB::purge('tujuan');
        @unlink($this->targetFile);

        parent::tearDown();
    }

    public function test_all_application_tables_are_copied_with_identical_rows(): void
    {
        $this->artisan('db:salin', ['--dari' => 'sqlite', '--ke' => 'tujuan'])
            ->expectsOutputToContain('belum ada di tujuan')
            ->assertFailed();

        $this->artisan('migrate', ['--database' => 'tujuan', '--force' => true])->assertSuccessful();

        $this->artisan('db:salin', ['--dari' => 'sqlite', '--ke' => 'tujuan'])
            ->expectsOutputToContain('Selesai:')
            ->assertSuccessful();

        foreach (['users', 'students', 'payment_categories', 'invoices', 'payments', 'receipts', 'activity_logs', 'expenses', 'teachers', 'teacher_bisyarah_payments'] as $table) {
            $this->assertSame(DB::table($table)->count(), DB::connection('tujuan')->table($table)->count(), "Jumlah baris $table berbeda");
        }

        $student = Student::orderBy('id')->firstOrFail();
        $copy = DB::connection('tujuan')->table('students')->where('id', $student->id)->first();
        $this->assertSame($student->nis, $copy->nis);
        $this->assertSame($student->getRawOriginal('pin_portal'), $copy->pin_portal);

        $payment = Payment::orderBy('id')->firstOrFail();
        $copy = DB::connection('tujuan')->table('payments')->where('id', $payment->id)->first();
        $this->assertSame((string) $payment->getRawOriginal('amount_paid'), (string) $copy->amount_paid);
        $this->assertSame($payment->invoice_id, $copy->invoice_id);

        // Tabel sistem tidak ikut disalin.
        $this->assertSame(0, DB::connection('tujuan')->table('sessions')->count());
    }

    public function test_second_copy_is_refused_unless_replacement_is_requested(): void
    {
        $this->artisan('migrate', ['--database' => 'tujuan', '--force' => true])->assertSuccessful();
        $this->artisan('db:salin', ['--dari' => 'sqlite', '--ke' => 'tujuan'])->assertSuccessful();

        $this->artisan('db:salin', ['--dari' => 'sqlite', '--ke' => 'tujuan'])
            ->expectsOutputToContain('sudah berisi data')
            ->assertFailed();

        Invoice::query()->delete();

        $this->artisan('db:salin', ['--dari' => 'sqlite', '--ke' => 'tujuan', '--ganti' => true])
            ->expectsOutputToContain('Isi lama')
            ->assertSuccessful();

        $this->assertSame(0, DB::connection('tujuan')->table('invoices')->count());
        $this->assertSame(Student::count(), DB::connection('tujuan')->table('students')->count());
    }
}
