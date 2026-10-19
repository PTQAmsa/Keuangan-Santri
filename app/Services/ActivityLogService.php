<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Enums\ActivityModule;
use App\Models\ActivityLog;
use App\Models\Invoice;
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
                'Kelompok & Kelas' => $student->kelompok.' - '.$student->kelas,
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
            'Kelompok & Kelas' => $student->kelompok.' - '.$student->kelas,
            'Kamar Asrama' => $student->kamar_asrama ?: '-',
            'Wali Santri' => trim(($student->nama_wali ?? '-').' ('.($student->hubungan_wali ?? '-').')'),
            'No. WhatsApp Wali' => $student->no_whatsapp_wali ?: '-',
            'Potongan Beasiswa' => ((float) $student->diskon_beasiswa > 0 ? number_format((float) $student->diskon_beasiswa, 0) : '0').'%',
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
     * Catat perbaikan data pembayaran (mis. nominal atau metode yang salah input).
     *
     * @param  array<string, array{0: string, 1: string}>  $changes
     */
    public static function paymentUpdated(Payment $payment, array $changes, ?int $userId = null): ActivityLog
    {
        $payment->loadMissing(['student', 'invoice', 'receipt']);

        $student = $payment->student;
        $invoice = $payment->invoice;

        $properties = [
            'Nomor Kuitansi' => $payment->receipt->receipt_number ?? '-',
            'Nomor Tagihan' => $invoice->invoice_number ?? '-',
            'Santri' => trim(($student->nama_lengkap ?? '-').' (NIS '.($student->nis ?? '-').')'),
        ];

        foreach ($changes as $label => $values) {
            $properties[$label] = $values[0].' → '.$values[1];
        }

        return self::record(
            module: ActivityModule::Payment,
            action: ActivityAction::Updated,
            description: sprintf(
                'Memperbaiki data pembayaran %s untuk %s (%s).',
                $payment->receipt->receipt_number ?? $payment->payment_code,
                $student->nama_lengkap ?? 'Santri',
                implode(', ', array_keys($changes))
            ),
            subjectId: $payment->id,
            studentId: $payment->student_id,
            properties: $properties,
            userId: $userId,
        );
    }

    /**
     * Catat pembatalan kuitansi/pembayaran beserta perubahan status tagihannya.
     */
    public static function paymentVoided(
        Payment $payment,
        string $reason,
        string $previousStatus,
        float $previousPaidAmount,
        ?int $userId = null,
    ): ActivityLog {
        $payment->loadMissing(['student', 'invoice', 'receipt']);

        $student = $payment->student;
        $invoice = $payment->invoice;

        return self::record(
            module: ActivityModule::Payment,
            action: ActivityAction::Cancelled,
            description: sprintf(
                'Membatalkan kuitansi %s senilai %s milik %s. Alasan: %s',
                $payment->receipt->receipt_number ?? $payment->payment_code,
                self::rupiah($payment->amount_paid),
                $student->nama_lengkap ?? 'Santri',
                $reason
            ),
            subjectId: $payment->id,
            studentId: $payment->student_id,
            properties: [
                'Nomor Kuitansi' => $payment->receipt->receipt_number ?? '-',
                'Nomor Tagihan' => $invoice->invoice_number ?? '-',
                'Uraian Tagihan' => $invoice->title ?? '-',
                'Santri' => trim(($student->nama_lengkap ?? '-').' (NIS '.($student->nis ?? '-').')'),
                'Nominal Dibatalkan' => self::rupiah($payment->amount_paid),
                'Alasan Pembatalan' => $reason,
                'Status Tagihan' => self::statusLabel($previousStatus).' → '.self::statusLabel($invoice->status),
                'Total Terbayar' => self::rupiah($previousPaidAmount).' → '.self::rupiah($invoice->paid_amount),
            ],
            userId: $userId,
        );
    }

    /**
     * Catat perubahan data tagihan (judul, nominal, jatuh tempo, atau catatan).
     *
     * @param  array<string, array{0: string, 1: string}>  $changes
     */
    public static function invoiceUpdated(Invoice $invoice, array $changes, ?int $userId = null): ActivityLog
    {
        $invoice->loadMissing('student');

        $properties = [
            'Nomor Tagihan' => $invoice->invoice_number,
            'Santri' => trim(($invoice->student->nama_lengkap ?? '-').' (NIS '.($invoice->student->nis ?? '-').')'),
        ];

        foreach ($changes as $label => $values) {
            $properties[$label] = $values[0].' → '.$values[1];
        }

        return self::record(
            module: ActivityModule::Invoice,
            action: ActivityAction::Updated,
            description: sprintf(
                'Mengubah %s pada tagihan %s (%s).',
                implode(', ', array_keys($changes)),
                $invoice->invoice_number,
                $invoice->student->nama_lengkap ?? 'Santri'
            ),
            subjectId: $invoice->id,
            studentId: $invoice->student_id,
            properties: $properties,
            userId: $userId,
        );
    }

    /**
     * Catat pembatalan sebuah tagihan.
     */
    public static function invoiceCancelled(Invoice $invoice, string $reason, ?int $userId = null): ActivityLog
    {
        $invoice->loadMissing('student');

        return self::record(
            module: ActivityModule::Invoice,
            action: ActivityAction::Cancelled,
            description: sprintf(
                'Membatalkan tagihan %s "%s" milik %s. Alasan: %s',
                $invoice->invoice_number,
                $invoice->title,
                $invoice->student->nama_lengkap ?? 'Santri',
                $reason
            ),
            subjectId: $invoice->id,
            studentId: $invoice->student_id,
            properties: [
                'Nomor Tagihan' => $invoice->invoice_number,
                'Uraian Tagihan' => $invoice->title,
                'Santri' => trim(($invoice->student->nama_lengkap ?? '-').' (NIS '.($invoice->student->nis ?? '-').')'),
                'Nominal Tagihan' => self::rupiah($invoice->amount),
                'Alasan Pembatalan' => $reason,
                'Status' => 'Dibatalkan',
            ],
            userId: $userId,
        );
    }

    /**
     * Catat penghapusan tagihan yang belum pernah dibayar.
     */
    public static function invoiceDeleted(Invoice $invoice, ?int $userId = null): ActivityLog
    {
        $invoice->loadMissing('student');

        return self::record(
            module: ActivityModule::Invoice,
            action: ActivityAction::Deleted,
            description: sprintf(
                'Menghapus tagihan %s "%s" milik %s.',
                $invoice->invoice_number,
                $invoice->title,
                $invoice->student->nama_lengkap ?? 'Santri'
            ),
            subjectId: $invoice->id,
            studentId: $invoice->student_id,
            properties: [
                'Nomor Tagihan' => $invoice->invoice_number,
                'Uraian Tagihan' => $invoice->title,
                'Santri' => trim(($invoice->student->nama_lengkap ?? '-').' (NIS '.($invoice->student->nis ?? '-').')'),
                'Nominal Tagihan' => self::rupiah($invoice->amount),
                'Jatuh Tempo' => $invoice->due_date?->format('d/m/Y') ?? '-',
            ],
            userId: $userId,
        );
    }

    /**
     * Catat perubahan PIN portal oleh wali santri sendiri.
     */
    public static function pinChanged(Student $student): ActivityLog
    {
        return self::record(
            module: ActivityModule::Student,
            action: ActivityAction::Updated,
            description: sprintf('Wali santri %s (NIS %s) mengganti PIN portal.', $student->nama_lengkap, $student->nis),
            subjectId: $student->id,
            studentId: $student->id,
            properties: [
                'NIS' => $student->nis,
                'Nama Santri' => $student->nama_lengkap,
                'Perubahan' => 'PIN portal wali diganti sendiri oleh wali santri',
            ],
        );
    }

    /**
     * Catat reset PIN portal oleh petugas (bendahara/administrator).
     */
    public static function pinReset(Student $student, ?int $userId = null): ActivityLog
    {
        return self::record(
            module: ActivityModule::Student,
            action: ActivityAction::Updated,
            description: sprintf(
                'Mereset PIN portal wali santri %s (NIS %s). Wali diminta mengganti PIN saat login berikutnya.',
                $student->nama_lengkap,
                $student->nis
            ),
            subjectId: $student->id,
            studentId: $student->id,
            properties: [
                'NIS' => $student->nis,
                'Nama Santri' => $student->nama_lengkap,
                'Wali Santri' => $student->nama_wali,
                'Perubahan' => 'PIN portal direset petugas',
            ],
            userId: $userId,
        );
    }

    /**
     * Catat pembuatan akun pengurus baru.
     */
    public static function userCreated(User $user, ?int $userId = null): ActivityLog
    {
        return self::record(
            module: ActivityModule::Auth,
            action: ActivityAction::Created,
            description: sprintf('Menambahkan akun pengurus %s sebagai %s.', $user->name, $user->displayTitle()),
            subjectId: $user->id,
            properties: [
                'Nama' => $user->name,
                'Jabatan' => $user->displayTitle(),
                'Email' => $user->email,
                'Peran' => $user->role?->label() ?? '-',
            ],
            userId: $userId,
        );
    }

    /**
     * Catat reset kata sandi akun pengurus oleh Administrator.
     */
    public static function userPasswordReset(User $user, ?int $userId = null): ActivityLog
    {
        return self::record(
            module: ActivityModule::Auth,
            action: ActivityAction::Updated,
            description: sprintf('Mereset kata sandi akun %s (%s).', $user->name, $user->displayTitle()),
            subjectId: $user->id,
            properties: [
                'Nama' => $user->name,
                'Jabatan' => $user->displayTitle(),
                'Email' => $user->email,
                'Perubahan' => 'Kata sandi direset oleh Administrator',
            ],
            userId: $userId,
        );
    }

    /**
     * Catat perubahan status aktif akun pengurus.
     */
    public static function userStatusChanged(User $user, ?int $userId = null): ActivityLog
    {
        return self::record(
            module: ActivityModule::Auth,
            action: ActivityAction::Updated,
            description: sprintf(
                '%s akun pengurus %s (%s).',
                $user->is_active ? 'Mengaktifkan kembali' : 'Menonaktifkan',
                $user->name,
                $user->displayTitle()
            ),
            subjectId: $user->id,
            properties: [
                'Nama' => $user->name,
                'Jabatan' => $user->displayTitle(),
                'Status Akun' => $user->is_active ? 'Aktif' : 'Nonaktif',
            ],
            userId: $userId,
        );
    }

    /**
     * Catat penggantian kata sandi oleh pengurus sendiri.
     */
    public static function passwordChanged(User $user): ActivityLog
    {
        return self::record(
            module: ActivityModule::Auth,
            action: ActivityAction::Updated,
            description: sprintf('Mengganti kata sandi akun sendiri (%s).', $user->displayTitle()),
            subjectId: $user->id,
            properties: [
                'Nama' => $user->name,
                'Jabatan' => $user->displayTitle(),
                'Perubahan' => 'Kata sandi diganti sendiri oleh pengurus',
            ],
        );
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
