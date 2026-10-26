<?php

namespace Database\Seeders;

use App\Enums\ExpenseCategory;
use App\Models\Expense;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Database\Seeder;

class ExpenseDemoSeeder extends Seeder
{
    /**
     * Contoh pengeluaran operasional untuk demo kas keluar.
     */
    public function run(): void
    {
        $bendahara = User::where('email', 'bendahara1@ptqamsa.id')->firstOrFail();

        $expenses = [
            [
                'expense_code' => 'EXP-202607-0001',
                'category' => ExpenseCategory::Operasional,
                'description' => 'Pembelian alat kebersihan asrama',
                'amount' => 350000,
                'expense_date' => '2026-07-05',
                'payment_method' => 'cash',
                'recipient' => 'Toko Bangunan Amanah',
                'notes' => 'Sapu, pel, tempat sampah, dan pembersih lantai.',
            ],
            [
                'expense_code' => 'EXP-202607-0002',
                'category' => ExpenseCategory::Pemeliharaan,
                'description' => 'Perbaikan kran dan pipa kamar mandi',
                'amount' => 750000,
                'expense_date' => '2026-07-18',
                'payment_method' => 'transfer',
                'recipient' => 'CV Karya Teknik',
                'notes' => 'Perbaikan 4 titik kran bocor di asrama putra.',
            ],

            // Kas keluar Agustus 2026
            [
                'expense_code' => 'EXP-202608-0001',
                'category' => ExpenseCategory::Gaji,
                'description' => 'Bisyarah ustadz & pegawai Agustus 2026',
                'amount' => 4500000,
                'expense_date' => '2026-08-03',
                'payment_method' => 'transfer',
                'recipient' => 'Tim Pengajar & Pegawai PTQ Amsa001',
                'notes' => 'Pembayaran bisyarah bulanan staf pengajar dan operasional.',
            ],
            [
                'expense_code' => 'EXP-202608-0002',
                'category' => ExpenseCategory::Konsumsi,
                'description' => 'Belanja bahan makanan asrama Agustus 2026',
                'amount' => 2750000,
                'expense_date' => '2026-08-07',
                'payment_method' => 'cash',
                'recipient' => 'Pasar Megamendung',
                'notes' => 'Beras, sayur, lauk, dan kebutuhan dapur santri.',
            ],
            [
                'expense_code' => 'EXP-202608-0003',
                'category' => ExpenseCategory::Operasional,
                'description' => 'Pembayaran listrik dan air Agustus 2026',
                'amount' => 1200000,
                'expense_date' => '2026-08-12',
                'payment_method' => 'transfer',
                'recipient' => 'PLN & PDAM',
                'notes' => 'Tagihan rutin listrik dan air untuk asrama dan ruang belajar.',
            ],
            [
                'expense_code' => 'EXP-202608-0004',
                'category' => ExpenseCategory::Pemeliharaan,
                'description' => 'Perbaikan atap asrama putra',
                'amount' => 1850000,
                'expense_date' => '2026-08-18',
                'payment_method' => 'transfer',
                'recipient' => 'CV Karya Teknik',
                'notes' => 'Penggantian genteng dan perbaikan rangka atap yang bocor.',
            ],
            [
                'expense_code' => 'EXP-202608-0005',
                'category' => ExpenseCategory::Transport,
                'description' => 'Transport pengadaan bahan bangunan',
                'amount' => 450000,
                'expense_date' => '2026-08-25',
                'payment_method' => 'cash',
                'recipient' => 'Pak Rahmat',
                'notes' => 'Sewa angkutan material perbaikan asrama.',
            ],

            // Kas keluar September 2026
            [
                'expense_code' => 'EXP-202609-0001',
                'category' => ExpenseCategory::Gaji,
                'description' => 'Bisyarah ustadz & pegawai September 2026',
                'amount' => 4500000,
                'expense_date' => '2026-09-01',
                'payment_method' => 'transfer',
                'recipient' => 'Tim Pengajar & Pegawai PTQ Amsa001',
                'notes' => 'Pembayaran bisyarah bulanan staf pengajar dan operasional.',
            ],
            [
                'expense_code' => 'EXP-202609-0002',
                'category' => ExpenseCategory::Konsumsi,
                'description' => 'Belanja bahan makanan asrama September 2026',
                'amount' => 2900000,
                'expense_date' => '2026-09-05',
                'payment_method' => 'cash',
                'recipient' => 'Pasar Megamendung',
                'notes' => 'Beras, sayur, lauk, dan kebutuhan dapur santri.',
            ],
            [
                'expense_code' => 'EXP-202609-0003',
                'category' => ExpenseCategory::Operasional,
                'description' => 'Token listrik dan PDAM September 2026',
                'amount' => 1350000,
                'expense_date' => '2026-09-10',
                'payment_method' => 'transfer',
                'recipient' => 'PLN & PDAM',
                'notes' => 'Tagihan rutin listrik dan air untuk asrama dan ruang belajar.',
            ],
            [
                'expense_code' => 'EXP-202609-0004',
                'category' => ExpenseCategory::Pemeliharaan,
                'description' => 'Servis pompa air asrama',
                'amount' => 850000,
                'expense_date' => '2026-09-15',
                'payment_method' => 'cash',
                'recipient' => 'CV Karya Teknik',
                'notes' => 'Perbaikan pompa air dan pembersihan toren.',
            ],
            [
                'expense_code' => 'EXP-202609-0005',
                'category' => ExpenseCategory::Transport,
                'description' => 'Pengiriman kitab dan buku ajar',
                'amount' => 600000,
                'expense_date' => '2026-09-20',
                'payment_method' => 'transfer',
                'recipient' => 'Ekspedisi Barokah',
                'notes' => 'Pengiriman paket kitab untuk kegiatan belajar santri.',
            ],
            [
                'expense_code' => 'EXP-202609-0006',
                'category' => ExpenseCategory::Lainnya,
                'description' => 'Perlengkapan kebersihan dan cuci',
                'amount' => 750000,
                'expense_date' => '2026-09-26',
                'payment_method' => 'cash',
                'recipient' => 'Toko Bangunan Amanah',
                'notes' => 'Sabun, deterjen, sapu, dan perlengkapan kebersihan asrama.',
            ],

            // Kas keluar Oktober 2026
            [
                'expense_code' => 'EXP-202610-0001',
                'category' => ExpenseCategory::Konsumsi,
                'description' => 'Konsumsi rapat wali santri',
                'amount' => 1250000,
                'expense_date' => '2026-10-12',
                'payment_method' => 'cash',
                'recipient' => 'Dapur Pesantren',
                'notes' => 'Snack dan makan siang peserta rapat.',
            ],
            [
                'expense_code' => 'EXP-202610-0003',
                'category' => ExpenseCategory::Konsumsi,
                'description' => 'Belanja bahan makanan asrama Oktober 2026',
                'amount' => 2000000,
                'expense_date' => '2026-10-03',
                'payment_method' => 'cash',
                'recipient' => 'Pasar Megamendung',
                'notes' => 'Beras, sayur, lauk, dan kebutuhan dapur santri.',
            ],
            [
                'expense_code' => 'EXP-202610-0004',
                'category' => ExpenseCategory::Operasional,
                'description' => 'Pembelian gas dan galon air minum',
                'amount' => 650000,
                'expense_date' => '2026-10-08',
                'payment_method' => 'cash',
                'recipient' => 'Depot Air & Gas',
                'notes' => 'Kebutuhan dapur dan asrama.',
            ],
            [
                'expense_code' => 'EXP-202610-0006',
                'category' => ExpenseCategory::Pemeliharaan,
                'description' => 'Perbaikan lampu dan instalasi listrik asrama',
                'amount' => 450000,
                'expense_date' => '2026-10-14',
                'payment_method' => 'cash',
                'recipient' => 'Pak Dedi Teknik',
                'notes' => 'Penggantian lampu dan perbaikan saklar di koridor asrama.',
            ],
            [
                'expense_code' => 'EXP-202610-0007',
                'category' => ExpenseCategory::Transport,
                'description' => 'Antar santri berobat ke klinik',
                'amount' => 300000,
                'expense_date' => '2026-10-18',
                'payment_method' => 'cash',
                'recipient' => 'Pak Rahmat',
                'notes' => 'Transportasi santri dan pendamping ke klinik terdekat.',
            ],
        ];

        foreach ($expenses as $data) {
            $expense = Expense::firstOrCreate(
                ['expense_code' => $data['expense_code']],
                $data + ['recorded_by' => $bendahara->id]
            );

            if ($expense->wasRecentlyCreated) {
                ActivityLogService::expenseRecorded($expense, $bendahara->id);
            }
        }

        $voided = Expense::firstOrCreate(
            ['expense_code' => 'EXP-202610-0002'],
            [
                'category' => ExpenseCategory::Transport,
                'description' => 'Sewa kendaraan operasional (salah input)',
                'amount' => 500000,
                'expense_date' => '2026-10-15',
                'payment_method' => 'cash',
                'recipient' => 'Pak Rahmat',
                'notes' => 'Contoh data yang dibatalkan karena salah input.',
                'recorded_by' => $bendahara->id,
            ]
        );

        if ($voided->wasRecentlyCreated) {
            ActivityLogService::expenseRecorded($voided, $bendahara->id);

            $voided->voided_at = now();
            $voided->voided_by = $bendahara->id;
            $voided->void_reason = 'Salah input nominal; data yang benar sudah dicatat terpisah.';
            $voided->save();

            ActivityLogService::expenseVoided($voided, $voided->void_reason, $bendahara->id);
        }
    }
}
