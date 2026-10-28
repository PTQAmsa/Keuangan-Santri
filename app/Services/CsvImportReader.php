<?php

namespace App\Services;

/**
 * Pembaca CSV rekapan bendahara: tahan terhadap BOM Excel, pemisah titik koma,
 * spasi berlebih di judul kolom, dan nama kolom yang bervariasi.
 */
class CsvImportReader
{
    /**
     * Baca berkas CSV dan petakan setiap baris ke kunci internal.
     *
     * @param  array<string, string>  $aliases  Judul kolom (huruf besar, spasi tunggal) => kunci internal
     * @param  list<string>  $requiredKeys  Kunci internal yang wajib ada di baris judul
     * @return array<int, array<string, string>>
     */
    public static function read(string $path, array $aliases, array $requiredKeys = []): array
    {
        if (! is_file($path)) {
            throw new \RuntimeException('Berkas CSV tidak ditemukan: '.$path);
        }

        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new \RuntimeException('Tidak dapat membuka berkas CSV: '.$path);
        }

        $delimiter = self::detectDelimiter($path);
        $headers = fgetcsv($handle, 0, $delimiter, '"', '');

        if ($headers === false) {
            fclose($handle);

            return [];
        }

        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headers[0]);
        $keys = array_map(fn ($header) => self::mapHeader((string) $header, $aliases), $headers);

        foreach ($requiredKeys as $required) {
            if (! in_array($required, $keys, true)) {
                fclose($handle);

                $expected = array_key_first(array_filter($aliases, fn ($key) => $key === $required)) ?? strtoupper($required);

                throw new \RuntimeException('Kolom '.$expected.' tidak ditemukan di baris judul CSV.');
            }
        }

        $rows = [];

        while (($data = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
            if ($data === [null] || implode('', array_map('trim', $data)) === '') {
                continue;
            }

            $row = [];

            foreach ($keys as $position => $key) {
                if ($key === null) {
                    continue;
                }

                $row[$key] = trim((string) ($data[$position] ?? ''));
            }

            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * Excel Indonesia sering menyimpan CSV dengan pemisah titik koma.
     */
    public static function detectDelimiter(string $path): string
    {
        $handle = fopen($path, 'r');
        $firstLine = $handle ? (string) fgets($handle) : '';

        if ($handle) {
            fclose($handle);
        }

        return substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
    }

    /**
     * Angka rupiah dari teks bebas: "1.350.000", "Rp 1,350,000", "1350000,50".
     */
    public static function parseAmount(string $value): float
    {
        $clean = preg_replace('/[^0-9,.\-]/', '', $value) ?? '';

        if ($clean === '' || $clean === '-') {
            return 0.0;
        }

        // Pemisah desimal hanya dianggap ada bila diikuti tepat 1-2 digit di akhir.
        if (preg_match('/^(-?[\d.,]*?)[.,](\d{1,2})$/', $clean, $matches) === 1) {
            $integer = preg_replace('/[^0-9\-]/', '', $matches[1]) ?? '0';

            return (float) ($integer.'.'.$matches[2]);
        }

        return (float) (preg_replace('/[^0-9\-]/', '', $clean) ?? '0');
    }

    /**
     * @param  array<string, string>  $aliases
     */
    private static function mapHeader(string $header, array $aliases): ?string
    {
        $normalized = strtoupper(trim(preg_replace('/\s+/', ' ', $header) ?? ''));

        return $aliases[$normalized] ?? null;
    }
}
