<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Enums\ActivityModule;
use App\Models\ActivityLog;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Pencatat log aktivitas perubahan data keuangan santri.
 *
 * Setiap aksi pengurus dicatat sebagai SATU baris log, termasuk aksi massal
 * (mis. generate SPP sebulan untuk semua santri) yang diringkas dalam satu baris.
 */
class ActivityLogService
{
    /**
     * Simpan satu baris log aktivitas.
     *
     * @param  array<string, string>  $properties
     */
    public static function record(
        ActivityModule $module,
        ActivityAction $action,
        string $description,
        ?int $subjectId = null,
        ?int $studentId = null,
        array $properties = [],
        ?int $userId = null,
        ?\DateTimeInterface $createdAt = null,
    ): ActivityLog {
        $log = new ActivityLog([
            'user_id' => $userId ?? Auth::id(),
            'module' => $module,
            'action' => $action,
            'subject_id' => $subjectId,
            'student_id' => $studentId,
            'description' => $description,
            'properties' => $properties,
            'ip_address' => request()->ip(),
        ]);

        // Waktu aksi dapat ditentukan manual (mis. saat impor data historis).
        if ($createdAt !== null) {
            $log->created_at = $createdAt;
        }

        $log->save();

        return $log;
    }

    /**
     * Catat pembayaran santri beserta perubahan status tagihannya.
     */
    public static function paymentRecorded(
        Payment $payment,
        ?string $previousStatus = null,
        ?float $previousPaidAmount = null,
        ?int $userId = null,
        ?\DateTimeInterface $createdAt = null,
    ): ActivityLog {
        $payment->loadMissing(['student', 'invoice', 'receipt']);

        $student = $payment->student;
        $invoice = $payment->invoice;
        $method = $payment->payment_method === 'cash' ? 'Tunai' : 'Transfer';

        $properties = [
            'Nomor Kuitansi' => $payment->receipt->receipt_number ?? '-',
            'Nomor Tagihan' => $invoice->invoice_number ?? '-',
            'Uraian Tagihan' => $invoice->title ?? '-',
            'Santri' => trim(($student->nama_lengkap ?? '-').' (NIS '.($student->nis ?? '-').')'),
            'Diterima Dari' => $payment->received_from ?: ($student->nama_wali ?? '-'),
            'Nominal Bayar' => self::rupiah($payment->amount_paid),
            'Metode Bayar' => $method,
        ];

        if ($previousStatus !== null) {
            $properties['Status Tagihan'] = self::statusLabel($previousStatus).' → '.self::statusLabel($invoice->status);
            $properties['Total Terbayar'] = self::rupiah($previousPaidAmount).' → '.self::rupiah($invoice->paid_amount);
        }

        return self::record(
            module: ActivityModule::Payment,
            action: ActivityAction::Created,
            description: sprintf(
                'Mencatat pembayaran %s (%s) untuk %s pada tagihan %s.',
                self::rupiah($payment->amount_paid),
                $method,
                $student->nama_lengkap ?? 'Santri',
                $invoice->invoice_number ?? '-'
            ),
            subjectId: $payment->id,
            studentId: $payment->student_id,
            properties: $properties,
            userId: $userId,
            createdAt: $createdAt,
        );
    }

    /**
     * Catat penerbitan tagihan baru (bisa satu santri atau sekaligus banyak santri).
     *
     * @param  Collection<int, Student>  $students
     */
    public static function invoiceCreated(
        string $categoryName,
        string $title,
        float $amount,
        string $dueDate,
        string $targetLabel,
        Collection $students,
        ?int $userId = null,
    ): ActivityLog {
        $count = $students->count();
        $singleStudent = $count === 1 ? $students->first() : null;

        return self::record(
            module: ActivityModule::Invoice,
            action: ActivityAction::Created,
            description: sprintf('Menerbitkan %d tagihan "%s" untuk %s.', $count, $title, $targetLabel),
            studentId: $singleStudent?->id,
            properties: [
                'Jumlah Tagihan' => $count.' tagihan',
                'Kategori' => $categoryName,
                'Uraian Tagihan' => $title,
                'Nominal per Santri' => self::rupiah($amount),
                'Total Nilai' => self::rupiah($amount * $count),
                'Jatuh Tempo' => $dueDate !== '' ? Carbon::parse($dueDate)->format('d/m/Y') : '-',
                'Target' => $targetLabel,
            ],
            userId: $userId,
        );
    }

    /**
     * Catat penerbitan tagihan SPP massal bulanan.
     */
    public static function monthlySppGenerated(
        string $categoryName,
        string $periodLabel,
        int $invoiceCount,
        float $totalAmount,
        ?int $userId = null,
    ): ActivityLog {
        return self::record(
            module: ActivityModule::Invoice,
            action: ActivityAction::Created,
            description: sprintf(
                'Menerbitkan %d tagihan %s untuk periode %s (total %s).',
                $invoiceCount,
                $categoryName,
                $periodLabel,
                self::rupiah($totalAmount)
            ),
            properties: [
                'Jumlah Tagihan Diterbitkan' => $invoiceCount.' tagihan',
                'Kategori' => $categoryName,
                'Periode' => $periodLabel,
                'Total Nilai Tagihan' => self::rupiah($totalAmount),
            ],
            userId: $userId,
        );
    }

    /**
     * Catat login pengurus ke panel admin.
     */
    public static function loggedIn(User $user): ActivityLog
    {
        return self::record(
            module: ActivityModule::Auth,
            action: ActivityAction::Login,
            description: sprintf('Login ke panel admin sebagai %s.', $user->displayTitle()),
            subjectId: $user->id,
            properties: [
                'Nama' => $user->name,
                'Jabatan' => $user->displayTitle(),
                'Email' => $user->email,
            ],
            userId: $user->id,
        );
    }

    /**
     * Catat penambahan data santri baru (santri masuk).
     */
    public static function studentCreated(Student $student, ?int $userId = null): ActivityLog
    {
        return self::record(
            module: ActivityModule::Student,
            action: ActivityAction::Created,
            description: sprintf(
                'Menambahkan data santri baru %s (NIS %s) kelas %s.',
                $student->nama_lengkap,
                $student->nis,
                $student->kelas
            ),
            subjectId: $student->id,
            studentId: $student->id,
            properties: self::studentProperties($student),
            userId: $userId,
        );
    }

    /**
     * Catat perubahan status santri (mis. santri keluar / aktif kembali).
     */
    public static function studentStatusChanged(Student $student, string $previousStatus, ?int $userId = null): ActivityLog
    {
        $label = $previousStatus === 'aktif' ? 'Menonaktifkan' : 'Mengaktifkan kembali';

        return self::record(
            module: ActivityModule::Student,
            action: ActivityAction::Updated,
            description: sprintf(
                '%s data keuangan %s (NIS %s) menjadi %s.',
                $label,
                $student->nama_lengkap,
                $student->nis,
                self::studentStatusLabel($student->status)
            ),
            subjectId: $student->id,
            studentId: $student->id,
            properties: [
                'Santri' => $student->nama_lengkap.' (NIS '.$student->nis.')',
                'Kelas' => $student->jenjang.' - '.$student->kelas,
                'Status' => self::studentStatusLabel($previousStatus).' → '.self::studentStatusLabel($student->status),
                'Tunggakan Tercatat' => self::rupiah($student->total_tunggakan),
            ],
            userId: $userId,
        );
    }

    /**
     * Rincian data santri untuk baris log penambahan santri baru.
     *
     * @return array<string, string>
     */
    private static function studentProperties(Student $student): array
    {
        return [
            'NIS' => $student->nis,
            'Nama Santri' => $student->nama_lengkap,
            'Jenjang & Kelas' => $student->jenjang.' - '.$student->kelas,
            'Kamar Asrama' => $student->kamar_asrama ?: '-',
            'Wali Santri' => trim(($student->nama_wali ?? '-').' ('.($student->hubungan_wali ?? '-').')'),
            'No. WhatsApp Wali' => $student->no_whatsapp_wali ?: '-',
            'Diskon Beasiswa' => ((float) $student->diskon_beasiswa > 0 ? number_format((float) $student->diskon_beasiswa, 0) : '0').'%',
            'Bebas Wakaf' => $student->bebas_wakaf ? 'Ya' : 'Tidak',
            'Status' => self::studentStatusLabel($student->status),
        ];
    }

    /**
     * Label status santri dalam Bahasa Indonesia.
     */
    private static function studentStatusLabel(?string $status): string
    {
        return match ($status) {
            'aktif' => 'Aktif',
            'nonaktif' => 'Nonaktif (Keluar)',
            'lulus' => 'Lulus',
            'pindah' => 'Pindah',
            default => (string) $status,
        };
    }

    /**
     * Format nominal menjadi teks Rupiah.
     */
    private static function rupiah(float|int|string|null $amount): string
    {
        return 'Rp '.number_format((float) $amount, 0, ',', '.');
    }

    /**
     * Label status tagihan dalam Bahasa Indonesia.
     */
    private static function statusLabel(?string $status): string
    {
        return match ($status) {
            'paid' => 'Lunas',
            'partial' => 'Dicicil',
            'unpaid' => 'Belum Bayar',
            default => (string) $status,
        };
    }
}
