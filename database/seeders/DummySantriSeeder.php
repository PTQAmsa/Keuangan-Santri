<?php

namespace Database\Seeders;

use App\Enums\ActivityAction;
use App\Enums\ActivityModule;
use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentCategory;
use App\Models\Receipt;
use App\Models\Student;
use App\Models\User;
use App\Services\ActivityLogService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Mengganti seluruh data santri contoh dengan data dummy dari CSV.
 *
 * Jalankan hanya pada database demo:
 *   php artisan db:seed --class=DummySantriSeeder --force
 *
 * Seeder ini menghapus santri lama beserta tagihan, pembayaran, kuitansi,
 * dan log aktivitas lama, lalu membuat riwayat keuangan contoh yang konsisten.
 */
class DummySantriSeeder extends Seeder
{
    /**
     * Nomor baris (kolom NO) yang mendapat beasiswa 50%.
     * 5 putra + 5 putri.
     */
    private array $scholarshipNos = [10, 20, 30, 40, 50, 60, 65, 70, 75, 79];

    private array $invoiceSequences = [];

    private array $paymentSequences = [];

    private array $receiptSequences = [];

    public function run(): void
    {
        $csvPath = database_path('seeders/data/Data_Santri_Dummy.csv');

        if (! is_file($csvPath)) {
            throw new \RuntimeException('File data santri dummy tidak ditemukan: '.$csvPath);
        }

        $rows = $this->readCsv($csvPath);

        if ($rows === []) {
            throw new \RuntimeException('File CSV santri dummy tidak berisi data.');
        }

        DB::transaction(function () use ($rows) {
            // Hapus data lama agar nama santri lama tidak tersisa di aplikasi.
            Receipt::query()->delete();
            Payment::query()->delete();
            Invoice::query()->delete();
            ActivityLog::query()->delete();
            Student::query()->delete();

            $categories = $this->categories();
            $recordedBy = $this->recordedByUsers();

            foreach ($rows as $row) {
                $student = $this->createStudent($row);

                $this->createFinancialHistory(
                    student: $student,
                    row: $row,
                    categories: $categories,
                    recordedBy: $recordedBy,
                );
            }

            $admin = User::where('role', 'admin')->first();

            ActivityLogService::record(
                module: ActivityModule::Student,
                action: ActivityAction::Created,
                description: 'Mengimpor '.count($rows).' data santri dummy dari file Data_Santri_Dummy.csv.',
                properties: [
                    'Jumlah Santri' => (string) count($rows),
                    'Sumber Data' => 'Data_Santri_Dummy.csv',
                    'Putra' => (string) Student::where('santri_group', 'putra')->count(),
                    'Putri' => (string) Student::where('santri_group', 'putri')->count(),
                    'Penerima Beasiswa 50%' => (string) Student::where('diskon_beasiswa', 50)->count(),
                ],
                userId: $admin?->id,
            );
        });

        // Lengkapi data demo kas keluar (Agustus–Oktober 2026).
        $this->call(ExpenseDemoSeeder::class);

        $this->command?->info(sprintf(
            'Selesai: %d santri, %d tagihan, %d pembayaran, %d kuitansi.',
            Student::count(),
            Invoice::count(),
            Payment::count(),
            Receipt::count(),
        ));
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new \RuntimeException('Tidak dapat membuka file CSV: '.$path);
        }

        $headers = fgetcsv($handle, 0, ',', '"', '');

        if ($headers === false) {
            fclose($handle);

            return [];
        }

        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headers[0]);
        $headers = array_map(fn ($header) => trim((string) $header), $headers);

        $rows = [];

        while (($data = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            if ($data === [null] || implode('', array_map('trim', $data)) === '') {
                continue;
            }

            if (count($data) !== count($headers)) {
                continue;
            }

            $rows[] = array_combine($headers, array_map(
                fn ($value) => trim((string) $value),
                $data
            ));
        }

        fclose($handle);

        return $rows;
    }

    private function createStudent(array $row): Student
    {
        $no = (int) ($row['NO'] ?? 0);
        $marhalah = strcasecmp($row['MARHALAH'] ?? 'Putra', 'Putri') === 0 ? 'Putri' : 'Putra';
        $gender = $marhalah === 'Putri' ? 'P' : 'L';
        $group = $gender === 'P' ? 'putri' : 'putra';
        $tahunMasuk = (int) round((float) ($row['TAHUN MASUK'] ?? 2026));
        $pin = $this->limit($row['PIN PORTAL'] ?? '123456', 20);
        $scholarship = in_array($no, $this->scholarshipNos, true);

        return Student::create([
            'nis' => $this->limit($row['NIS'] ?? '', 20),
            'nisn' => $this->limit($row['NISN'] ?? '', 20) ?: null,
            'nama_lengkap' => $this->limit($row['NAMA LENGKAP'] ?? '', 100),
            'jenis_kelamin' => $gender,
            'santri_group' => $group,
            'kelas' => $this->limit($row['KELAS'] ?? '', 20),
            'kamar_asrama' => $this->roomFor($gender, $no),
            'tahun_masuk' => $tahunMasuk,
            'status' => 'aktif',
            'kota_asal' => $this->limit($row['ASAL'] ?? '', 50) ?: null,
            'alamat_lengkap' => null,
            'nama_wali' => $this->limit($row['NAMA ORANGTUA/WALI'] ?? '', 100),
            'no_whatsapp_wali' => $this->limit($row['NO WA ORTU/WALI'] ?? '', 25),
            'hubungan_wali' => $this->mapHubungan($row['HUBUNGAN'] ?? 'Wali'),
            'hubungan_wali_detail' => null,
            'pin_portal' => $pin,
            'must_change_pin' => false,
            'pin_changed_at' => now(),
            'pin_reset_requested_at' => null,
            'diskon_beasiswa' => $scholarship ? 50 : 0,
            'bebas_wakaf' => false,
        ]);
    }

    /**
     * @param  array<string, PaymentCategory>  $categories
     * @param  array<int, int>  $recordedBy
     */
    private function createFinancialHistory(Student $student, array $row, array $categories, array $recordedBy): void
    {
        $no = (int) ($row['NO'] ?? 0);
        $userId = $recordedBy[$no % count($recordedBy)];
        $isScholar = (float) $student->diskon_beasiswa > 0;
        $isNewStudent = (int) $student->tahun_masuk === 2026;

        if ($isNewStudent) {
            $this->createFormulirInvoice($student, $categories['FORMULIR'], $no, $userId);
            $this->createFasilitasInvoice($student, $categories['FASILITAS'], $no, $userId);
        }

        // Wakaf tidak wajib untuk semua santri; sebagian saja agar data tetap wajar.
        if ($no % 5 === 0) {
            $this->createWakafInvoice($student, $categories['WAKAF'], $no, $userId);
        }

        $this->createBukuInvoice($student, $categories['BUKU'], $no, $userId);
        $this->createSppInvoice($student, $categories['SPP'], 9, $no, $userId, $isScholar);
        $this->createSppInvoice($student, $categories['SPP'], 10, $no, $userId, $isScholar);
    }

    private function createFormulirInvoice(Student $student, PaymentCategory $category, int $no, int $userId): void
    {
        $invoice = $this->createInvoice(
            student: $student,
            category: $category,
            title: 'Biaya Formulir Pendaftaran Santri Baru',
            amount: 200000,
            createdAt: Carbon::create(2026, 6, 20, 8, 0),
            dueDate: Carbon::create(2026, 7, 1),
            notes: 'Pendaftaran santri baru tahun 2026.',
        );

        $this->recordPayment(
            invoice: $invoice,
            student: $student,
            amount: 200000,
            paidAt: Carbon::create(2026, 6, 25)->addDays($no % 5),
            recordedById: $userId,
        );
    }

    private function createFasilitasInvoice(Student $student, PaymentCategory $category, int $no, int $userId): void
    {
        $amount = 1000000;

        $invoice = $this->createInvoice(
            student: $student,
            category: $category,
            title: 'Fasilitas Kamar (Lemari, Kasur, Bantal, Selimut & Sprei)',
            amount: $amount,
            createdAt: Carbon::create(2026, 6, 20, 8, 0),
            dueDate: Carbon::create(2026, 7, 15),
            notes: 'Perlengkapan asrama santri baru tahun 2026.',
        );

        $paidAt = Carbon::create(2026, 7, 1)->addDays($no % 10);

        if ($no % 5 !== 0) {
            $this->recordPayment($invoice, $student, $amount, $paidAt, $userId);
        } else {
            $this->recordPayment($invoice, $student, 500000, $paidAt, $userId);
        }
    }

    private function createWakafInvoice(Student $student, PaymentCategory $category, int $no, int $userId): void
    {
        $amount = 5000000;

        $invoice = $this->createInvoice(
            student: $student,
            category: $category,
            title: 'Wakaf Pembangunan Gedung & Asrama',
            amount: $amount,
            createdAt: Carbon::create(2026, 6, 20, 8, 0),
            dueDate: Carbon::create(2026, 12, 31),
            notes: 'Wakaf dapat dicicil selama menjadi santri.',
        );

        if ($no % 10 === 0) {
            $this->recordPayment(
                invoice: $invoice,
                student: $student,
                amount: 2500000,
                paidAt: Carbon::create(2026, 7, 25)->addDays($no % 5),
                recordedById: $userId,
            );
        }
    }

    private function createBukuInvoice(Student $student, PaymentCategory $category, int $no, int $userId): void
    {
        $amount = 450000;

        $invoice = $this->createInvoice(
            student: $student,
            category: $category,
            title: 'Uang Kitab & Buku Ajar',
            amount: $amount,
            createdAt: Carbon::create(2026, 8, 1, 8, 0),
            dueDate: Carbon::create(2026, 8, 31),
            notes: 'Pengadaan kitab kuning & buku ajar kurikulum Muadalah.',
        );

        $paidAt = Carbon::create(2026, 8, 5)->addDays($no % 10);

        if ($no % 10 <= 5) {
            $this->recordPayment($invoice, $student, $amount, $paidAt, $userId);
        } elseif ($no % 10 <= 7) {
            $this->recordPayment($invoice, $student, 200000, $paidAt, $userId);
        }
    }

    private function createSppInvoice(
        Student $student,
        PaymentCategory $category,
        int $month,
        int $no,
        int $userId,
        bool $isScholar,
    ): void {
        $amount = $isScholar ? 675000 : 1350000;
        $monthName = $month === 9 ? 'September' : 'Oktober';
        $title = 'SPP, Makan & Laundry - '.$monthName.' 2026';

        if ($isScholar) {
            $title .= ' (Potongan 50%)';
        }

        $invoice = $this->createInvoice(
            student: $student,
            category: $category,
            title: $title,
            amount: $amount,
            createdAt: Carbon::create(2026, $month, 1, 8, 0),
            dueDate: Carbon::create(2026, $month, 10),
            periodMonth: $month,
            periodYear: 2026,
            notes: $isScholar ? 'Penerima Beasiswa 50%.' : null,
        );

        $mode = $month === 9
            ? match (true) {
                $no % 10 <= 6 => 'full',
                $no % 10 <= 8 => 'partial',
                default => 'none',
            }
        : match (true) {
            $no % 10 <= 4 => 'full',
            $no % 10 <= 6 => 'partial',
            default => 'none',
        };

        if ($mode === 'none') {
            return;
        }

        $paidAt = Carbon::create(2026, $month, 3)->addDays($no % 10);

        if ($mode === 'full') {
            $this->recordPayment($invoice, $student, $amount, $paidAt, $userId);

            return;
        }

        $partial = $this->roundTo($amount * 0.5, 50000);

        if ($partial > 0 && $partial < $amount) {
            $this->recordPayment($invoice, $student, $partial, $paidAt, $userId);
        }
    }

    private function createInvoice(
        Student $student,
        PaymentCategory $category,
        string $title,
        float $amount,
        Carbon $createdAt,
        Carbon $dueDate,
        ?int $periodMonth = null,
        ?int $periodYear = null,
        ?string $notes = null,
    ): Invoice {
        $invoice = Invoice::create([
            'invoice_number' => $this->nextInvoiceNumber($createdAt),
            'student_id' => $student->id,
            'payment_category_id' => $category->id,
            'title' => $title,
            'amount' => $amount,
            'paid_amount' => 0,
            'due_date' => $dueDate->toDateString(),
            'status' => 'unpaid',
            'period_month' => $periodMonth,
            'period_year' => $periodYear,
            'notes' => $notes,
        ]);

        $this->stamp($invoice, $createdAt);

        return $invoice;
    }

    private function recordPayment(
        Invoice $invoice,
        Student $student,
        float $amount,
        Carbon $paidAt,
        int $recordedById,
    ): void {
        $previousStatus = (string) $invoice->status;
        $previousPaidAmount = (float) $invoice->paid_amount;
        $isCash = $student->id % 3 === 0;

        $payment = Payment::create([
            'payment_code' => $this->nextPaymentNumber($paidAt),
            'invoice_id' => $invoice->id,
            'student_id' => $student->id,
            'amount_paid' => $amount,
            'payment_date' => $paidAt->toDateString(),
            'payment_method' => $isCash ? 'cash' : 'transfer',
            'received_from' => $this->limit($student->nama_wali.' ('.$student->hubungan_wali.')', 150),
            'notes' => $isCash ? 'Pembayaran tunai di kasir pesantren.' : 'Transfer bank BSI.',
            'recorded_by' => $recordedById,
        ]);

        $this->stamp($payment, $paidAt->copy()->setTime(9, 0));

        $receipt = Receipt::create([
            'receipt_number' => $this->nextReceiptNumber($paidAt),
            'payment_id' => $payment->id,
            'qr_verification_token' => Str::random(32),
            'issued_at' => $paidAt->copy()->setTime(9, 0),
        ]);

        $this->stamp($receipt, $paidAt->copy()->setTime(9, 0));

        $invoice->paid_amount = $previousPaidAmount + $amount;
        $invoice->status = $invoice->paid_amount >= $invoice->amount ? 'paid' : 'partial';
        $invoice->saveQuietly();

        ActivityLogService::paymentRecorded(
            payment: $payment,
            previousStatus: $previousStatus,
            previousPaidAmount: $previousPaidAmount,
            userId: $recordedById,
            createdAt: $paidAt->copy()->setTime(9, 15),
        );
    }

    /**
     * @return array<string, PaymentCategory>
     */
    private function categories(): array
    {
        return [
            'FORMULIR' => PaymentCategory::firstOrCreate(
                ['code' => 'FORMULIR'],
                ['name' => 'Biaya Formulir Pendaftaran', 'payment_type' => 'one_time', 'default_amount' => 200000, 'is_active' => true]
            ),
            'FASILITAS' => PaymentCategory::firstOrCreate(
                ['code' => 'FASILITAS'],
                ['name' => 'Fasilitas Kamar (Lemari, Kasur, Bantal, Selimut & Sprei)', 'payment_type' => 'one_time', 'default_amount' => 1000000, 'is_active' => true]
            ),
            'WAKAF' => PaymentCategory::firstOrCreate(
                ['code' => 'WAKAF'],
                ['name' => 'Wakaf Pembangunan (Gedung & Asrama)', 'payment_type' => 'installment', 'default_amount' => 5000000, 'is_active' => true]
            ),
            'SPP' => PaymentCategory::firstOrCreate(
                ['code' => 'SPP'],
                ['name' => 'Iuran Rutin Bulanan (SPP + Makan 3x + Laundry)', 'payment_type' => 'monthly', 'default_amount' => 1350000, 'is_active' => true]
            ),
            'BUKU' => PaymentCategory::firstOrCreate(
                ['code' => 'BUKU'],
                ['name' => 'Uang Kitab & Buku Ajar', 'payment_type' => 'periodic', 'default_amount' => 450000, 'is_active' => true]
            ),
        ];
    }

    /**
     * @return array<int, int>
     */
    private function recordedByUsers(): array
    {
        $ids = User::whereIn('email', ['bendahara1@ptqamsa.id', 'bendahara2@ptqamsa.id'])
            ->orderBy('id')
            ->pluck('id')
            ->all();

        if ($ids === []) {
            $ids = User::query()->orderBy('id')->pluck('id')->all();
        }

        if ($ids === []) {
            throw new \RuntimeException('Tidak ada user yang dapat dicatat sebagai petugas pembayaran.');
        }

        return $ids;
    }

    private function nextInvoiceNumber(Carbon $date): string
    {
        $key = $date->format('Ym');
        $this->invoiceSequences[$key] = ($this->invoiceSequences[$key] ?? 0) + 1;

        return 'INV-'.$key.'-'.str_pad((string) $this->invoiceSequences[$key], 4, '0', STR_PAD_LEFT);
    }

    private function nextPaymentNumber(Carbon $date): string
    {
        $key = $date->format('Ym');
        $this->paymentSequences[$key] = ($this->paymentSequences[$key] ?? 0) + 1;

        return 'PAY-'.$key.'-'.str_pad((string) $this->paymentSequences[$key], 4, '0', STR_PAD_LEFT);
    }

    private function nextReceiptNumber(Carbon $date): string
    {
        $key = $date->format('Ym');
        $this->receiptSequences[$key] = ($this->receiptSequences[$key] ?? 0) + 1;

        return 'KWT-'.$key.'-'.str_pad((string) $this->receiptSequences[$key], 4, '0', STR_PAD_LEFT);
    }

    private function stamp(Invoice|Payment|Receipt $model, Carbon $at): void
    {
        $model->created_at = $at;
        $model->updated_at = $at;
        $model->saveQuietly();
    }

    private function mapHubungan(string $hubungan): string
    {
        return match (strtolower(trim($hubungan))) {
            'ayah', 'ayah kandung' => 'Ayah Kandung',
            'ibu', 'ibu kandung' => 'Ibu Kandung',
            'ayah angkat' => 'Ayah Angkat',
            'ibu angkat' => 'Ibu Angkat',
            'wali' => 'Wali',
            default => 'Lainnya',
        };
    }

    private function roomFor(string $gender, int $no): string
    {
        $room = str_pad((string) ((($no - 1) % 12) + 1), 2, '0', STR_PAD_LEFT);

        return $gender === 'P'
            ? 'Asrama Putri - Kamar '.$room
            : 'Asrama Putra - Kamar '.$room;
    }

    private function roundTo(float $value, int $nearest): float
    {
        return round($value / $nearest) * $nearest;
    }

    private function limit(string $value, int $limit): string
    {
        return mb_substr(trim($value), 0, $limit);
    }
}
