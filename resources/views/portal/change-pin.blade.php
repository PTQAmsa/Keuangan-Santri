@extends('layouts.app')

@section('title', 'Ganti PIN Portal - PTQ Amsa001')

@section('content')
<div class="max-w-md mx-auto my-8">
    <div class="bg-white rounded-3xl shadow-xl overflow-hidden border-2 border-gold-400/40">
        <div class="bg-gradient-to-br from-brand-900 via-brand-800 to-brand-900 px-6 py-7 text-center text-white border-b-4 border-gold-400">
            <div class="w-12 h-12 mx-auto rounded-2xl bg-gold-400 text-brand-900 flex items-center justify-center text-xl mb-2 shadow">
                <i class="fa-solid fa-lock"></i>
            </div>
            <h1 class="text-lg font-bold">Ganti PIN Portal</h1>
            <p class="text-xs text-blue-200 mt-1">Amankan akun portal wali santri Anda</p>
        </div>

        <div class="p-6 sm:p-8 space-y-5">
            @if($student->must_change_pin)
                <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-[11px] text-amber-900 flex items-start space-x-2">
                    <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
                    <p>Anda login memakai nomor WhatsApp. Untuk keamanan, <strong>wajib ganti PIN</strong> sebelum melanjutkan.</p>
                </div>
            @endif

            <div class="p-3 bg-blue-50 border border-blue-100 rounded-xl text-[11px] text-blue-900">
                <p class="font-semibold">{{ $student->nama_lengkap }} (NIS {{ $student->nis }})</p>
                <p class="text-blue-700 mt-0.5">PIN dipakai untuk login ke Portal Wali Santri. Gunakan 6 angka yang tidak mudah ditebak (hindari 123456 atau tanggal lahir).</p>
            </div>

            <form action="{{ route('portal.change-pin.post') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label for="current_pin" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">PIN Lama</label>
                    <input type="password" name="current_pin" id="current_pin" required inputmode="numeric"
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-gold-400 focus:border-gold-400 text-sm">
                </div>

                <div>
                    <label for="pin" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">PIN Baru (6 angka)</label>
                    <input type="password" name="pin" id="pin" required inputmode="numeric" maxlength="6"
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-gold-400 focus:border-gold-400 text-sm">
                </div>

                <div>
                    <label for="pin_confirmation" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Ulangi PIN Baru</label>
                    <input type="password" name="pin_confirmation" id="pin_confirmation" required inputmode="numeric" maxlength="6"
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-gold-400 focus:border-gold-400 text-sm">
                </div>

                <button type="submit" class="w-full py-3 px-4 bg-gradient-to-r from-gold-400 via-gold-500 to-amber-500 hover:from-gold-500 hover:to-amber-600 text-brand-900 font-extrabold rounded-xl shadow-md transition flex items-center justify-center space-x-2 text-sm border border-gold-300">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>Simpan PIN Baru</span>
                </button>
            </form>

            <div class="text-center text-xs text-gray-500 pt-2 border-t border-gray-100">
                <a href="{{ route('portal.dashboard') }}" class="text-brand-900 font-bold hover:underline">
                    <i class="fa-solid fa-arrow-left mr-1"></i> Kembali ke Dashboard
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
