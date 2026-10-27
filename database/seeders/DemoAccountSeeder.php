<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentCategory;
use App\Models\Receipt;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Akun santri khusus uji coba portal wali.
 *
 * NIS berawalan UJI sehingga tidak ikut terhapus saat data riil diimpor
 * dengan `santri:import --hapus-dummy`. Hapus manual saat mode demo dimatikan.
 *
 *   php artisan db:seed --class=DemoAccountSeeder --force
 */
class DemoAccountSeeder extends Seeder
{
    /**
     * @var array<int, array{nis: string, pin: string, nama: string, group: string, kelas: string}>
     */
    public const ACCOUNTS = [
        ['nis' => 'UJI00001', 'pin' => '100001', 'nama' => 'Santri Uji Coba Putra', 'group' => 'putra', 'kelas' => 'Uji Coba'],
        ['nis' => 'UJI00002', 'pin' => '100002', 'nama' => 'Santri Uji Coba Putri', 'group' => 'putri', 'kelas' => 'Uji Coba'],
    ];

    public function run(): void
    {
        $spp = PaymentCategory::firstOrCreate(
            ['code' => 'SPP'],
            ['name' => 'Iuran Rutin Bulanan (SPP + Makan 3x + Laundry)', 'payment_type' => 'monthly', 'default_amount' => 1350000, 'is_active' => true]
        );
        $formulir = PaymentCategory::firstOrCreate(
            ['code' => 'FORMULIR'],
            ['name' => 'Biaya Formulir Pendaftaran', 'payment_type' => 'one_time', 'default_amount' => 200000, 'is_active' => true]
        );
        $recordedBy = User::orderBy('id')->value('id');

        foreach (self::ACCOUNTS as $index => $account) {
            $student = Student::updateOrCreate(
                ['nis' => $account['nis']],
                [
                    'nama_lengkap' => $account['nama'],
                    'jenis_kelamin' => $account['group'] === 'putri' ? 'P' : 'L',
                    'santri_group' => $account['group'],
                    'kelas' => $account['kelas'],
                    'kamar_asrama' => 'Uji Coba',
                    'tahun_masuk' => (int) now()->year,
                    'status' => 'aktif',
                    'kota_asal' => 'Bogor',
                    'nama_wali' => 'Wali Uji Coba',
                    'no_whatsapp_wali' => '08000000000'.($index + 1),
                    'hubungan_wali' => 'Wali',
                    'pin_portal' => $account['pin'],
                    'must_change_pin' => false,
                    'pin_changed_at' => now(),
                    'diskon_beasiswa' => 0,
                    'bebas_wakaf' => false,
                ]
            );

            if ($student->invoices()->exists()) {
                continue;
            }

            $seq = str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);

            // Satu tagihan lunas (ada kuitansi) dan satu tagihan bulan ini yang belum dibayar.
            $paidInvoice = Invoice::create([
                'invoice_number' => 'INV-UJI-'.$seq,
                'student_id' => $student->id,
                'payment_category_id' => $formulir->id,
                'title' => 'Biaya Formulir Pendaftaran (Uji Coba)',
                'amount' => 200000,
                'paid_amount' => 200000,
                'due_date' => now()->subMonth()->toDateString(),
                'status' => 'paid',
            ]);

            $payment = Payment::create([
                'payment_code' => 'PAY-UJI-'.$seq,
                'invoice_id' => $paidInvoice->id,
                'student_id' => $student->id,
                'amount_paid' => 200000,
                'payment_date' => now()->subMonth()->toDateString(),
                'payment_method' => 'cash',
                'received_from' => 'Wali Uji Coba',
                'notes' => 'Transaksi uji coba.',
                'recorded_by' => $recordedBy,
            ]);

            Receipt::create([
                'receipt_number' => 'KWT-UJI-'.$seq,
                'payment_id' => $payment->id,
                'qr_verification_token' => Str::random(32),
                'issued_at' => now()->subMonth(),
            ]);

            Invoice::create([
                'invoice_number' => 'INV-UJI-'.str_pad((string) ($index + 11), 4, '0', STR_PAD_LEFT),
                'student_id' => $student->id,
                'payment_category_id' => $spp->id,
                'title' => 'SPP, Makan & Laundry - '.now()->translatedFormat('F Y').' (Uji Coba)',
                'amount' => 1350000,
                'paid_amount' => 0,
                'due_date' => now()->startOfMonth()->addDays(9)->toDateString(),
                'status' => 'unpaid',
                'period_month' => (int) now()->month,
                'period_year' => (int) now()->year,
            ]);
        }

        $this->command?->info('Akun uji coba siap: UJI00001 / PIN 100001 (putra), UJI00002 / PIN 100002 (putri).');
    }
}
