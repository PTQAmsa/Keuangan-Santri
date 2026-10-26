<?php

namespace Database\Seeders;

use App\Enums\ActivityModule;
use App\Enums\ExpenseCategory;
use App\Models\ActivityLog;
use App\Models\Expense;
use App\Models\Teacher;
use App\Models\TeacherBisyarahPayment;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\BisyarahExpenseService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Mengganti seluruh data guru contoh dengan data dummy dari CSV, lalu
 * mencatat bisyarah tiga bulan terakhir beserta kas keluarnya.
 *
 * Jalankan hanya pada database demo:
 *   php artisan db:seed --class=DummyGuruSeeder --force
 *
 * Format CSV (tanpa header, pemisah titik koma): Nama Lengkap;Nominal Bisyarah.
 * Seeder ini menghapus guru lama, catatan bisyarahnya, kas keluar yang
 * terhubung, serta kas keluar kategori Bisyarah gabungan dari seeder demo
 * lama agar bisyarah tidak terhitung dua kali.
 */
class DummyGuruSeeder extends Seeder
{
    /**
     * Berapa bulan ke belakang (termasuk bulan berjalan) yang dibuatkan bisyarah.
     */
    private const MONTHS_OF_HISTORY = 3;

    private const CITIES = [
        'Bogor', 'Sukabumi', 'Garut', 'Tasikmalaya', 'Cianjur', 'Bandung',
        'Depok', 'Bekasi', 'Cirebon', 'Kuningan', 'Purwakarta', 'Subang',
    ];

    private const BANKS = ['BSI', 'BRI', 'BNI', 'Mandiri', 'BCA'];

    public function run(): void
    {
        $csvPath = database_path('seeders/data/Data_Guru_Dummy.csv');

        if (! is_file($csvPath)) {
            throw new \RuntimeException('File data guru dummy tidak ditemukan: '.$csvPath);
        }

        $rows = $this->readCsv($csvPath);

        if ($rows === []) {
            throw new \RuntimeException('File CSV guru dummy tidak berisi data.');
        }

        $expenses = app(BisyarahExpenseService::class);
        $recordedBy = $this->recordedByUsers();

        DB::transaction(function () use ($rows, $expenses, $recordedBy): void {
            $this->purgeExistingData();

            $periods = $this->periods();

            foreach ($rows as $index => $row) {
                $teacher = $this->createTeacher($row, $index);

                foreach ($periods as $period) {
                    $this->recordBisyarah($teacher, $row['amount'], $period, $recordedBy[$index % count($recordedBy)], $expenses);
                }
            }
        });

        $this->command?->info(sprintf(
            'Selesai: %d guru, %d catatan bisyarah, %d kas keluar bisyarah.',
            Teacher::count(),
            TeacherBisyarahPayment::count(),
            Expense::where('category', ExpenseCategory::Gaji->value)->notVoided()->count(),
        ));
    }

    /**
     * @return array<int, array{name: string, amount: float}>
     */
    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new \RuntimeException('Tidak dapat membuka file CSV: '.$path);
        }

        $rows = [];

        while (($line = fgetcsv($handle, 0, ';', '"', '')) !== false) {
            $name = trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) ($line[0] ?? '')));
            $amount = (float) preg_replace('/[^\d.]/', '', (string) ($line[1] ?? ''));

            if ($name === '' || $amount <= 0) {
                continue;
            }

            $rows[] = ['name' => $name, 'amount' => $amount];
        }

        fclose($handle);

        return $rows;
    }

    private function purgeExistingData(): void
    {
        $linkedExpenseIds = TeacherBisyarahPayment::query()->whereNotNull('expense_id')->pluck('expense_id');

        TeacherBisyarahPayment::query()->delete();
        Teacher::query()->delete();

        Expense::query()->whereIn('id', $linkedExpenseIds)->delete();
        Expense::query()->where('category', ExpenseCategory::Gaji->value)->delete();

        ActivityLog::query()->where('module', ActivityModule::Bisyarah->value)->delete();
    }

    /**
     * @param  array{name: string, amount: float}  $row
     */
    private function createTeacher(array $row, int $index): Teacher
    {
        $status = match (true) {
            $row['amount'] < 100000 => 'guru_tidak_tetap',
            $row['amount'] >= 2000000 => 'pengasuh',
            default => 'guru_tetap',
        };

        return Teacher::create([
            'nama_lengkap' => $row['name'],
            'kota_asal' => self::CITIES[$index % count(self::CITIES)],
            'no_whatsapp' => sprintf('62857%08d', 20000000 + $index * 137 + ($index * 73) % 1000),
            'status' => $status,
            'is_active' => true,
        ]);
    }

    private function recordBisyarah(
        Teacher $teacher,
        float $amount,
        Carbon $period,
        int $userId,
        BisyarahExpenseService $expenses,
    ): void {
        $useTransfer = $amount >= 1000000;
        $bank = self::BANKS[$teacher->id % count(self::BANKS)];

        $payment = TeacherBisyarahPayment::create([
            'teacher_id' => $teacher->id,
            'period_year' => $period->year,
            'period_month' => $period->month,
            'amount' => $amount,
            'payment_method' => $useTransfer ? 'transfer' : 'cash',
            'bank_name' => $useTransfer ? $bank : null,
            'bank_account_number' => $useTransfer ? sprintf('%03d%07d', 100 + $teacher->id, 1000000 + $teacher->id * 4321) : null,
            'bank_account_name' => $useTransfer ? $this->accountHolderName($teacher->nama_lengkap) : null,
            'recorded_by' => $userId,
        ]);

        $expense = $expenses->sync($payment, $userId);

        // Bisyarah dibayarkan di awal bulan agar riwayat demo terasa wajar.
        $paidAt = $period->copy()->addDays(2)->setTime(9, 0);
        $expense->forceFill(['expense_date' => $paidAt->toDateString(), 'created_at' => $paidAt, 'updated_at' => $paidAt])->save();
        $payment->forceFill(['created_at' => $paidAt, 'updated_at' => $paidAt])->save();

        $log = ActivityLogService::bisyarahRecorded($payment, $userId);
        $log->forceFill(['created_at' => $paidAt, 'updated_at' => $paidAt])->save();
    }

    /**
     * Bulan-bulan yang dibuatkan bisyarah, dari yang terlama ke bulan berjalan.
     *
     * @return list<Carbon>
     */
    private function periods(): array
    {
        $periods = [];

        for ($back = self::MONTHS_OF_HISTORY - 1; $back >= 0; $back--) {
            $periods[] = now()->startOfMonth()->subMonthsNoOverflow($back);
        }

        return $periods;
    }

    /**
     * Nama pemilik rekening tanpa gelar, mis. "Yuni Permatasari, M.Pd." → "Yuni Permatasari".
     */
    private function accountHolderName(string $fullName): string
    {
        $withoutTitles = preg_replace('/,.*$/', '', $fullName) ?? $fullName;
        $withoutTitles = preg_replace('/\s+(S\.|M\.)[A-Za-z. ]*$/', '', $withoutTitles) ?? $withoutTitles;

        return strtoupper(trim($withoutTitles));
    }

    /**
     * @return list<int>
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
            throw new \RuntimeException('Tidak ada user yang dapat dicatat sebagai pencatat bisyarah.');
        }

        return $ids;
    }
}
