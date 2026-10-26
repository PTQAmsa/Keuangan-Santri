<?php

namespace App\Http\Controllers\Admin;

use App\Models\Teacher;
use App\Models\TeacherBisyarahPayment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Data induk guru/pengajar.
 *
 * Nominal dan metode bisyarah tidak lagi disimpan di sini karena berbeda
 * setiap bulan. Pencatatan bulanan ditangani BisyarahController.
 *
 * Hanya Administrator & Bendahara yang boleh mengelola (lihat routes/web.php).
 */
class TeacherController
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search'));
        $status = (string) $request->input('status');

        $query = Teacher::query();

        if ($search !== '') {
            $query->where(function ($sub) use ($search) {
                $sub->where('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('kota_asal', 'like', "%{$search}%")
                    ->orWhere('no_whatsapp', 'like', "%{$search}%");
            });
        }

        if (array_key_exists($status, Teacher::STATUSES)) {
            $query->where('status', $status);
        }

        $teachers = $query
            ->orderByDesc('is_active')
            ->orderBy('nama_lengkap')
            ->get();

        $active = Teacher::active()->count();
        $inactive = Teacher::where('is_active', false)->count();
        $missingWhatsApp = Teacher::active()->where(fn ($sub) => $sub->whereNull('no_whatsapp')->orWhere('no_whatsapp', ''))->count();

        $monthPayments = TeacherBisyarahPayment::forPeriod(now()->year, now()->month)->get();
        $monthTotal = (float) $monthPayments->sum('amount');
        $monthRecorded = $monthPayments->count();

        $statuses = Teacher::STATUSES;
        $currentPeriodLabel = now()->translatedFormat('F Y');

        return view('admin.teachers', compact(
            'teachers',
            'search',
            'status',
            'statuses',
            'active',
            'inactive',
            'missingWhatsApp',
            'monthTotal',
            'monthRecorded',
            'currentPeriodLabel',
        ));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        Teacher::create($data);

        return redirect()
            ->route('admin.teachers')
            ->with('success', 'Data guru '.$data['nama_lengkap'].' berhasil ditambahkan. Nominal bisyarah dapat dicatat setiap bulan di tab Bisyarah Bulanan.');
    }

    public function update(Request $request, Teacher $teacher)
    {
        $data = $this->validated($request);

        $teacher->fill($data)->save();

        return redirect()
            ->route('admin.teachers')
            ->with('success', 'Data guru '.$teacher->nama_lengkap.' berhasil diperbarui.');
    }

    /**
     * Nonaktifkan / aktifkan guru tanpa menghapus riwayatnya.
     */
    public function toggleStatus(Teacher $teacher)
    {
        $teacher->is_active = ! $teacher->is_active;
        $teacher->save();

        return back()->with(
            'success',
            $teacher->is_active
                ? 'Guru '.$teacher->nama_lengkap.' diaktifkan kembali.'
                : 'Guru '.$teacher->nama_lengkap.' dinonaktifkan. Data dan riwayat bisyarahnya tetap tersimpan.'
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:100'],
            'kota_asal' => ['required', 'string', 'max:50'],
            'no_whatsapp' => ['required', 'string', 'max:25'],
            'status' => ['required', Rule::in(array_keys(Teacher::STATUSES))],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'nama_lengkap.required' => 'Nama guru wajib diisi.',
            'kota_asal.required' => 'Asal guru wajib diisi.',
            'no_whatsapp.required' => 'Nomor WhatsApp guru wajib diisi.',
            'status.required' => 'Status guru wajib dipilih.',
            'status.in' => 'Status guru tidak dikenali.',
        ], [
            'nama_lengkap' => 'nama guru',
            'kota_asal' => 'asal',
            'no_whatsapp' => 'nomor WhatsApp',
            'status' => 'status',
            'notes' => 'catatan',
        ]);

        $normalized = Teacher::normalizeWhatsApp($data['no_whatsapp']);

        if (! preg_match('/^628[0-9]{7,11}$/', $normalized)) {
            throw ValidationException::withMessages([
                'no_whatsapp' => 'Nomor WhatsApp tidak valid. Gunakan nomor Indonesia, misalnya 081234567890.',
            ]);
        }

        $data['no_whatsapp'] = $normalized;

        return $data;
    }
}
