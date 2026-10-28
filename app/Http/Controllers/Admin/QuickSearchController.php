<?php

namespace App\Http\Controllers\Admin;

use App\Models\Payment;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherBisyarahPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Pencarian cepat dari kotak cari di header panel.
 *
 * Hasil dikelompokkan sesuai prioritas kerja Bendahara: santri & wali dulu,
 * lalu kuitansi, lalu guru. Semua peran panel melihat hasil yang sama;
 * Pengasuh membuka halaman tujuan dalam mode lihat saja.
 */
class QuickSearchController
{
    private const LIMIT_PER_GROUP = 5;

    public function __invoke(Request $request): JsonResponse
    {
        $term = trim((string) $request->input('q', ''));

        if (mb_strlen($term) < 2) {
            return response()->json(['query' => $term, 'groups' => []]);
        }

        $like = '%'.$term.'%';

        $groups = [
            $this->studentGroup($like),
            $this->receiptGroup($like),
            $this->teacherGroup($like),
        ];

        return response()->json([
            'query' => $term,
            'groups' => array_values(array_filter($groups, fn (array $group) => $group['items'] !== [])),
        ]);
    }

    /**
     * @return array{key: string, label: string, icon: string, items: list<array<string, mixed>>}
     */
    private function studentGroup(string $like): array
    {
        $students = Student::query()
            ->where(function ($query) use ($like) {
                $query->whereLike('nama_lengkap', $like)
                    ->orWhereLike('nis', $like)
                    ->orWhereLike('nama_wali', $like)
                    ->orWhereLike('no_whatsapp_wali', $like);
            })
            ->orderByRaw("CASE WHEN status = 'aktif' THEN 0 ELSE 1 END")
            ->orderBy('nama_lengkap')
            ->limit(self::LIMIT_PER_GROUP)
            ->get();

        $items = $students->map(function (Student $student): array {
            $arrears = (float) $student->total_tunggakan;

            return [
                'title' => $student->nama_lengkap,
                'subtitle' => 'NIS '.$student->nis.' • Kelas '.$student->kelas.' '.$student->kelompok.' • Wali: '.$student->nama_wali,
                'meta' => $arrears > 0
                    ? 'Tunggakan Rp '.number_format($arrears, 0, ',', '.')
                    : ($student->status === 'aktif' ? 'Tidak ada tunggakan' : 'Status: '.ucfirst($student->status)),
                'metaClass' => $arrears > 0 ? 'text-rose-600' : 'text-emerald-600',
                'url' => route('admin.cashier', ['student_id' => $student->id]),
                'actions' => [
                    ['label' => 'Kasir', 'icon' => 'fa-cash-register', 'url' => route('admin.cashier', ['student_id' => $student->id])],
                    ['label' => 'Kartu', 'icon' => 'fa-address-card', 'url' => route('admin.students.card', $student)],
                ],
            ];
        })->all();

        return ['key' => 'students', 'label' => 'Santri & Wali', 'icon' => 'fa-user-graduate', 'items' => $items];
    }

    /**
     * @return array{key: string, label: string, icon: string, items: list<array<string, mixed>>}
     */
    private function receiptGroup(string $like): array
    {
        $payments = Payment::with(['student', 'receipt', 'invoice'])
            ->where(function ($query) use ($like) {
                $query->whereLike('payment_code', $like)
                    ->orWhereHas('receipt', fn ($sub) => $sub->whereLike('receipt_number', $like));
            })
            ->latest('payment_date')
            ->latest('id')
            ->limit(self::LIMIT_PER_GROUP)
            ->get();

        $items = $payments->map(function (Payment $payment): array {
            $number = $payment->receipt?->receipt_number ?? $payment->payment_code;
            $voided = $payment->voided_at !== null;

            return [
                'title' => $number,
                'subtitle' => ($payment->student?->nama_lengkap ?? '-').' • '.($payment->invoice?->title ?? 'Pembayaran').' • '.$payment->payment_date?->format('d/m/Y'),
                'meta' => 'Rp '.number_format((float) $payment->amount_paid, 0, ',', '.').($voided ? ' • DIBATALKAN' : ''),
                'metaClass' => $voided ? 'text-gray-400 line-through' : 'text-emerald-600',
                'url' => route('admin.transactions', ['search' => $number, 'status' => 'all']),
                'actions' => array_values(array_filter([
                    $payment->receipt ? ['label' => 'PDF', 'icon' => 'fa-file-pdf', 'url' => route('receipt.download', $payment->receipt->id)] : null,
                ])),
            ];
        })->all();

        return ['key' => 'receipts', 'label' => 'Kuitansi / Transaksi', 'icon' => 'fa-receipt', 'items' => $items];
    }

    /**
     * @return array{key: string, label: string, icon: string, items: list<array<string, mixed>>}
     */
    private function teacherGroup(string $like): array
    {
        $teachers = Teacher::query()
            ->where(function ($query) use ($like) {
                $query->whereLike('nama_lengkap', $like)
                    ->orWhereLike('kota_asal', $like)
                    ->orWhereLike('no_whatsapp', $like);
            })
            ->orderByDesc('is_active')
            ->orderBy('nama_lengkap')
            ->limit(self::LIMIT_PER_GROUP)
            ->get();

        $recordedThisMonth = TeacherBisyarahPayment::forPeriod(now()->year, now()->month)
            ->whereIn('teacher_id', $teachers->pluck('id'))
            ->get()
            ->keyBy('teacher_id');

        $period = now()->format('Y-m');

        $items = $teachers->map(function (Teacher $teacher) use ($recordedThisMonth, $period): array {
            $payment = $recordedThisMonth->get($teacher->id);

            return [
                'title' => $teacher->nama_lengkap,
                'subtitle' => $teacher->statusLabel().' • '.($teacher->kota_asal ?: '-').(! $teacher->is_active ? ' • Nonaktif' : ''),
                'meta' => $payment
                    ? 'Bisyarah '.now()->translatedFormat('F').' sudah dicatat (Rp '.number_format((float) $payment->amount, 0, ',', '.').')'
                    : 'Bisyarah '.now()->translatedFormat('F').' belum dicatat',
                'metaClass' => $payment ? 'text-emerald-600' : 'text-amber-600',
                'url' => route('admin.bisyarah', ['period' => $period]).'#guru-'.$teacher->id,
                'actions' => [
                    ['label' => 'Bisyarah', 'icon' => 'fa-money-bill-wave', 'url' => route('admin.bisyarah', ['period' => $period]).'#guru-'.$teacher->id],
                    ['label' => 'Data', 'icon' => 'fa-user-tie', 'url' => route('admin.teachers', ['search' => $teacher->nama_lengkap])],
                ],
            ];
        })->all();

        return ['key' => 'teachers', 'label' => 'Guru', 'icon' => 'fa-user-tie', 'items' => $items];
    }
}
