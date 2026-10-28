<?php

namespace App\Console\Commands;

use App\Services\ArrearsImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ImportTunggakanAwal extends Command
{
    protected $signature = 'tagihan:tunggakan-awal
                            {file? : Lokasi berkas CSV tunggakan awal}
                            {--template : Buat berkas contoh CSV di storage/app/private/import}';

    protected $description = 'Impor tunggakan awal santri (tagihan yang belum lunas saat sistem mulai dipakai)';

    public function handle(ArrearsImportService $service): int
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
            $result = $service->import($file);
        } catch (\RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Selesai: %d tagihan tunggakan awal dibuat, %d baris dilewati. Total tunggakan Rp %s.',
            count($result['imported']),
            count($result['skipped']),
            number_format($result['total'], 0, ',', '.'),
        ));

        foreach ($result['skipped'] as $skipped) {
            $this->warn(sprintf('Baris %d (NIS %s) dilewati: %s', $skipped['baris'], $skipped['nis'], $skipped['alasan']));
        }

        if ($result['imported'] !== []) {
            $this->table(
                ['NIS', 'Nama', 'Tagihan', 'Sisa'],
                array_map(fn (array $row) => [$row['nis'], $row['nama'], $row['tagihan'], 'Rp '.number_format($row['sisa'], 0, ',', '.')], $result['imported']),
            );
        }

        return self::SUCCESS;
    }

    private function writeTemplate(): int
    {
        $relative = 'import/template-tunggakan-awal.csv';

        Storage::disk('local')->put($relative, implode("\n", [
            'NIS,NAMA,JENIS TAGIHAN,PERIODE,NOMINAL TAGIHAN,SUDAH DIBAYAR,JATUH TEMPO,KETERANGAN',
            '2026001,Maulana Rahma Yusuf,SPP,2026-09,1350000,0,2026-09-10,',
            '2026001,Maulana Rahma Yusuf,SPP,2026-10,1350000,500000,2026-10-10,',
            '2025003,Abdul Rasyid Fayyadh,WAKAF,,5000000,2500000,2026-12-31,Wakaf cicilan kedua',
            '2024003,Lukman Nur Hakim,BUKU,,450000,0,2026-11-30,',
        ])."\n");

        $this->info('Contoh berkas dibuat: '.Storage::disk('local')->path($relative));
        $this->line('Kolom NAMA hanya pembantu baca, yang dipakai sistem adalah NIS. Jenis tagihan: SPP, WAKAF, FASILITAS, FORMULIR, BUKU, EVENT.');

        return self::SUCCESS;
    }
}
