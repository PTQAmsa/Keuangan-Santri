<?php

namespace App\Http\Controllers\Admin;

use App\Models\Student;
use App\Services\ActivityLogService;
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

        $query = Student::query()
            ->with(['invoices' => function ($query) {
                $query->whereIn('status', ['unpaid', 'partial']);
            }])
            ->orderBy('nama_lengkap');

        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%")
                    ->orWhere('kelas', 'like', "%{$search}%")
                    ->orWhere('nama_wali', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        $students = $query->get();

        $totalAktif = Student::where('status', 'aktif')->count();
        $totalNonaktif = Student::where('status', '!=', 'aktif')->count();
        $totalBeasiswa = Student::where('diskon_beasiswa', '>', 0)->count();

        return view('admin.students', compact(
            'students',
            'search',
            'status',
            'totalAktif',
            'totalNonaktif',
            'totalBeasiswa'
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
            'jenjang' => ['required', 'in:SMP,SMA'],
            'kelas' => ['required', 'string', 'max:20'],
            'kamar_asrama' => ['nullable', 'string', 'max:50'],
            'tahun_masuk' => ['required', 'integer', 'min:2000', 'max:2100'],
            'status' => ['required', 'in:aktif,nonaktif'],
            'kota_asal' => ['nullable', 'string', 'max:50'],
            'nama_wali' => ['required', 'string', 'max:100'],
            'no_whatsapp_wali' => ['required', 'string', 'max:25'],
            'hubungan_wali' => ['required', 'in:Ayah,Ibu,Wali'],
            'pin_portal' => ['required', 'string', 'max:10'],
            'diskon_beasiswa' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'bebas_wakaf' => ['nullable', 'boolean'],
        ], [], [
            'nis' => 'NIS',
            'nama_lengkap' => 'nama lengkap',
            'nama_wali' => 'nama wali',
            'no_whatsapp_wali' => 'nomor WhatsApp wali',
            'pin_portal' => 'PIN portal',
        ]);

        $data['bebas_wakaf'] = $request->boolean('bebas_wakaf');
        $data['diskon_beasiswa'] = $data['diskon_beasiswa'] ?? 0;

        $student = Student::create($data);

        ActivityLogService::studentCreated($student, auth()->id());

        return redirect()
            ->route('admin.students')
            ->with('success', 'Data santri '.$student->nama_lengkap.' (NIS '.$student->nis.') berhasil ditambahkan.');
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
}
