<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Services\ActivityLogService;
use App\Services\StudentFinancialCardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class PortalController
{
    /**
     * Batas percobaan login portal per NIS + IP dalam satu menit.
     */
    private const MAX_ATTEMPTS = 5;

    /**
     * Halaman Login Portal Wali Santri
     */
    public function index(Request $request)
    {
        if (session()->has('portal_student_id')) {
            return redirect()->route('portal.dashboard');
        }

        // Isian dari kotak cepat di halaman depan (?q=): NIS diisi ke kolom NIS,
        // nomor WhatsApp (minimal 9 digit) diisi ke kolom identitas.
        $quick = trim((string) $request->input('q', ''));
        $quickIsPhone = $quick !== '' && preg_match('/^\+?\d{9,}$/', $quick) === 1;
        $demoMode = (bool) config('app.demo_mode');

        return view('portal.login', [
            'prefillNis' => $quick !== '' && ! $quickIsPhone ? $quick : ($demoMode ? 'DMY00001' : ''),
            'prefillIdentifier' => $quickIsPhone ? $quick : ($demoMode ? '100001' : ''),
        ]);
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

        $nis = trim((string) $request->nis);

        // Cegah percobaan PIN berulang (brute force) pada satu NIS.
        $throttleKey = 'portal-login:'.$nis.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors([
                'identifier' => "Terlalu banyak percobaan login. Silakan coba lagi dalam {$seconds} detik.",
            ])->withInput();
        }

        $student = Student::where('nis', $nis)->first();

        if (! $student) {
            RateLimiter::hit($throttleKey, 60);

            return back()->withErrors(['nis' => 'Nomor Induk Santri (NIS) tidak ditemukan dalam sistem.'])->withInput();
        }

        if ($student->status !== 'aktif') {
            return back()->withErrors([
                'nis' => 'Santri ini sudah tidak aktif. Silakan hubungi Bendahara Pesantren.',
            ])->withInput();
        }

        // Cek kecocokan nomor WA (dinormalisasi: hanya angka, awalan 62/0 dibuang) atau PIN portal.
        $inputDigits = preg_replace('/[^0-9]/', '', (string) $request->identifier);
        $normalizedInput = preg_replace('/^(62|0)/', '', $inputDigits);
        $normalizedPhone = preg_replace('/^(62|0)/', '', preg_replace('/[^0-9]/', '', (string) $student->no_whatsapp_wali));

        // Nomor WA harus diketik lengkap (minimal 9 digit) agar tidak bisa ditebak dari 1-2 digit terakhir.
        $isPhoneMatch = strlen($normalizedInput) >= 9 && $normalizedInput === $normalizedPhone;
        $isPinMatch = preg_match('/^\d{6}$/', (string) $request->identifier) === 1
            && Hash::check((string) $request->identifier, (string) $student->pin_portal);

        if (! $isPhoneMatch && ! $isPinMatch) {
            RateLimiter::hit($throttleKey, 60);

            return back()->withErrors(['identifier' => 'Nomor WhatsApp atau PIN tidak sesuai dengan data santri.'])->withInput();
        }

        RateLimiter::clear($throttleKey);

        // Login memakai nomor WA (bukan PIN) → wajib ganti PIN karena PIN dianggap lupa.
        if ($isPhoneMatch && ! $isPinMatch) {
            $student->must_change_pin = true;
            $student->save();
        }

        // Simpan session portal
        session([
            'portal_student_id' => $student->id,
            'portal_login_via_wa' => $isPhoneMatch && ! $isPinMatch,
        ]);

        return redirect()->route('portal.dashboard')->with('success', 'Selamat datang di Portal Wali Santri PTQ Amsa001.');
    }

    /**
     * Dashboard Wali Santri
     */
    public function dashboard(StudentFinancialCardService $cardService)
    {
        $studentId = session('portal_student_id');
        if (! $studentId) {
            return redirect()->route('portal.login');
        }

        $student = Student::with(['invoices.category', 'payments.receipt', 'payments.invoice'])->findOrFail($studentId);

        // Tagihan untuk wali dibatasi sampai bulan berjalan: kewajiban bulan-bulan
        // mendatang belum ditampilkan agar tidak terasa membebani. Wakaf selalu
        // tampil karena merupakan komitmen, bukan tagihan bulanan.
        $activeInvoices = $cardService->limitArrearsToCurrentMonth(
            $student->invoices()
                ->with('category')
                ->whereIn('status', ['unpaid', 'partial'])
                ->latest()
                ->get(),
            now()
        );

        $totalTunggakan = (float) $activeInvoices->sum(fn ($invoice) => $invoice->remaining_amount);
        $batasTunggakanLabel = now()->translatedFormat('F Y');
        $batasTunggakanCatatan = 'Dihitung sampai '.$batasTunggakanLabel.'.';

        $paidInvoices = $student->invoices()->where('status', 'paid')->latest()->get();
        $payments = $student->payments()->with(['receipt', 'invoice'])->latest()->get();

        return view('portal.dashboard', compact(
            'student',
            'activeInvoices',
            'paidInvoices',
            'payments',
            'totalTunggakan',
            'batasTunggakanLabel',
            'batasTunggakanCatatan'
        ));
    }

    /**
     * Profil santri (hanya baca) beserta pintu masuk untuk mengganti PIN.
     */
    public function profile()
    {
        $student = $this->currentStudent();

        if (! $student) {
            return redirect()->route('portal.login');
        }

        return view('portal.profile', compact('student'));
    }

    /**
     * Kartu keuangan santri untuk wali yang sedang login.
     */
    public function card(StudentFinancialCardService $cardService)
    {
        $student = $this->currentStudent();

        if (! $student) {
            return redirect()->route('portal.login');
        }

        return view('student-card', $cardService->build($student));
    }

    /**
     * Unduh daftar tagihan santri (versi wali, maksimal satu semester).
     */
    public function cardInvoicesPdf(StudentFinancialCardService $cardService)
    {
        $student = $this->currentStudent();

        if (! $student) {
            return redirect()->route('portal.login');
        }

        return $cardService->invoicesPdf($student);
    }

    /**
     * Unduh riwayat pembayaran & kuitansi santri (versi wali, maksimal satu semester).
     */
    public function cardPaymentsPdf(StudentFinancialCardService $cardService)
    {
        $student = $this->currentStudent();

        if (! $student) {
            return redirect()->route('portal.login');
        }

        return $cardService->paymentsPdf($student);
    }

    /**
     * Halaman Ganti PIN Portal (dari dalam portal).
     */
    public function showChangePin()
    {
        $student = $this->currentStudent();

        if (! $student) {
            return redirect()->route('portal.login');
        }

        return view('portal.change-pin', compact('student'));
    }

    /**
     * Simpan PIN baru.
     */
    public function changePin(Request $request)
    {
        $student = $this->currentStudent();

        if (! $student) {
            return redirect()->route('portal.login');
        }

        $viaWhatsApp = (bool) session('portal_login_via_wa');

        $rules = [
            'pin' => ['required', 'string', 'digits:6', 'confirmed', 'not_in:123456'],
        ];

        if (! $viaWhatsApp) {
            $rules['current_pin'] = ['required', 'string'];
            $rules['pin'][] = 'different:current_pin';
        }

        $data = $request->validate($rules, [
            'pin.digits' => 'PIN baru harus terdiri dari 6 angka.',
            'pin.confirmed' => 'Konfirmasi PIN baru tidak sama.',
            'pin.different' => 'PIN baru harus berbeda dari PIN lama.',
            'pin.not_in' => 'PIN baru tidak boleh menggunakan 123456.',
        ]);

        if (! $viaWhatsApp && ! Hash::check((string) $data['current_pin'], (string) $student->pin_portal)) {
            return back()->withErrors(['current_pin' => 'PIN lama tidak sesuai.']);
        }

        $student->pin_portal = $data['pin'];
        $student->must_change_pin = false;
        $student->pin_changed_at = now();
        $student->pin_reset_requested_at = null;
        $student->save();

        // Setelah PIN baru dibuat, sesi tidak lagi berstatus login-via-WA.
        session()->forget('portal_login_via_wa');

        ActivityLogService::pinChanged($student);

        return redirect()->route('portal.dashboard')
            ->with('success', 'PIN portal berhasil diganti. Gunakan PIN baru pada login berikutnya.');
    }

    /**
     * Halaman bantuan "Lupa PIN".
     */
    public function forgotPin()
    {
        return view('portal.forgot-pin');
    }

    /**
     * Catat permintaan reset PIN dari wali (agar bendahara melihatnya di panel).
     */
    public function requestPinReset(Request $request)
    {
        $data = $request->validate([
            'nis' => ['required', 'string'],
            'nama_wali' => ['required', 'string', 'max:100'],
        ], [], [
            'nis' => 'NIS',
            'nama_wali' => 'nama wali',
        ]);

        $student = Student::where('nis', trim($data['nis']))->first();

        if ($student) {
            $student->pin_reset_requested_at = now();
            $student->save();
        }

        // Pesan dibuat sama baik NIS ditemukan atau tidak (mencegah penebakan NIS).
        return redirect()->route('portal.login')
            ->with('info', 'Permintaan reset PIN sudah dicatat. Bendahara akan menghubungi Anda melalui WhatsApp untuk memberikan PIN baru.');
    }

    /**
     * Logout Portal
     */
    public function logout()
    {
        session()->forget(['portal_student_id', 'portal_login_via_wa']);

        return redirect()->route('portal.login')->with('info', 'Anda telah keluar dari Portal Wali Santri.');
    }

    /**
     * Santri yang sedang login di portal.
     */
    private function currentStudent(): ?Student
    {
        $studentId = session('portal_student_id');

        return $studentId ? Student::find($studentId) : null;
    }
}
