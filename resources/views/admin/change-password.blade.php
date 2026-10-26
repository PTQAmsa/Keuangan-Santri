@extends('layouts.app')

@section('title', 'Ganti Kata Sandi - PTQ Amsa001')

@section('content')
<div class="max-w-md mx-auto my-8">
    <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-gray-100">
        <div class="bg-gradient-to-br from-slate-900 via-blue-950 to-slate-900 px-6 py-7 text-center text-white border-b-4 border-amber-400">
            <div class="w-12 h-12 mx-auto rounded-2xl bg-amber-500 text-slate-900 flex items-center justify-center text-xl mb-2 shadow">
                <i class="fa-solid fa-lock"></i>
            </div>
            <h1 class="text-lg font-bold">Ganti Kata Sandi</h1>
            <p class="text-xs text-blue-200 mt-1">{{ auth()->user()->name }} — {{ auth()->user()->displayTitle() }}</p>
        </div>

        <div class="p-6 sm:p-8">
            <form action="{{ route('admin.password.update') }}" method="POST" data-allow-readonly class="space-y-4 text-xs">
                @csrf
                @method('PATCH')

                <div>
                    <label class="block font-bold text-gray-700 uppercase mb-1">Kata Sandi Saat Ini <span class="text-rose-500">*</span></label>
                    <input type="password" name="current_password" required autocomplete="current-password"
                           class="w-full text-xs rounded-xl border-gray-300 py-2.5 px-3 focus:ring-amber-500 focus:border-amber-500">
                </div>

                <div>
                    <label class="block font-bold text-gray-700 uppercase mb-1">Kata Sandi Baru <span class="text-rose-500">*</span></label>
                    <input type="password" name="password" required minlength="8" autocomplete="new-password"
                           class="w-full text-xs rounded-xl border-gray-300 py-2.5 px-3 focus:ring-amber-500 focus:border-amber-500">
                    <p class="text-[11px] text-gray-500 mt-1">Minimal 8 karakter. Gunakan kombinasi huruf dan angka.</p>
                </div>

                <div>
                    <label class="block font-bold text-gray-700 uppercase mb-1">Ulangi Kata Sandi Baru <span class="text-rose-500">*</span></label>
                    <input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password"
                           class="w-full text-xs rounded-xl border-gray-300 py-2.5 px-3 focus:ring-amber-500 focus:border-amber-500">
                </div>

                <button type="submit" class="w-full py-3 px-4 bg-amber-500 hover:bg-amber-600 text-slate-900 font-bold rounded-xl shadow-md transition flex items-center justify-center space-x-2 text-sm">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>Simpan Kata Sandi Baru</span>
                </button>
            </form>

            <div class="text-center text-xs text-gray-500 pt-5 border-t border-gray-100 mt-5">
                <a href="{{ route('admin.dashboard') }}" class="text-slate-900 font-bold hover:underline">
                    <i class="fa-solid fa-arrow-left mr-1"></i> Kembali ke Dashboard
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
