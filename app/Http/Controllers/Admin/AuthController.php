<?php

namespace App\Http\Controllers\Admin;

use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController
{
    /**
     * Halaman Login Panel Admin (Bendahara, Pengasuh, Administrator)
     */
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.login');
    }

    /**
     * Proses Login Pengurus Pesantren
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi tidak sesuai.',
            ]);
        }

        // Akun pengurus yang dinonaktifkan tidak boleh masuk.
        if (! $request->user()->is_active) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'Akun ini sudah dinonaktifkan. Hubungi Administrator pesantren.',
            ]);
        }

        $request->session()->regenerate();

        ActivityLogService::loggedIn($request->user());

        return redirect()->intended(route('admin.dashboard'));
    }

    /**
     * Logout dari Panel Admin
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('info', 'Anda telah keluar dari panel admin.');
    }
}
