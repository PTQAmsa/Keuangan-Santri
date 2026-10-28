<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\PaymentCategory;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentImportTest extends TestCase
{
    use RefreshDatabase;

    private string $fixture = __DIR__.'/../Fixtures/santri-impor.csv';

    private function makeDummyStudent(string $nis, string $group): Student
    {
        return Student::create([
            'nis' => $nis,
            'nama_lengkap' => 'Santri Dummy '.$nis,
            'jenis_kelamin' => $group === 'putri' ? 'P' : 'L',
            'santri_group' => $group,
            'kelas' => '1 Reguler',
            'tahun_masuk' => 2026,
            'status' => 'aktif',
            'nama_wali' => 'Wali Dummy',
            'no_whatsapp_wali' => '000000000001',
            'hubungan_wali' => 'Ayah Kandung',
            'pin_portal' => '123456',
        ]);
    }

    public function test_csv_rows_become_students_with_generated_nis_and_pin(): void
    {
        Storage::fake('local');

        $this->artisan('santri:import', ['file' => $this->fixture, '--marhalah' => 'putra'])
            ->expectsOutputToContain('3 santri putra baru diimpor, 0 santri diperbarui, 1 baris dilewati')
            ->assertSuccessful();

        $this->assertDatabaseCount('students', 3);

        $maulana = Student::where('nama_lengkap', 'Maulana Rahma Yusuf')->firstOrFail();
        $this->assertSame('2026001', $maulana->nis);
        $this->assertSame('putra', $maulana->santri_group);
        $this->assertSame('L', $maulana->jenis_kelamin);
        $this->assertSame('Angkatan 2026', $maulana->kelas);
        $this->assertSame('Bogor', $maulana->kota_asal);
        $this->assertSame('Iin Silvia Nita', $maulana->nama_wali);
        $this->assertSame('Ayah Kandung', $maulana->hubungan_wali);
        $this->assertSame('', $maulana->no_whatsapp_wali);
        $this->assertTrue($maulana->must_change_pin);
        $this->assertFalse(Hash::check('123456', $maulana->pin_portal));

        $nahdan = Student::where('nama_lengkap', 'Nahdan Riandi')->firstOrFail();
        $this->assertSame('2026002', $nahdan->nis);
        $this->assertSame('085789000970', $nahdan->no_whatsapp_wali);
        $this->assertSame('Ibu Kandung', $nahdan->hubungan_wali);

        $zul = Student::where('nama_lengkap', 'Zulqarnayn')->firstOrFail();
        $this->assertSame('2023001', $zul->nis);
        $this->assertSame('081285004642', $zul->no_whatsapp_wali);
        $this->assertSame('Lainnya', $zul->hubungan_wali);
        $this->assertSame('Kakek', $zul->hubungan_wali_detail);

        $this->assertDatabaseHas('activity_logs', [
            'module' => 'student',
            'description' => 'Mengimpor 3 santri baru dan memperbarui 0 santri putra dari berkas santri-impor.csv.',
        ]);
    }

    public function test_pin_list_file_lets_the_portal_login_with_the_generated_pin(): void
    {
        Storage::fake('local');

        $this->artisan('santri:import', ['file' => $this->fixture])->assertSuccessful();

        $files = Storage::disk('local')->files('import');
        $this->assertCount(1, $files);

        $lines = array_values(array_filter(explode("\n", Storage::disk('local')->get($files[0]))));
        $this->assertSame('NIS,Nama,Wali,No. WA,PIN Awal', $lines[0]);

        $columns = str_getcsv($lines[2], ',', '"', '');
        $this->assertSame('2026002', $columns[0]);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $columns[4]);

        $student = Student::where('nis', '2026002')->firstOrFail();
        $this->assertTrue(Hash::check($columns[4], $student->pin_portal));
    }

    public function test_dummy_students_of_the_same_marhalah_are_replaced_only_when_requested(): void
    {
        Storage::fake('local');

        $dummyPutra = $this->makeDummyStudent('DMY00001', 'putra');
        $dummyPutri = $this->makeDummyStudent('DMY00060', 'putri');
        $category = PaymentCategory::create(['name' => 'SPP', 'code' => 'SPP', 'payment_type' => 'monthly', 'default_amount' => 100000]);
        $invoice = Invoice::create([
            'invoice_number' => 'INV-202610-0001',
            'student_id' => $dummyPutra->id,
            'payment_category_id' => $category->id,
            'title' => 'SPP Oktober',
            'amount' => 100000,
            'due_date' => '2026-10-10',
            'status' => 'unpaid',
        ]);

        $this->artisan('santri:import', ['file' => $this->fixture])->assertSuccessful();
        $this->assertDatabaseHas('students', ['id' => $dummyPutra->id]);

        $this->artisan('santri:import', ['file' => $this->fixture, '--hapus-dummy' => true])
            ->expectsOutputToContain('0 santri putra baru diimpor, 3 santri diperbarui, 1 baris dilewati, 1 santri dummy dihapus')
            ->assertSuccessful();

        $this->assertDatabaseMissing('students', ['id' => $dummyPutra->id]);
        $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);
        $this->assertDatabaseHas('students', ['id' => $dummyPutri->id]);
        $this->assertSame(1, Student::where('nama_lengkap', 'Nahdan Riandi')->count());
        $this->assertSame(2, ActivityLog::where('module', 'student')->count());
    }

    public function test_second_csv_with_semicolons_updates_existing_students_without_changing_pin(): void
    {
        Storage::fake('local');

        $this->artisan('santri:import', ['file' => $this->fixture])->assertSuccessful();

        $files = Storage::disk('local')->files('import');
        $lines = array_values(array_filter(explode('
', Storage::disk('local')->get($files[0]))));
        $pinMaulana = str_getcsv($lines[1], ',', '"', '')[4];

        $this->artisan('santri:import', ['file' => __DIR__.'/../Fixtures/santri-impor-update.csv'])
            ->expectsOutputToContain('1 santri putra baru diimpor, 2 santri diperbarui, 0 baris dilewati')
            ->assertSuccessful();

        $this->assertDatabaseCount('students', 4);

        $maulana = Student::where('nis', '2026001')->firstOrFail();
        $this->assertSame('1 Reguler', $maulana->kelas);
        $this->assertSame('Ibu Kandung', $maulana->hubungan_wali);
        $this->assertTrue(Hash::check($pinMaulana, $maulana->pin_portal));

        // Nomor WA yang sudah ada tidak boleh terhapus oleh kolom kosong di CSV baru.
        $nahdan = Student::where('nis', '2026002')->firstOrFail();
        $this->assertSame('085789000970', $nahdan->no_whatsapp_wali);
        $this->assertSame('1 Reguler', $nahdan->kelas);

        $baru = Student::where('nama_lengkap', 'Santri Baru Putra')->firstOrFail();
        $this->assertSame('2025001', $baru->nis);
        $this->assertSame('Intensif', $baru->kelas);
        $this->assertSame('081200000001', $baru->no_whatsapp_wali);
        $this->assertSame('Ayah Kandung', $baru->hubungan_wali);
    }

    public function test_missing_file_or_name_column_fails_without_writing_students(): void
    {
        Storage::fake('local');

        $this->artisan('santri:import', ['file' => __DIR__.'/../Fixtures/tidak-ada.csv'])
            ->expectsOutputToContain('Berkas tidak ditemukan')
            ->assertFailed();

        $invalid = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($invalid, "NO,ASAL\n1,Bogor\n");

        $this->artisan('santri:import', ['file' => $invalid])
            ->expectsOutputToContain('Kolom NAMA tidak ditemukan')
            ->assertFailed();

        unlink($invalid);

        $this->assertDatabaseCount('students', 0);
        $this->assertSame([], Storage::disk('local')->files('import'));
    }
}
