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
     *     updated: array<int, array{nis: string, nama: string}>,
     *     skipped: array<int, array{baris: int, nama: string, alasan: string}>,
     *     dummy_deleted: int,
     *     missing_wa: int
     * }
     */
    public function import(string $path, string $marhalah, bool $deleteDummy = false, ?int $userId = null): array
    {
        $marhalah = strtolower($marhalah) === 'putri' ? 'putri' : 'putra';
        $rows = CsvImportReader::read($path, self::COLUMN_ALIASES, ['nama']);

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
            $updated = [];
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

                // Baris yang santrinya sudah ada (NIS sama, atau nama + tahun masuk sama)
                // memperbarui data profil tanpa mengubah PIN wali.
                $existing = $nis !== ''
                    ? Student::where('nis', $nis)->first()
                    : $this->findExisting($nama, $tahunMasuk, $group);

                $wa = $this->normalizePhone($row['wa'] ?? '');
                [$hubungan, $hubunganDetail] = $this->mapHubungan($row['hubungan'] ?? '', $row['hubungan_detail'] ?? '');

                $profile = [
                    'nisn' => $this->limit($row['nisn'] ?? '', 20) ?: $existing?->nisn,
                    'nama_lengkap' => $nama,
                    'jenis_kelamin' => $group === 'putri' ? 'P' : 'L',
                    'santri_group' => $group,
                    'kelas' => $this->limit($row['kelas'] ?? '', 20) ?: ($existing?->kelas ?: 'Angkatan '.$tahunMasuk),
                    'kamar_asrama' => $this->limit($row['kamar'] ?? '', 50) ?: $existing?->kamar_asrama,
                    'tahun_masuk' => $tahunMasuk,
                    'status' => $this->mapStatus($row['status'] ?? '', $existing?->status),
                    'kota_asal' => $this->limit($row['asal'] ?? '', 50) ?: $existing?->kota_asal,
                    'alamat_lengkap' => trim($row['alamat'] ?? '') ?: $existing?->alamat_lengkap,
                    'nama_wali' => $this->limit($row['wali'] ?? '', 100) ?: ($existing?->nama_wali ?: '-'),
                    'no_whatsapp_wali' => $wa !== '' ? $wa : (string) ($existing?->no_whatsapp_wali ?? ''),
                    'hubungan_wali' => $hubungan,
                    'hubungan_wali_detail' => $hubunganDetail,
                    'diskon_beasiswa' => $this->parseDiscount($row['diskon'] ?? '', $existing?->diskon_beasiswa),
                    'bebas_wakaf' => $this->parseBoolean($row['bebas_wakaf'] ?? '', (bool) $existing?->bebas_wakaf),
                ];

                if ($profile['no_whatsapp_wali'] === '') {
                    $missingWa++;
                }

                if ($existing) {
                    $existing->fill($profile)->save();

                    $updated[] = ['nis' => $existing->nis, 'nama' => $nama];

                    continue;
                }

                $nis = $nis !== '' ? $nis : $this->nextNis($tahunMasuk);
                $pin = $this->generatePin();

                Student::create($profile + [
                    'nis' => $nis,
                    'pin_portal' => $pin,
                    'must_change_pin' => true,
                    'pin_changed_at' => null,
                    'pin_reset_requested_at' => null,
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
                description: 'Mengimpor '.count($imported).' santri baru dan memperbarui '.count($updated).' santri '.$marhalah.' dari berkas '.basename($path).'.',
                properties: [
                    'Berkas' => basename($path),
                    'Marhalah' => ucfirst($marhalah),
                    'Santri Baru' => (string) count($imported),
                    'Santri Diperbarui' => (string) count($updated),
                    'Baris Dilewati' => (string) count($skipped),
                    'Data Dummy Dihapus' => (string) $dummyDeleted,
                    'Belum Ada No. WhatsApp' => (string) $missingWa,
                ],
                userId: $userId,
            );

            return [
                'imported' => $imported,
                'updated' => $updated,
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

    private function findExisting(string $nama, int $tahunMasuk, string $group): ?Student
    {
        return Student::where('santri_group', $group)
            ->where('tahun_masuk', $tahunMasuk)
            ->whereRaw('LOWER(nama_lengkap) = ?', [mb_strtolower($nama)])
            ->first();
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
            '', 'ayah', 'ayah kandung', 'bapak', 'kandung', 'orang tua', 'orangtua' => 'Ayah Kandung',
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

    private function mapStatus(string $value, ?string $fallback = null): string
    {
        return match (strtolower(trim($value))) {
            '' => $fallback ?: 'aktif',
            'lulus', 'alumni' => 'lulus',
            'pindah', 'mutasi' => 'pindah',
            'nonaktif', 'non aktif', 'non-aktif', 'keluar' => 'nonaktif',
            default => 'aktif',
        };
    }

    private function parseDiscount(string $value, mixed $fallback = null): float
    {
        if (trim($value) === '') {
            return (float) ($fallback ?? 0);
        }

        $number = (float) str_replace(',', '.', preg_replace('/[^0-9,.]/', '', $value) ?? '');

        return max(0, min(100, $number));
    }

    private function parseBoolean(string $value, bool $fallback = false): bool
    {
        if (trim($value) === '') {
            return $fallback;
        }

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
