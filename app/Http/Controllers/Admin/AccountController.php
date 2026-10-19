<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

/**
 * Pengelolaan akun pengurus pesantren:
 * - Administrator dapat menambah pengurus, mereset sandi, dan menonaktifkan akun.
 * - Semua pengurus dapat mengganti sandinya sendiri.
 */
class AccountController
{
    /**
     * Daftar akun pengurus (khusus Administrator).
     */
    public function index()
    {
        $users = User::orderByRaw("CASE role WHEN 'admin' THEN 0 WHEN 'bendahara' THEN 1 ELSE 2 END")
            ->orderBy('name')
            ->get();

        $roles = UserRole::cases();

        return view('admin.accounts', compact('users', 'roles'));
    }

    /**
     * Tambah akun pengurus baru (khusus Administrator).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'jabatan' => ['nullable', 'string', 'max:100'],
            'role' => ['required', 'in:admin,bendahara,pengasuh'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [], [
            'name' => 'nama pengurus',
            'jabatan' => 'jabatan',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'jabatan' => $data['jabatan'] ?? null,
            'role' => UserRole::from($data['role']),
            'password' => $data['password'],
            'is_active' => true,
        ]);

        ActivityLogService::userCreated($user, auth()->id());

        return back()->with('success', 'Akun pengurus '.$user->name.' berhasil ditambahkan.');
    }

    /**
     * Reset sandi akun pengurus (khusus Administrator).
     */
    public function resetPassword(Request $request, User $user)
    {
        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [], [
            'password' => 'kata sandi baru',
        ]);

        $user->password = $data['password'];
        $user->save();

        ActivityLogService::userPasswordReset($user, auth()->id());

        return back()->with('success', 'Kata sandi '.$user->name.' berhasil direset. Sampaikan sandi baru secara pribadi kepada yang bersangkutan.');
    }

    /**
     * Aktifkan / nonaktifkan akun pengurus (khusus Administrator).
     */
    public function toggleStatus(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['account' => 'Anda tidak dapat menonaktifkan akun sendiri.']);
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        ActivityLogService::userStatusChanged($user, auth()->id());

        return back()->with('success', $user->is_active
            ? 'Akun '.$user->name.' diaktifkan kembali.'
            : 'Akun '.$user->name.' dinonaktifkan. Pengurus ini tidak dapat login sampai diaktifkan kembali.');
    }

    /**
     * Halaman ganti sandi untuk pengurus yang sedang login.
     */
    public function showChangePassword()
    {
        return view('admin.change-password');
    }

    /**
     * Simpan kata sandi baru milik sendiri.
     */
    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'current_password.current_password' => 'Kata sandi saat ini tidak sesuai.',
        ], [
            'current_password' => 'kata sandi saat ini',
            'password' => 'kata sandi baru',
        ]);

        $user = $request->user();
        $user->password = $data['password'];
        $user->save();

        ActivityLogService::passwordChanged($user);

        return back()->with('success', 'Kata sandi Anda berhasil diganti. Gunakan sandi baru pada login berikutnya.');
    }
}
