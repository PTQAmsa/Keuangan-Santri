<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Enums\ActivityModule;
use App\Models\OpeningBalance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Impor saldo kas awal (tunai dan tiap rekening) dari CSV rekapan bendahara.
 */
class OpeningBalanceImportService
{
    /**
     * @var array<string, string>
     */
    public const COLUMN_ALIASES = [
        'TANGGAL' => 'tanggal',
        'TANGGAL MULAI' => 'tanggal',
        'JENIS' => 'jenis',
        'TEMPAT' => 'jenis',
        'KAS' => 'jenis',
        'NOMINAL' => 'nominal',
        'SALDO' => 'nominal',
        'JUMLAH' => 'nominal',
        'NAMA BANK' => 'bank',
        'BANK' => 'bank',
        'NO REKENING' => 'rekening',
        'NOMOR REKENING' => 'rekening',
        'NO. REKENING' => 'rekening',
        'ATAS NAMA' => 'atas_nama',
        'NAMA PEMILIK REKENING' => 'atas_nama',
        'KETERANGAN' => 'keterangan',
    ];

    /**
     * @return array{
     *     imported: array<int, array{tanggal: string, tempat: string, nominal: float}>,
     *     skipped: array<int, array{baris: int, alasan: string}>,
     *     replaced: int
     * }
     */
    public function import(string $path, bool $replaceExisting = false, ?int $userId = null): array
    {
        $rows = CsvImportReader::read($path, self::COLUMN_ALIASES, ['tanggal', 'nominal']);

        if ($rows === []) {
            throw new \RuntimeException('Berkas CSV tidak berisi data saldo.');
        }

        if (! $replaceExisting && OpeningBalance::exists()) {
            throw new \RuntimeException('Saldo awal sudah pernah diisi. Jalankan dengan --ganti untuk menimpa saldo awal yang lama.');
        }

        return DB::transaction(function () use ($rows, $replaceExisting, $userId, $path) {
            $replaced = $replaceExisting ? OpeningBalance::query()->delete() : 0;
            $imported = [];
            $skipped = [];

            foreach ($rows as $index => $row) {
                $line = $index + 2;

                try {
                    $date = Carbon::parse($row['tanggal'] ?? '');
                } catch (\Throwable) {
                    $skipped[] = ['baris' => $line, 'alasan' => 'Tanggal tidak dikenali: '.($row['tanggal'] ?? '')];

                    continue;
                }

                $amount = CsvImportReader::parseAmount($row['nominal'] ?? '');

                if ($amount <= 0) {
                    $skipped[] = ['baris' => $line, 'alasan' => 'Nominal kosong atau nol'];

                    continue;
                }

                $isCash = $this->isCash($row['jenis'] ?? '', $row['bank'] ?? '', $row['rekening'] ?? '');

                $balance = OpeningBalance::create([
                    'effective_date' => $date->toDateString(),
                    'payment_method' => $isCash ? 'cash' : 'transfer',
                    'amount' => $amount,
                    'bank_name' => $isCash ? null : (mb_substr($row['bank'] ?? '', 0, 50) ?: 'Bank'),
                    'bank_account_number' => $isCash ? null : (mb_substr($row['rekening'] ?? '', 0, 50) ?: null),
                    'bank_account_name' => $isCash ? null : (mb_substr($row['atas_nama'] ?? '', 0, 100) ?: null),
                    'notes' => mb_substr($row['keterangan'] ?? '', 0, 255) ?: 'Saldo awal migrasi dari pembukuan lama.',
                    'recorded_by' => $userId,
                ]);

                $imported[] = [
                    'tanggal' => $date->toDateString(),
                    'tempat' => $balance->placeLabel(),
                    'nominal' => $amount,
                ];
            }

            ActivityLogService::record(
                module: ActivityModule::BookClosing,
                action: ActivityAction::Created,
                description: 'Mengimpor '.count($imported).' saldo kas awal dari berkas '.basename($path).'.',
                properties: [
                    'Berkas' => basename($path),
                    'Baris Masuk' => (string) count($imported),
                    'Baris Dilewati' => (string) count($skipped),
                    'Saldo Lama Dihapus' => (string) $replaced,
                    'Total Saldo Awal' => 'Rp '.number_format(array_sum(array_column($imported, 'nominal')), 0, ',', '.'),
                ],
                userId: $userId,
            );

            return [
                'imported' => $imported,
                'skipped' => $skipped,
                'replaced' => (int) $replaced,
            ];
        });
    }

    private function isCash(string $jenis, string $bank, string $rekening): bool
    {
        $jenis = strtolower(trim($jenis));

        if (in_array($jenis, ['tunai', 'kas', 'kas tunai', 'cash', 'brankas', 'laci'], true)) {
            return true;
        }

        if (in_array($jenis, ['rekening', 'bank', 'transfer'], true)) {
            return false;
        }

        return trim($bank) === '' && trim($rekening) === '';
    }
}
