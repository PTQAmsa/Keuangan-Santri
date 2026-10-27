<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Enums\ActivityModule;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

/**
 * Impor data santri riil dari berkas CSV rekapan bendahara.
 *
 * Nama kolom CSV fleksibel (lihat {@see self::COLUMN_ALIASES}). Kolom yang
 * tidak ada akan diisi nilai bawaan yang aman: NIS dibuat otomatis dari tahun
 * masuk, kelas diisi "Angkatan {tahun}", dan setiap santri mendapat PIN portal
 * acak yang wajib diganti saat wali pertama kali masuk.
 */
class StudentImportService
{
    /**
     * Awalan NIS data dummy yang boleh dihapus saat data riil masuk.
     */
    public const DUMMY_NIS_PREFIX = 'DMY';

    /**
     * Peta nama kolom CSV (huruf besar, spasi tunggal) ke kunci internal.
     *
     * @var array<string, string>
     */
    private const COLUMN_ALIASES = [
        'NIS' => 'nis',
        'NISN' => 'nisn',
        'NAMA' => 'nama',
        'NAMA LENGKAP' => 'nama',
        'NAMA SANTRI' => 'nama',
        'JENIS KELAMIN' => 'jenis_kelamin',
        'JK' => 'jenis_kelamin',
        'MARHALAH' => 'marhalah',
        'KELAS' => 'kelas',
        'KAMAR' => 'kamar',
        'KAMAR ASRAMA' => 'kamar',
        'TAHUN MASUK' => 'tahun_masuk',
        'ANGKATAN' => 'tahun_masuk',
        'STATUS' => 'status',
        'STATUS SANTRI' => 'status',
        'ASAL' => 'asal',
        'KOTA ASAL' => 'asal',
        'ALAMAT' => 'alamat',
        'ALAMAT LENGKAP' => 'alamat',
        'NAMA WALI' => 'wali',
        'NAMA ORANG TUA/WALI' => 'wali',
        'NAMA ORANGTUA/WALI' => 'wali',
        'NAMA ORANG TUA' => 'wali',
        'HUBUNGAN' => 'hubungan',
        'HUBUNGAN WALI' => 'hubungan',
        'KETERANGAN HUBUNGAN' => 'hubungan_detail',
        'NO WA ORTU/WALI' => 'wa',
        'NO WA' => 'wa',
        'NO WHATSAPP' => 'wa',
        'NO WHATSAPP WALI' => 'wa',
        'NO. WHATSAPP WALI' => 'wa',
        'WHATSAPP' => 'wa',
        'DISKON BEASISWA' => 'diskon',
        'DISKON BEASISWA (%)' => 'diskon',
        'BEASISWA' => 'diskon',
        'BEBAS WAKAF' => 'bebas_wakaf',
    ];

    /**
     * Nomor urut NIS yang sudah terpakai per tahun masuk selama satu kali impor.
     *
     * @var array<int, int>
     */
    private array $nisSequences = [];

    /**
     * Impor satu berkas CSV untuk satu marhalah (putra/putri).
     *
     * @return array{
     *     imported: array<int, array{nis: string, nama: string, wali: string, wa: string, pin: string}>,
     *     skipped: array<int, array{baris: int, nama: string, alasan: string}>,
     *     dummy_deleted: int,
     *     missing_wa: int
     * }
     */
    public function import(string $path, string $marhalah, bool $deleteDummy = false, ?int $userId = null): array
    {
        $marhalah = strtolower($marhalah) === 'putri' ? 'putri' : 'putra';
        $rows = $this->readCsv($path);

        if ($rows === []) {
            throw new \RuntimeException('Berkas CSV tidak berisi data santri.');
        }

        $this->nisSequences = [];

        return DB::transaction(function () use ($rows, $marhalah, $deleteDummy, $userId, $path) {
            $dummyDeleted = 0;

            if ($deleteDummy) {
                $dummyDeleted = $this->deleteDummyStudents($marhalah);
            }

            $imported = [];
            $skipped = [];
            $missingWa = 0;

            foreach ($rows as $index => $row) {
                $line = $index + 2;
                $nama = $this->limit($row['nama'] ?? '', 100);

                if ($nama === '') {
                    $skipped[] = ['baris' => $line, 'nama' => '-', 'alasan' => 'Nama kosong'];

                    continue;
                }

                $tahunMasuk = $this->parseYear($row['tahun_masuk'] ?? '');
                $group = $this->resolveGroup($row['marhalah'] ?? '', $row['jenis_kelamin'] ?? '', $marhalah);
                $nis = $this->limit($row['nis'] ?? '', 20);

                if ($nis === '') {
                    if ($this->alreadyExists($nama, $tahunMasuk, $group)) {
                        $skipped[] = ['baris' => $line, 'nama' => $nama, 'alasan' => 'Sudah ada santri dengan nama dan tahun masuk yang sama'];

                        continue;
                    }

                    $nis = $this->nextNis($tahunMasuk);
                } elseif (Student::where('nis', $nis)->exists()) {
                    $skipped[] = ['baris' => $line, 'nama' => $nama, 'alasan' => 'NIS '.$nis.' sudah terdaftar'];

                    continue;
                }

                $wa = $this->normalizePhone($row['wa'] ?? '');

                if ($wa === '') {
                    $missingWa++;
                }

                $pin = $this->generatePin();
                [$hubungan, $hubunganDetail] = $this->mapHubungan($row['hubungan'] ?? '', $row['hubungan_detail'] ?? '');

                Student::create([
                    'nis' => $nis,
                    'nisn' => $this->limit($row['nisn'] ?? '', 20) ?: null,
                    'nama_lengkap' => $nama,
                    'jenis_kelamin' => $group === 'putri' ? 'P' : 'L',
                    'santri_group' => $group,
                    'kelas' => $this->limit($row['kelas'] ?? '', 20) ?: 'Angkatan '.$tahunMasuk,
                    'kamar_asrama' => $this->limit($row['kamar'] ?? '', 50) ?: null,
                    'tahun_masuk' => $tahunMasuk,
                    'status' => $this->mapStatus($row['status'] ?? ''),
                    'kota_asal' => $this->limit($row['asal'] ?? '', 50) ?: null,
                    'alamat_lengkap' => trim($row['alamat'] ?? '') ?: null,
                    'nama_wali' => $this->limit($row['wali'] ?? '', 100) ?: '-',
                    'no_whatsapp_wali' => $wa,
                    'hubungan_wali' => $hubungan,
                    'hubungan_wali_detail' => $hubunganDetail,
                    'pin_portal' => $pin,
                    'must_change_pin' => true,
                    'pin_changed_at' => null,
                    'pin_reset_requested_at' => null,
                    'diskon_beasiswa' => $this->parseDiscount($row['diskon'] ?? ''),
                    'bebas_wakaf' => $this->parseBoolean($row['bebas_wakaf'] ?? ''),
                ]);

                $imported[] = [
                    'nis' => $nis,
                    'nama' => $nama,
                    'wali' => $this->limit($row['wali'] ?? '', 100) ?: '-',
                    'wa' => $wa,
                    'pin' => $pin,
                ];
            }

            ActivityLogService::record(
                module: ActivityModule::Student,
                action: ActivityAction::Created,
                description: 'Mengimpor '.count($imported).' data santri '.$marhalah.' dari berkas '.basename($path).'.',
                properties: [
                    'Berkas' => basename($path),
                    'Marhalah' => ucfirst($marhalah),
                    'Santri Diimpor' => (string) count($imported),
                    'Baris Dilewati' => (string) count($skipped),
                    'Data Dummy Dihapus' => (string) $dummyDeleted,
                    'Belum Ada No. WhatsApp' => (string) $missingWa,
                ],
                userId: $userId,
            );

            return [
                'imported' => $imported,
                'skipped' => $skipped,
                'dummy_deleted' => $dummyDeleted,
                'missing_wa' => $missingWa,
            ];
        });
    }

    /**
     * Hapus santri dummy satu marhalah beserta tagihan dan pembayarannya (cascade).
     */
    private function deleteDummyStudents(string $marhalah): int
    {
        $students = Student::where('santri_group', $marhalah)
            ->where('nis', 'like', self::DUMMY_NIS_PREFIX.'%')
            ->get();

        foreach ($students as $student) {
            $student->delete();
        }

        return $students->count();
    }

    /**
     * Baca CSV dan petakan nama kolom ke kunci internal.
     *
     * @return array<int, array<string, string>>
     */
    private function readCsv(string $path): array
    {
        if (! is_file($path)) {
            throw new \RuntimeException('Berkas CSV tidak ditemukan: '.$path);
        }

        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new \RuntimeException('Tidak dapat membuka berkas CSV: '.$path);
        }

        $headers = fgetcsv($handle, 0, ',', '"', '');

        if ($headers === false) {
            fclose($handle);

            return [];
        }

        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headers[0]);
        $keys = array_map(fn ($header) => $this->mapHeader((string) $header), $headers);

        if (! in_array('nama', $keys, true)) {
            fclose($handle);

            throw new \RuntimeException('Kolom NAMA tidak ditemukan di baris judul CSV.');
        }

        $rows = [];

        while (($data = fgetcsv($handle, 0, ',', '"', '')) !== false) {
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

    private function mapHeader(string $header): ?string
    {
        $normalized = strtoupper(trim(preg_replace('/\s+/', ' ', $header) ?? ''));

        return self::COLUMN_ALIASES[$normalized] ?? null;
    }

    private function resolveGroup(string $marhalah, string $jenisKelamin, string $default): string
    {
        $marhalah = strtolower(trim($marhalah));

        if (in_array($marhalah, ['putra', 'putri'], true)) {
            return $marhalah;
        }

        return match (strtoupper(trim($jenisKelamin))) {
            'P', 'PEREMPUAN' => 'putri',
            'L', 'LAKI-LAKI' => 'putra',
            default => $default,
        };
    }

    private function alreadyExists(string $nama, int $tahunMasuk, string $group): bool
    {
        return Student::where('santri_group', $group)
            ->where('tahun_masuk', $tahunMasuk)
            ->whereRaw('LOWER(nama_lengkap) = ?', [mb_strtolower($nama)])
            ->exists();
    }

    /**
     * NIS otomatis: tahun masuk + 3 digit urut, contoh 2026001.
     */
    private function nextNis(int $tahunMasuk): string
    {
        if (! isset($this->nisSequences[$tahunMasuk])) {
            $last = Student::where('nis', 'like', $tahunMasuk.'%')
                ->whereRaw('LENGTH(nis) = 7')
                ->orderByDesc('nis')
                ->value('nis');

            $this->nisSequences[$tahunMasuk] = $last ? (int) substr((string) $last, 4) : 0;
        }

        do {
            $this->nisSequences[$tahunMasuk]++;
            $nis = $tahunMasuk.str_pad((string) $this->nisSequences[$tahunMasuk], 3, '0', STR_PAD_LEFT);
        } while (Student::where('nis', $nis)->exists());

        return $nis;
    }

    private function parseYear(string $value): int
    {
        $year = (int) round((float) preg_replace('/[^0-9.]/', '', $value));

        return $year >= 2000 && $year <= 2100 ? $year : (int) now()->year;
    }

    /**
     * Simpan nomor dalam format 08xxxxxxxxxx seperti data yang diinput petugas.
     */
    private function normalizePhone(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '62')) {
            $digits = '0'.substr($digits, 2);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '0'.$digits;
        }

        return $this->limit($digits, 25);
    }

    /**
     * @return array{0: string, 1: ?string}
     */
    private function mapHubungan(string $hubungan, string $detail): array
    {
        $normalized = strtolower(trim($hubungan));

        $mapped = match ($normalized) {
            '', 'ayah', 'ayah kandung', 'bapak' => 'Ayah Kandung',
            'ibu', 'ibu kandung' => 'Ibu Kandung',
            'ayah angkat' => 'Ayah Angkat',
            'ibu angkat' => 'Ibu Angkat',
            'wali' => 'Wali',
            default => 'Lainnya',
        };

        if ($mapped !== 'Lainnya') {
            return [$mapped, null];
        }

        $detail = $this->limit($detail, 100) ?: $this->limit(ucwords($normalized), 100);

        return ['Lainnya', $detail ?: 'Lainnya'];
    }

    private function mapStatus(string $value): string
    {
        return match (strtolower(trim($value))) {
            'lulus', 'alumni' => 'lulus',
            'pindah', 'mutasi' => 'pindah',
            'nonaktif', 'non aktif', 'non-aktif', 'keluar' => 'nonaktif',
            default => 'aktif',
        };
    }

    private function parseDiscount(string $value): float
    {
        $number = (float) str_replace(',', '.', preg_replace('/[^0-9,.]/', '', $value) ?? '');

        return max(0, min(100, $number));
    }

    private function parseBoolean(string $value): bool
    {
        return in_array(strtolower(trim($value)), ['ya', 'y', 'yes', 'true', '1', 'bebas'], true);
    }

    private function generatePin(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function limit(string $value, int $limit): string
    {
        return mb_substr(trim($value), 0, $limit);
    }
}
