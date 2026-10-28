<?php

namespace App\Http\Controllers\Admin;

use App\Models\Student;
use App\Services\ActivityLogService;
use App\Services\StudentFinancialCardService;
use Illuminate\Http\Request;

class StudentController
{
    /**
     * Daftar seluruh santri beserta status keuangannya (aktif / sudah keluar).
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $kelompok = $request->input('kelompok');

        // Kolom yang bisa diurutkan lewat klik judul kolom.
        $allowedSorts = [
            'student' => 'nama_lengkap',
            'kelas' => 'kelas',
            'wali' => 'nama_wali',
            'beasiswa' => 'diskon_beasiswa',
            'sisa_tagihan' => null, // dihitung dari tagihan, diurutkan setelah data diambil
            'status' => 'status',
        ];

        $sortBy = (string) $request->input('sort_by', 'student');
        $sortOrder = strtolower((string) $request->input('sort_order', 'asc')) === 'desc' ? 'desc' : 'asc';

        if (! array_key_exists($sortBy, $allowedSorts)) {
            $sortBy = 'student';
        }

        $query = Student::query()
            ->with(['invoices' => function ($query) {
                $query->whereIn('status', ['unpaid', 'partial']);
            }])
            ->kelompok($kelompok);

        if ($allowedSorts[$sortBy] !== null) {
            $query->orderBy($allowedSorts[$sortBy], $sortOrder);
        }

        if ($sortBy !== 'student') {
            $query->orderBy('nama_lengkap');
        }

        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->whereLike('nama_lengkap', "%{$search}%")
                    ->orWhereLike('nis', "%{$search}%")
                    ->orWhereLike('kelas', "%{$search}%")
                    ->orWhereLike('nama_wali', "%{$search}%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        $students = $query->get();

        if ($sortBy === 'sisa_tagihan') {
            $students = $students
                ->sortBy(fn (Student $student) => (float) $student->invoices->sum(fn ($invoice) => $invoice->remaining_amount), SORT_REGULAR, $sortOrder === 'desc')
                ->values();
        }

        $totalAktif = Student::where('status', 'aktif')->count();
        $totalNonaktif = Student::where('status', '!=', 'aktif')->count();
        $totalBeasiswa = Student::where('diskon_beasiswa', '>', 0)->count();
        $totalPutra = Student::where('santri_group', 'putra')->count();
        $totalPutri = Student::where('santri_group', 'putri')->count();

        return view('admin.students', compact(
            'students',
            'search',
            'status',
            'kelompok',
            'sortBy',
            'sortOrder',
            'totalAktif',
            'totalNonaktif',
            'totalBeasiswa',
            'totalPutra',
            'totalPutri'
        ));
    }

    /**
     * Tambah santri baru (santri masuk) beserta data keuangannya.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'nis' => ['required', 'string', 'max:20', 'unique:students,nis'],
            'nisn' => ['nullable', 'string', 'max:20'],
            'nama_lengkap' => ['required', 'string', 'max:100'],
            'jenis_kelamin' => ['required', 'in:L,P'],
            'santri_group' => ['required', 'in:putra,putri'],
            'kelas' => ['required', 'string', 'max:20'],
            'kamar_asrama' => ['nullable', 'string', 'max:50'],
            'tahun_masuk' => ['required', 'integer', 'min:2000', 'max:2100'],
            'status' => ['required', 'in:aktif,nonaktif'],
            'kota_asal' => ['nullable', 'string', 'max:50'],
            'nama_wali' => ['required', 'string', 'max:100'],
            'no_whatsapp_wali' => ['required', 'string', 'max:25'],
            'hubungan_wali' => ['required', 'in:Ayah Kandung,Ibu Kandung,Ayah Angkat,Ibu Angkat,Wali,Lainnya'],
            'hubungan_wali_detail' => ['nullable', 'required_if:hubungan_wali,Lainnya', 'string', 'max:100'],
            'pin_portal' => ['required', 'digits:6'],
            'diskon_beasiswa' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'bebas_wakaf' => ['nullable', 'boolean'],
        ], [
            'hubungan_wali_detail.required_if' => 'Keterangan hubungan wajib diisi bila Anda memilih "Lainnya".',
        ], [
            'nis' => 'NIS',
            'nama_lengkap' => 'nama lengkap',
            'nama_wali' => 'nama wali',
            'no_whatsapp_wali' => 'nomor WhatsApp wali',
            'hubungan_wali_detail' => 'keterangan hubungan',
            'pin_portal' => 'PIN portal',
        ]);

        $data['bebas_wakaf'] = $request->boolean('bebas_wakaf');
        $data['diskon_beasiswa'] = $data['diskon_beasiswa'] ?? 0;

        // Santri baru selalu diminta mengganti PIN bawaan saat login pertama.
        $data['must_change_pin'] = true;

        $student = Student::create($data);

        ActivityLogService::studentCreated($student, auth()->id());

        return redirect()
            ->route('admin.students')
            ->with('success', 'Data santri '.$student->nama_lengkap.' (NIS '.$student->nis.') berhasil ditambahkan.');
    }

    /**
     * Reset PIN portal wali oleh petugas (bendahara/administrator).
     */
    public function resetPin(Student $student)
    {
        $student->pin_portal = '123456';
        $student->must_change_pin = true;
        $student->pin_reset_requested_at = null;
        $student->save();

        ActivityLogService::pinReset($student, auth()->id());

        return back()->with('success', 'PIN portal '.$student->nama_lengkap.' direset ke 123456. Wali akan diminta mengganti PIN saat login berikutnya.');
    }

    /**
     * Aktifkan / nonaktifkan santri (mis. santri keluar atau kembali mondok).
     */
    public function toggleStatus(Student $student)
    {
        $previousStatus = $student->status;

        $student->status = $previousStatus === 'aktif' ? 'nonaktif' : 'aktif';
        $student->save();

        ActivityLogService::studentStatusChanged($student, $previousStatus, auth()->id());

        $message = $student->status === 'aktif'
            ? 'Santri '.$student->nama_lengkap.' diaktifkan kembali.'
            : 'Santri '.$student->nama_lengkap.' dinonaktifkan (keluar). Tagihan barunya tidak akan dibuat lagi.';

        return back()->with('success', $message);
    }

    /**
     * Kartu keuangan santri: riwayat tagihan, pembayaran, dan kuitansi.
     */
    public function card(Student $student, StudentFinancialCardService $cardService)
    {
        return view('student-card', $cardService->build($student));
    }

    /**
     * Unduh daftar tagihan santri (maksimal satu semester berjalan).
     */
    public function cardInvoicesPdf(Student $student, StudentFinancialCardService $cardService)
    {
        return $cardService->invoicesPdf($student);
    }

    /**
     * Unduh riwayat pembayaran & kuitansi santri (maksimal satu semester berjalan).
     */
    public function cardPaymentsPdf(Student $student, StudentFinancialCardService $cardService)
    {
        return $cardService->paymentsPdf($student);
    }
}
