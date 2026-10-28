<?php

namespace App\Console\Commands;

use App\Services\OpeningBalanceImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ImportSaldoAwal extends Command
{
    protected $signature = 'kas:saldo-awal
                            {file? : Lokasi berkas CSV saldo awal}
                            {--ganti : Timpa saldo awal yang sudah pernah diisi}
                            {--template : Buat berkas contoh CSV di storage/app/private/import}';

    protected $description = 'Impor saldo kas awal (tunai dan rekening) saat sistem mulai dipakai';

    public function handle(OpeningBalanceImportService $service): int
    {
        if ($this->option('template')) {
            return $this->writeTemplate();
        }

        $file = (string) $this->argument('file');

        if ($file === '' || ! is_file($file)) {
            $this->error('Berkas tidak ditemukan: '.$file);

            return self::FAILURE;
        }

        try {
            $result = $service->import($file, (bool) $this->option('ganti'));
        } catch (\RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Selesai: %d saldo awal dicatat, %d baris dilewati, %d saldo lama dihapus.',
            count($result['imported']),
            count($result['skipped']),
            $result['replaced'],
        ));

        foreach ($result['skipped'] as $skipped) {
            $this->warn(sprintf('Baris %d dilewati: %s', $skipped['baris'], $skipped['alasan']));
        }

        if ($result['imported'] !== []) {
            $this->table(
                ['Tanggal', 'Tempat', 'Saldo'],
                array_map(fn (array $row) => [$row['tanggal'], $row['tempat'], 'Rp '.number_format($row['nominal'], 0, ',', '.')], $result['imported']),
            );

            $this->line('Total saldo awal: Rp '.number_format(array_sum(array_column($result['imported'], 'nominal')), 0, ',', '.'));
        }

        return self::SUCCESS;
    }

    private function writeTemplate(): int
    {
        $relative = 'import/template-saldo-awal.csv';

        Storage::disk('local')->put($relative, implode("\n", [
            'TANGGAL,JENIS,NOMINAL,NAMA BANK,NO REKENING,ATAS NAMA,KETERANGAN',
            '2026-11-01,Tunai,2500000,,,,Uang di brankas bendahara',
            "2026-11-01,Rekening,15000000,BSI,1202020212,Pesantren Tadabbur Al-Qur'an,Saldo rekening per 1 November",
        ])."\n");

        $this->info('Contoh berkas dibuat: '.Storage::disk('local')->path($relative));

        return self::SUCCESS;
    }
}
