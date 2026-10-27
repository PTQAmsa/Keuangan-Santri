<?php

namespace App\Console\Commands;

use App\Services\StudentImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ImportSantri extends Command
{
    protected $signature = 'santri:import
                            {file : Lokasi berkas CSV rekapan bendahara}
                            {--marhalah=putra : putra atau putri}
                            {--hapus-dummy : Hapus santri dummy (NIS DMY...) marhalah ini beserta tagihan contohnya}';

    protected $description = 'Impor data santri riil dari CSV dan simpan daftar PIN awal portal wali';

    public function handle(StudentImportService $service): int
    {
        $file = (string) $this->argument('file');
        $marhalah = strtolower((string) $this->option('marhalah')) === 'putri' ? 'putri' : 'putra';

        if (! is_file($file)) {
            $this->error('Berkas tidak ditemukan: '.$file);

            return self::FAILURE;
        }

        try {
            $result = $service->import(
                path: $file,
                marhalah: $marhalah,
                deleteDummy: (bool) $this->option('hapus-dummy'),
            );
        } catch (\RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Selesai: %d santri %s diimpor, %d baris dilewati, %d santri dummy dihapus.',
            count($result['imported']),
            $marhalah,
            count($result['skipped']),
            $result['dummy_deleted'],
        ));

        foreach ($result['skipped'] as $skipped) {
            $this->warn(sprintf('Baris %d (%s) dilewati: %s', $skipped['baris'], $skipped['nama'], $skipped['alasan']));
        }

        if ($result['missing_wa'] > 0) {
            $this->warn($result['missing_wa'].' santri belum punya nomor WhatsApp wali. Lengkapi lewat menu Edit Santri agar wali bisa masuk portal.');
        }

        if ($result['imported'] === []) {
            return self::SUCCESS;
        }

        $pinPath = $this->savePinList($result['imported'], $marhalah);

        $this->table(
            ['NIS', 'Nama', 'Wali', 'No. WA', 'PIN Awal'],
            array_map(fn (array $row) => [$row['nis'], $row['nama'], $row['wali'], $row['wa'] ?: '-', $row['pin']], $result['imported']),
        );

        $this->line('Daftar PIN awal disimpan di: '.$pinPath);
        $this->warn('PIN hanya tersimpan di berkas ini. Bagikan ke wali lalu hapus berkasnya.');

        return self::SUCCESS;
    }

    /**
     * @param  array<int, array{nis: string, nama: string, wali: string, wa: string, pin: string}>  $rows
     */
    private function savePinList(array $rows, string $marhalah): string
    {
        $relative = 'import/pin-santri-'.$marhalah.'-'.now()->format('Ymd-His').'.csv';

        $lines = ['NIS,Nama,Wali,No. WA,PIN Awal'];

        foreach ($rows as $row) {
            $lines[] = implode(',', array_map(
                fn (string $value) => '"'.str_replace('"', '""', $value).'"',
                [$row['nis'], $row['nama'], $row['wali'], $row['wa'], $row['pin']],
            ));
        }

        Storage::disk('local')->put($relative, implode("\n", $lines)."\n");

        return Storage::disk('local')->path($relative);
    }
}
