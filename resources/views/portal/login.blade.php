@extends('layouts.app')

@section('title', 'Login Portal Wali Santri - SIKA PTQ')

@section('content')
@php($demoMode = config('app.demo_mode'))
<div class="mx-auto my-6 max-w-md sm:my-10">
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-lg">
        <!-- Kepala kartu -->
        <div class="bg-gradient-to-r from-slate-900 via-blue-950 to-slate-900 px-6 py-8 text-center text-white">
            <img src="{{ asset('images/logo-pesantren.png') }}" alt="Logo PTQ Amsa001" class="mx-auto mb-3 h-16 w-16 rounded-2xl bg-white object-contain p-1.5 shadow-lg ring-2 ring-amber-400">
            <h1 class="text-xl font-bold">Portal Wali Santri</h1>
            <p class="mt-1 text-xs text-blue-200">Cek Tagihan, Riwayat Pembayaran &amp; Kuitansi Digital</p>
        </div>

        <!-- Form Login -->
        <div class="space-y-6 p-6 sm:p-8">
            <form action="{{ route('portal.login.post') }}" method="POST" class="space-y-5">
                @csrf

                <div>
                    <label for="nis" class="mb-1.5 block text-xs font-semibold text-slate-700">Nomor Induk Santri (NIS)</label>
                    <div class="relative">
                        <i class="fa-solid fa-id-card pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                        <input type="text" name="nis" id="nis" value="{{ old('nis', $prefillNis ?? ($demoMode ? 'UJI00001' : '')) }}" required
                            placeholder="Contoh: 2026001"
                            class="h-11 w-full rounded-xl border border-slate-300 bg-slate-50 pl-10 pr-3 text-sm font-medium text-slate-900 placeholder:text-slate-400 focus:border-amber-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500/30">
                    </div>
                </div>

                <div>
                    <label for="identifier" class="mb-1.5 block text-xs font-semibold text-slate-700">No. WhatsApp Wali / PIN</label>
                    <div class="relative">
                        <i class="fa-brands fa-whatsapp pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-base text-slate-400"></i>
                        <input type="password" name="identifier" id="identifier" value="{{ old('identifier', $prefillIdentifier ?? ($demoMode ? '100001' : '')) }}" required
                            placeholder="Nomor WA terdaftar atau PIN 6 digit"
                            class="h-11 w-full rounded-xl border border-slate-300 bg-slate-50 pl-10 pr-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-amber-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500/30">
                    </div>
                    <p class="mt-1.5 text-[11px] text-slate-500">
                        Masukkan no. WA wali yang terdaftar atau PIN 6 digit.
                        @if($demoMode) PIN demo: <code>100001</code> (sesuai data santri).@endif
                    </p>
                </div>

                <button type="submit" class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-amber-500 text-sm font-semibold text-slate-900 shadow-sm transition hover:bg-amber-600">
                    <i class="fa-solid fa-right-to-bracket"></i> Masuk ke Portal Santri
                </button>
            </form>

            <!-- Bantuan / Quick Test Box -->
            <div class="border-t border-slate-100 pt-5">
                @if($demoMode)
                <div class="space-y-2.5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs text-slate-800">
                    <p class="flex items-center gap-2 font-bold text-amber-900">
                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-amber-500 text-[10px] text-slate-900">
                            <i class="fa-solid fa-key"></i>
                        </span>
                        Akun Santri untuk Uji Coba:
                    </p>
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between rounded-lg border border-amber-200 bg-white p-2">
                            <div>
                                <strong class="text-slate-900">Santri Uji Coba Putra</strong> (masuk dengan PIN)
                                <span class="block text-[11px] text-slate-500">NIS: <code>UJI00001</code> | PIN: <code>100001</code></span>
                            </div>
                            <span class="rounded bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-700">Uji Coba Putra</span>
                        </div>
                        <div class="flex items-center justify-between rounded-lg border border-amber-200 bg-white p-2">
                            <div>
                                <strong class="text-slate-900">Santri Uji Coba Putri</strong> (masuk dengan PIN)
                                <span class="block text-[11px] text-slate-500">NIS: <code>UJI00002</code> | PIN: <code>100002</code></span>
                            </div>
                            <span class="rounded bg-purple-100 px-2 py-0.5 text-[10px] font-bold text-purple-700">Uji Coba Putri</span>
                        </div>
                    </div>
                </div>
                @endif

                <div class="mt-4 space-y-1.5 text-center text-xs text-slate-500">
                    <p>
                        Lupa PIN?
                        <a href="{{ route('portal.forgot-pin') }}" class="font-semibold text-blue-700 hover:underline">Lihat cara reset PIN</a>
                    </p>
                    @if($waUrl = \App\Services\TreasurerContactService::primaryUrl('Assalamu\'alaikum, saya wali santri dan mengalami kendala login Portal Wali.'))
                        <p>
                            Mengalami kendala login?
                            <a href="{{ $waUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 font-semibold text-emerald-700 hover:underline">
                                <i class="fa-brands fa-whatsapp"></i> Hubungi Bendahara
                            </a>
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
