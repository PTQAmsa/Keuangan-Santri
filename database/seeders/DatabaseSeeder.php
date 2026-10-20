<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentCategory;
use App\Models\Receipt;
use App\Models\Student;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Akun Pengurus yang Berhak Mengakses Panel Admin (semua sandi: "password"):
        // - Administrator     : akses penuh
        // - Bendahara 1 & 2   : kasir, tagihan & laporan
        // - 3 Kyai / Pengasuh : hanya melihat (read-only)

        User::firstOrCreate(
            ['email' => 'admin@ptqamsa.id'],
            [
                'name' => 'Administrator Sistem',
                'role' => UserRole::Admin,
                'password' => Hash::make('password'),
            ]
        );

        $bendahara1 = User::firstOrCreate(
            ['email' => 'bendahara1@ptqamsa.id'],
            [
                'name' => 'Usth. Yuni Permatasari (Bendahara 1)',
                'jabatan' => 'Bendahara 1 PTQ Amsa001',
                'role' => UserRole::Bendahara,
                'password' => Hash::make('password'),
            ]
        );

        User::firstOrCreate(
            ['email' => 'bendahara2@ptqamsa.id'],
            [
                'name' => 'Usth. Revina (Bendahara 2)',
                'jabatan' => 'Bendahara 2 PTQ Amsa001',
                'role' => UserRole::Bendahara,
                'password' => Hash::make('password'),
            ]
        );

        // Tiga Kyai yang memiliki akses panel (read-only).
        $kyai = [
            ['email' => 'pengasuh@ptqamsa.id', 'name' => 'KH. Drs. Aminullah Tsamud', 'jabatan' => 'Pengasuh Pesantren'],
            ['email' => 'muadalah@ptqamsa.id', 'name' => 'KH. Drs. Zubaidi, MM', 'jabatan' => "Kepala Muadalah Mu'allimien"],
            ['email' => 'yayasan@ptqamsa.id', 'name' => 'KH. Drs. Ahmadie Thaha, M.Si.', 'jabatan' => 'Direktur Yayasan'],
        ];

        foreach ($kyai as $akun) {
            User::firstOrCreate(
                ['email' => $akun['email']],
                [
                    'name' => $akun['name'],
                    'jabatan' => $akun['jabatan'],
                    'role' => UserRole::Pengasuh,
                    'password' => Hash::make('password'),
                ]
            );
        }

        // 2. Master Pos Pembayaran Resmi PTQ Amsa001
        $catFormulir = PaymentCategory::create([
            'name' => 'Biaya Formulir Pendaftaran',
            'code' => 'FORMULIR',
            'payment_type' => 'one_time',
            'default_amount' => 200000,
            'description' => 'Biaya pendaftaran santri baru masuk PTQ Amsa001',
            'is_active' => true,
        ]);

        $catFasilitas = PaymentCategory::create([
            'name' => 'Fasilitas Kamar (Lemari, Kasur, Bantal, Selimut & Sprei)',
            'code' => 'FASILITAS',
            'payment_type' => 'one_time',
            'default_amount' => 1000000,
            'description' => 'Perlengkapan asrama santri baru (kasur busa, lemari pakaian, bantal, selimut dan sprei)',
            'is_active' => true,
        ]);

        $catWakaf = PaymentCategory::create([
            'name' => 'Wakaf Pembangunan (Gedung & Asrama)',
            'code' => 'WAKAF',
            'payment_type' => 'installment',
            'default_amount' => 5000000,
            'description' => 'Wakaf sarana pembangunan pesantren, dapat dicicil 2 kali (@Rp 2.500.000)',
            'is_active' => true,
        ]);

        $catSPP = PaymentCategory::create([
            'name' => 'Iuran Rutin Bulanan (SPP + Makan 3x + Laundry)',
            'code' => 'SPP',
            'payment_type' => 'monthly',
            'default_amount' => 1350000,
            'description' => 'SPP Rp 600.000 + Makan 3x Rp 550.000 + Laundry Rp 200.000 = Rp 1.350.000/bulan',
            'is_active' => true,
        ]);

        $catBuku = PaymentCategory::create([
            'name' => 'Uang Kitab & Buku Ajar',
            'code' => 'BUKU',
            'payment_type' => 'periodic',
            'default_amount' => 450000,
            'description' => 'Pengadaan kitab kuning & buku ajar kurikulum Muadalah',
            'is_active' => true,
        ]);

        $catEvent = PaymentCategory::create([
            'name' => 'Iuran Kondisional (Rihlah / Camping / Wisuda)',
            'code' => 'EVENT',
            'payment_type' => 'conditional',
            'default_amount' => 250000,
            'description' => 'Kegiatan kepesantrenan terprogram (rihlah ilmiah, perkemahan santri, wisuda)',
            'is_active' => true,
        ]);

        // 3. Data Santri Contoh PTQ Amsa001
        $santri1 = Student::create([
            'nis' => '202601001',
            'nisn' => '0098765431',
            'nama_lengkap' => 'Muhammad Salman',
            'jenis_kelamin' => 'L',
            'santri_group' => 'putra',
            'kelas' => '7A',
            'kamar_asrama' => 'Gedung Amsa - Kamar 01',
            'tahun_masuk' => 2026,
            'status' => 'aktif',
            'kota_asal' => 'Bogor',
            'alamat_lengkap' => 'Jl. Pajajaran No. 45, Kota Bogor',
            'nama_wali' => 'Bpk. Bambang Sutrisno',
            'no_whatsapp_wali' => '085692253516', // Nomor kontak pesantren untuk test notifikasi
            'hubungan_wali' => 'Ayah Kandung',
            'pin_portal' => '123456',
            'must_change_pin' => true,
            'diskon_beasiswa' => 0.00,
            'bebas_wakaf' => false,
        ]);

        $santri2 = Student::create([
            'nis' => '202601002',
            'nisn' => '0098765432',
            'nama_lengkap' => 'Zaid Abdullah Al-Qurthubi',
            'jenis_kelamin' => 'L',
            'santri_group' => 'putra',
            'kelas' => '7A',
            'kamar_asrama' => 'Gedung Amsa - Kamar 02',
            'tahun_masuk' => 2026,
            'status' => 'aktif',
            'kota_asal' => 'Jakarta Selatan',
            'alamat_lengkap' => 'Tebet Barat Dalam, Jakarta Selatan',
            'nama_wali' => 'Ibu Siti Maryam',
            'no_whatsapp_wali' => '081234567891',
            'hubungan_wali' => 'Ibu Kandung',
            'pin_portal' => '123456',
            'must_change_pin' => true,
            'diskon_beasiswa' => 50.00, // Program dhuafa / yatim (Potongan 50%)
            'bebas_wakaf' => true,      // Bebas wakaf
        ]);

        $santri3 = Student::create([
            'nis' => '202601003',
            'nisn' => '0098765433',
            'nama_lengkap' => 'Aisyah Khumaira',
            'jenis_kelamin' => 'P',
            'santri_group' => 'putri',
            'kelas' => '8 Putri',
            'kamar_asrama' => 'Asrama Banat - Kamar Khadijah',
            'tahun_masuk' => 2025,
            'status' => 'aktif',
            'kota_asal' => 'Depok',
            'alamat_lengkap' => 'Margonda Raya, Depok',
            'nama_wali' => 'Bpk. H. Rahmat Hidayat',
            'no_whatsapp_wali' => '081398765432',
            'hubungan_wali' => 'Ayah Kandung',
            'pin_portal' => '123456',
            'must_change_pin' => true,
            'diskon_beasiswa' => 0.00,
            'bebas_wakaf' => false,
        ]);

        // Santri 4: Ahmad Iqbal (Putra - Beasiswa)
        $santri4 = Student::create([
            'nis' => '202601004',
            'nisn' => '0098765434',
            'nama_lengkap' => 'Ahmad Iqbal',
            'jenis_kelamin' => 'L',
            'santri_group' => 'putra',
            'kelas' => '7B',
            'kamar_asrama' => 'Gedung Amsa - Kamar 03',
            'tahun_masuk' => 2026,
            'status' => 'aktif',
            'kota_asal' => 'Sukabumi',
            'alamat_lengkap' => 'Jl. Bhayangkara No. 12, Sukabumi',
            'nama_wali' => 'Bpk. Hendra Gunawan',
            'no_whatsapp_wali' => '085712345678',
            'hubungan_wali' => 'Ayah Kandung',
            'pin_portal' => '123456',
            'must_change_pin' => true,
            'diskon_beasiswa' => 50.00, // Beasiswa 50%
            'bebas_wakaf' => true,      // Bebas wakaf
        ]);

        // Santri 5: Aulia Syifarani (Putri - Beasiswa)
        $santri5 = Student::create([
            'nis' => '202601005',
            'nisn' => '0098765435',
            'nama_lengkap' => 'Aulia Syifarani',
            'jenis_kelamin' => 'P',
            'santri_group' => 'putri',
            'kelas' => '7 Putri',
            'kamar_asrama' => 'Asrama Banat - Kamar Aisyah',
            'tahun_masuk' => 2026,
            'status' => 'aktif',
            'kota_asal' => 'Cianjur',
            'alamat_lengkap' => 'Jl. Raya Cipanas, Cianjur',
            'nama_wali' => 'Ibu Nurhayati',
            'no_whatsapp_wali' => '085898765432',
            'hubungan_wali' => 'Ibu Kandung',
            'pin_portal' => '123456',
            'must_change_pin' => true,
            'diskon_beasiswa' => 50.00, // Beasiswa 50%
            'bebas_wakaf' => true,      // Bebas wakaf
        ]);

        // 4. Sample Tagihan & Pembayaran untuk Santri 1 (Muhammad Salman)

        // Tagihan 1: Formulir (Lunas)
        $invFormulir = Invoice::create([
            'invoice_number' => 'INV-202601-0001',
            'student_id' => $santri1->id,
            'payment_category_id' => $catFormulir->id,
            'title' => 'Biaya Formulir Pendaftaran Santri Baru',
            'amount' => 200000,
            'paid_amount' => 200000,
            'due_date' => '2026-07-01',
            'status' => 'paid',
            'notes' => 'Pendaftaran gelombang 1',
        ]);
        $payFormulir = Payment::create([
            'payment_code' => 'PAY-202607-0001',
            'invoice_id' => $invFormulir->id,
            'student_id' => $santri1->id,
            'amount_paid' => 200000,
            'payment_date' => '2026-07-01',
            'payment_method' => 'transfer',
            'received_from' => 'Bpk. Bambang Sutrisno (Ayah Kandung)',
            'notes' => 'Transfer Bank BSI an Bambang Sutrisno',
            'recorded_by' => $bendahara1->id,
        ]);
        Receipt::create([
            'receipt_number' => 'KWT-202607-0001',
            'payment_id' => $payFormulir->id,
            'qr_verification_token' => Str::random(32),
            'issued_at' => now(),
        ]);

        // Tagihan 2: Fasilitas Kamar (Lunas)
        $invFasilitas = Invoice::create([
            'invoice_number' => 'INV-202607-0002',
            'student_id' => $santri1->id,
            'payment_category_id' => $catFasilitas->id,
            'title' => 'Fasilitas Kamar (Kasur, Lemari, Sprei, Bantal, Selimut)',
            'amount' => 1000000,
            'paid_amount' => 1000000,
            'due_date' => '2026-07-15',
            'status' => 'paid',
        ]);
        $payFasilitas = Payment::create([
            'payment_code' => 'PAY-202607-0002',
            'invoice_id' => $invFasilitas->id,
            'student_id' => $santri1->id,
            'amount_paid' => 1000000,
            'payment_date' => '2026-07-15',
            'payment_method' => 'transfer',
            'received_from' => 'Bpk. Bambang Sutrisno (Ayah Kandung)',
            'notes' => 'Lunas perlengkapan asrama',
            'recorded_by' => $bendahara1->id,
        ]);
        Receipt::create([
            'receipt_number' => 'KWT-202607-0002',
            'payment_id' => $payFasilitas->id,
            'qr_verification_token' => Str::random(32),
            'issued_at' => now(),
        ]);

        // Tagihan 3: Wakaf Pembangunan (Dicicil 1x Rp 2.500.000 -> Status: PARTIAL)
        $invWakaf = Invoice::create([
            'invoice_number' => 'INV-202607-0003',
            'student_id' => $santri1->id,
            'payment_category_id' => $catWakaf->id,
            'title' => 'Wakaf Pembangunan Gedung (Total Rp 5.000.000)',
            'amount' => 5000000,
            'paid_amount' => 2500000,
            'due_date' => '2026-12-31',
            'status' => 'partial', // Masih ada sisa cicilan Rp 2.500.000
            'notes' => 'Skema cicilan 2x. Cicilan 1 telah masuk.',
        ]);
        $payWakaf = Payment::create([
            'payment_code' => 'PAY-202607-0003',
            'invoice_id' => $invWakaf->id,
            'student_id' => $santri1->id,
            'amount_paid' => 2500000,
            'payment_date' => '2026-07-20',
            'payment_method' => 'transfer',
            'received_from' => 'Bpk. Bambang Sutrisno (Ayah Kandung)',
            'notes' => 'Cicilan 1 Wakaf Pembangunan',
            'recorded_by' => $bendahara1->id,
        ]);
        Receipt::create([
            'receipt_number' => 'KWT-202607-0003',
            'payment_id' => $payWakaf->id,
            'qr_verification_token' => Str::random(32),
            'issued_at' => now(),
        ]);

        // Tagihan 4: SPP & Makan Oktober 2026 (Lunas)
        $invSPPOkt = Invoice::create([
            'invoice_number' => 'INV-202610-0001',
            'student_id' => $santri1->id,
            'payment_category_id' => $catSPP->id,
            'title' => 'Iuran Rutin SPP, Makan & Laundry - Oktober 2026',
            'amount' => 1350000,
            'paid_amount' => 1350000,
            'due_date' => '2026-10-10',
            'status' => 'paid',
            'period_month' => 10,
            'period_year' => 2026,
        ]);
        $paySPPOkt = Payment::create([
            'payment_code' => 'PAY-202610-0001',
            'invoice_id' => $invSPPOkt->id,
            'student_id' => $santri1->id,
            'amount_paid' => 1350000,
            'payment_date' => '2026-10-05',
            'payment_method' => 'transfer',
            'received_from' => 'Bambang Sutrisno (Transfer BSI)',
            'notes' => 'Pembayaran SPP Oktober 2026 via BSI',
            'recorded_by' => $bendahara1->id,
        ]);
        Receipt::create([
            'receipt_number' => 'KWT-202610-0001',
            'payment_id' => $paySPPOkt->id,
            'qr_verification_token' => Str::random(32),
            'issued_at' => now(),
        ]);

        // Tagihan Muhammad Salman (3 Bulan ke Depan: Nov 2026, Des 2026, Jan 2027)
        Invoice::create([
            'invoice_number' => 'INV-202611-0001',
            'student_id' => $santri1->id,
            'payment_category_id' => $catSPP->id,
            'title' => 'Iuran Rutin SPP, Makan & Laundry - November 2026',
            'amount' => 1350000,
            'paid_amount' => 0,
            'due_date' => '2026-11-10',
            'status' => 'unpaid',
            'period_month' => 11,
            'period_year' => 2026,
        ]);

        Invoice::create([
            'invoice_number' => 'INV-202612-0001',
            'student_id' => $santri1->id,
            'payment_category_id' => $catSPP->id,
            'title' => 'Iuran Rutin SPP, Makan & Laundry - Desember 2026',
            'amount' => 1350000,
            'paid_amount' => 0,
            'due_date' => '2026-12-10',
            'status' => 'unpaid',
            'period_month' => 12,
            'period_year' => 2026,
        ]);

        Invoice::create([
            'invoice_number' => 'INV-202701-0001',
            'student_id' => $santri1->id,
            'payment_category_id' => $catSPP->id,
            'title' => 'Iuran Rutin SPP, Makan & Laundry - Januari 2027',
            'amount' => 1350000,
            'paid_amount' => 0,
            'due_date' => '2027-01-10',
            'status' => 'unpaid',
            'period_month' => 1,
            'period_year' => 2027,
        ]);

        // 5. Tagihan Santri 2 (Zaid - Beasiswa Dhuafa 50%)
        Invoice::create([
            'invoice_number' => 'INV-202610-0002',
            'student_id' => $santri2->id,
            'payment_category_id' => $catSPP->id,
            'title' => 'Iuran Rutin SPP, Makan & Laundry - Oktober 2026 (Potongan 50%)',
            'amount' => 675000,
            'paid_amount' => 675000,
            'due_date' => '2026-10-10',
            'status' => 'paid',
            'period_month' => 10,
            'period_year' => 2026,
            'notes' => 'Penerima Beasiswa Yatim/Dhuafa (Potongan 50%)',
        ]);

        Invoice::create([
            'invoice_number' => 'INV-202611-0002',
            'student_id' => $santri2->id,
            'payment_category_id' => $catSPP->id,
            'title' => 'Iuran Rutin SPP, Makan & Laundry - November 2026 (Potongan 50%)',
            'amount' => 675000,
            'paid_amount' => 0,
            'due_date' => '2026-11-10',
            'status' => 'unpaid',
            'period_month' => 11,
            'period_year' => 2026,
            'notes' => 'Penerima Beasiswa Yatim/Dhuafa',
        ]);

        Invoice::create([
            'invoice_number' => 'INV-202612-0002',
            'student_id' => $santri2->id,
            'payment_category_id' => $catSPP->id,
            'title' => 'Iuran Rutin SPP, Makan & Laundry - Desember 2026 (Potongan 50%)',
            'amount' => 675000,
            'paid_amount' => 0,
            'due_date' => '2026-12-10',
            'status' => 'unpaid',
            'period_month' => 12,
            'period_year' => 2026,
            'notes' => 'Penerima Beasiswa Yatim/Dhuafa',
        ]);

        Invoice::create([
            'invoice_number' => 'INV-202701-0002',
            'student_id' => $santri2->id,
            'payment_category_id' => $catSPP->id,
            'title' => 'Iuran Rutin SPP, Makan & Laundry - Januari 2027 (Potongan 50%)',
            'amount' => 675000,
            'paid_amount' => 0,
            'due_date' => '2027-01-10',
            'status' => 'unpaid',
            'period_month' => 1,
            'period_year' => 2027,
            'notes' => 'Penerima Beasiswa Yatim/Dhuafa',
        ]);

        // 6. Tagihan Santri 4: Ahmad Iqbal (Putra - Beasiswa 50%)
        Invoice::create([
            'invoice_number' => 'INV-202611-0004',
            'student_id' => $santri4->id,
            'payment_category_id' => $catSPP->id,
            'title' => 'Iuran Rutin SPP, Makan & Laundry - November 2026 (Potongan 50%)',
            'amount' => 675000,
            'paid_amount' => 0,
            'due_date' => '2026-11-10',
            'status' => 'unpaid',
            'period_month' => 11,
            'period_year' => 2026,
            'notes' => 'Penerima Beasiswa Yatim/Dhuafa (Potongan 50%)',
        ]);
        Invoice::create([
            'invoice_number' => 'INV-202612-0004',
            'student_id' => $santri4->id,
            'payment_category_id' => $catSPP->id,
            'title' => 'Iuran Rutin SPP, Makan & Laundry - Desember 2026 (Potongan 50%)',
            'amount' => 675000,
            'paid_amount' => 0,
            'due_date' => '2026-12-10',
            'status' => 'unpaid',
            'period_month' => 12,
            'period_year' => 2026,
            'notes' => 'Penerima Beasiswa Yatim/Dhuafa (Potongan 50%)',
        ]);
        Invoice::create([
            'invoice_number' => 'INV-202701-0004',
            'student_id' => $santri4->id,
            'payment_category_id' => $catSPP->id,
            'title' => 'Iuran Rutin SPP, Makan & Laundry - Januari 2027 (Potongan 50%)',
            'amount' => 675000,
            'paid_amount' => 0,
            'due_date' => '2027-01-10',
            'status' => 'unpaid',
            'period_month' => 1,
            'period_year' => 2027,
            'notes' => 'Penerima Beasiswa Yatim/Dhuafa (Potongan 50%)',
        ]);

        // 7. Tagihan Santri 5: Aulia Syifarani (Putri - Beasiswa 50%)
        Invoice::create([
            'invoice_number' => 'INV-202611-0005',
            'student_id' => $santri5->id,
            'payment_category_id' => $catSPP->id,
            'title' => 'Iuran Rutin SPP, Makan & Laundry - November 2026 (Potongan 50%)',
            'amount' => 675000,
            'paid_amount' => 0,
            'due_date' => '2026-11-10',
            'status' => 'unpaid',
            'period_month' => 11,
            'period_year' => 2026,
            'notes' => 'Penerima Beasiswa Yatim/Dhuafa (Potongan 50%)',
        ]);
        Invoice::create([
            'invoice_number' => 'INV-202612-0005',
            'student_id' => $santri5->id,
            'payment_category_id' => $catSPP->id,
            'title' => 'Iuran Rutin SPP, Makan & Laundry - Desember 2026 (Potongan 50%)',
            'amount' => 675000,
            'paid_amount' => 0,
            'due_date' => '2026-12-10',
            'status' => 'unpaid',
            'period_month' => 12,
            'period_year' => 2026,
            'notes' => 'Penerima Beasiswa Yatim/Dhuafa (Potongan 50%)',
        ]);
        Invoice::create([
            'invoice_number' => 'INV-202701-0005',
            'student_id' => $santri5->id,
            'payment_category_id' => $catSPP->id,
            'title' => 'Iuran Rutin SPP, Makan & Laundry - Januari 2027 (Potongan 50%)',
            'amount' => 675000,
            'paid_amount' => 0,
            'due_date' => '2027-01-10',
            'status' => 'unpaid',
            'period_month' => 1,
            'period_year' => 2027,
            'notes' => 'Penerima Beasiswa Yatim/Dhuafa (Potongan 50%)',
        ]);

        // Log aktivitas awal (riwayat demo): pembayaran yang dicatat oleh Bendahara 1.
        foreach ([$payFormulir, $payFasilitas, $payWakaf, $paySPPOkt] as $seededPayment) {
            ActivityLogService::paymentRecorded(
                payment: $seededPayment,
                userId: $bendahara1->id,
                createdAt: $seededPayment->payment_date->copy()->setTime(9, 15),
            );
        }

        // Contoh pengeluaran operasional untuk halaman Kas Keluar.
        $this->call(ExpenseDemoSeeder::class);
    }
}
