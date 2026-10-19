<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;

class PortalController
{
    /**
     * Halaman Login Portal Wali Santri
     */
    public function index()
    {
        if (session()->has('portal_student_id')) {
            return redirect()->route('portal.dashboard');
        }

        return view('portal.login');
    }

    /**
     * Proses Login Wali Santri
     */
    public function login(Request $request)
    {
        $request->validate([
            'nis' => 'required|string',
            'identifier' => 'required|string', // bisa no wa atau PIN
        ]);

        $student = Student::where('nis', trim($request->nis))->first();

        if (!$student) {
            return back()->withErrors(['nis' => 'Nomor Induk Santri (NIS) tidak ditemukan dalam sistem.'])->withInput();
        }

        // Cek kecocokan nomor WA (dibersihkan dari karakter spasi/tanda hubung) atau PIN portal
        $identifierInput = preg_replace('/[^0-9]/', '', $request->identifier);
        $studentPhone = preg_replace('/[^0-9]/', '', $student->no_whatsapp_wali);

        $isPhoneMatch = !empty($identifierInput) && (str_ends_with($studentPhone, $identifierInput) || str_ends_with($identifierInput, $studentPhone));
        $isPinMatch = $request->identifier === $student->pin_portal;

        if (!$isPhoneMatch && !$isPinMatch) {
            return back()->withErrors(['identifier' => 'Nomor WhatsApp atau PIN tidak sesuai dengan data santri.'])->withInput();
        }

        // Simpan session portal
        session(['portal_student_id' => $student->id]);

        return redirect()->route('portal.dashboard')->with('success', 'Selamat datang di Portal Wali Santri PTQ Amsa001.');
    }

    /**
     * Dashboard Wali Santri
     */
    public function dashboard()
    {
        $studentId = session('portal_student_id');
        if (!$studentId) {
            return redirect()->route('portal.login');
        }

        $student = Student::with(['invoices.category', 'payments.receipt', 'payments.invoice'])->findOrFail($studentId);

        // Untuk portal wali santri: tagihan non-SPP (seperti wakaf/fasilitas) selalu tampil jika belum lunas,
        // sedangkan tagihan rutin SPP bulanan dibatasi hanya sampai 1 bulan ke depan (bulan depan)
        $currentMonth = now()->month;
        $currentYear = now()->year;
        $nextMonthDate = now()->copy()->addMonth();

        $activeInvoices = $student->invoices()
            ->whereIn('status', ['unpaid', 'partial'])
            ->where(function ($q) use ($nextMonthDate) {
                // Tagihan umum (non-periodik) ATAU tagihan bulanan sampai maksimal 1 bulan ke depan
                $q->whereNull('period_month')
                  ->orWhere(function ($sub) use ($nextMonthDate) {
                      $sub->where('period_year', '<', $nextMonthDate->year)
                          ->orWhere(function ($sub2) use ($nextMonthDate) {
                              $sub2->where('period_year', '=', $nextMonthDate->year)
                                   ->where('period_month', '<=', $nextMonthDate->month);
                          });
                  });
            })
            ->latest()
            ->get();

        $paidInvoices = $student->invoices()->where('status', 'paid')->latest()->get();
        $payments = $student->payments()->with(['receipt', 'invoice'])->latest()->get();

        return view('portal.dashboard', compact('student', 'activeInvoices', 'paidInvoices', 'payments'));
    }

    /**
     * Logout Portal
     */
    public function logout()
    {
        session()->forget('portal_student_id');
        return redirect()->route('portal.login')->with('info', 'Anda telah keluar dari Portal Wali Santri.');
    }
}
