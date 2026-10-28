@extends('layouts.app')

@section('title', 'Ganti PIN Portal - SIKA PTQ')

@section('content')
@php($viaWhatsApp = (bool) session('portal_login_via_wa'))
<div class="mx-auto my-6 max-w-md sm:my-10">
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-lg">
        <div class="bg-gradient-to-r from-slate-900 via-blue-950 to-slate-900 px-6 py-7 text-center text-white">
            <span class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-amber-500 text-slate-900 shadow">
                <i class="fa-solid fa-lock text-lg"></i>
            </span>
            <h1 class="text-lg font-bold">Ganti PIN Portal</h1>
            <p class="mt-1 text-xs text-blue-200">Amankan akun portal wali santri Anda</p>
        </div>

        <div class="space-y-5 p-6 sm:p-8">
            @if($student->must_change_pin)
                <div class="flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900">
                    <i class="fa-solid fa-triangle-exclamation mt-0.5 text-amber-600"></i>
                    <p>Demi keamanan data keuangan, <strong>PIN wajib diganti</strong> sebelum melanjutkan ke portal.</p>
                </div>
            @endif

            @if($viaWhatsApp)
                <div class="rounded-xl border border-emerald-100 bg-emerald-50 p-3 text-xs text-emerald-900">
                    <p><i class="fa-brands fa-whatsapp mr-1 text-emerald-600"></i> Anda login memakai nomor WhatsApp wali, jadi tidak perlu memasukkan PIN lama.</p>
                </div>
            @endif

            <div class="rounded-xl border border-blue-100 bg-blue-50 p-3 text-xs text-blue-900">
                <p class="font-semibold">{{ $student->nama_lengkap }} (NIS {{ $student->nis }})</p>
                <p class="mt-0.5 text-blue-700">PIN dipakai untuk login ke Portal Wali Santri. Gunakan 6 angka yang tidak mudah ditebak (hindari 123456 atau tanggal lahir).</p>
            </div>

            <form action="{{ route('portal.change-pin.post') }}" method="POST" class="space-y-4">
                @csrf

                @unless($viaWhatsApp)
                    <div>
                        <label for="current_pin" class="mb-1.5 block text-xs font-semibold text-slate-700">PIN Lama</label>
                        <div class="relative">
                            <i class="fa-solid fa-key pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                            <input type="password" name="current_pin" id="current_pin" required inputmode="numeric"
                                   class="h-11 w-full rounded-xl border border-slate-300 bg-slate-50 pl-10 pr-3 text-sm text-slate-900 focus:border-amber-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500/30">
                        </div>
                    </div>
                @endunless

                <div>
                    <label for="pin" class="mb-1.5 block text-xs font-semibold text-slate-700">PIN Baru (6 angka)</label>
                    <div class="relative">
                        <i class="fa-solid fa-lock pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                        <input type="password" name="pin" id="pin" required inputmode="numeric" maxlength="6"
                               class="h-11 w-full rounded-xl border border-slate-300 bg-slate-50 pl-10 pr-3 text-sm text-slate-900 focus:border-amber-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500/30">
                    </div>
                </div>

                <div>
                    <label for="pin_confirmation" class="mb-1.5 block text-xs font-semibold text-slate-700">Ulangi PIN Baru</label>
                    <div class="relative">
                        <i class="fa-solid fa-lock pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                        <input type="password" name="pin_confirmation" id="pin_confirmation" required inputmode="numeric" maxlength="6"
                               class="h-11 w-full rounded-xl border border-slate-300 bg-slate-50 pl-10 pr-3 text-sm text-slate-900 focus:border-amber-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500/30">
                    </div>
                </div>

                <button type="submit" class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-amber-500 text-sm font-semibold text-slate-900 shadow-sm transition hover:bg-amber-600">
                    <i class="fa-solid fa-floppy-disk"></i> Simpan PIN Baru
                </button>
            </form>

            @unless($student->must_change_pin)
                <div class="border-t border-slate-100 pt-4 text-center text-xs text-slate-500">
                    <a href="{{ route('portal.dashboard') }}" class="inline-flex items-center gap-1.5 font-semibold text-slate-700 hover:text-slate-900 hover:underline">
                        <i class="fa-solid fa-arrow-left"></i> Kembali ke Dashboard
                    </a>
                </div>
            @endunless
        </div>
    </div>
</div>
@endsection
