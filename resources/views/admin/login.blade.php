@extends('layouts.app')

@section('title', 'Login Pengurus - SIKA PTQ')

@section('content')
@php($demoMode = config('app.demo_mode'))
<div class="mx-auto my-6 max-w-md sm:my-10">
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-lg">
        <!-- Kepala kartu -->
        <div class="bg-gradient-to-r from-slate-900 via-blue-950 to-slate-900 px-6 py-8 text-center text-white">
            <img src="{{ asset('images/logo-pesantren.png') }}" alt="Logo PTQ Amsa001" class="mx-auto mb-3 h-16 w-16 rounded-2xl bg-white object-contain p-1.5 shadow-lg ring-2 ring-amber-400">
            <h1 class="text-xl font-bold">Panel Pengurus &amp; Bendahara</h1>
            <p class="mt-1 text-xs text-blue-200">Kasir, Tagihan, Laporan &amp; Rekap Keuangan Pesantren</p>
        </div>

        <!-- Form Login -->
        <div class="space-y-6 p-6 sm:p-8">
            {{-- Isi otomatis hanya aktif bila APP_DEMO_MODE=true. --}}
            <form action="{{ route('admin.login.post') }}" method="POST" class="space-y-5">
                @csrf

                <div>
                    <label for="email" class="mb-1.5 block text-xs font-semibold text-slate-700">Email</label>
                    <div class="relative">
                        <i class="fa-solid fa-envelope pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                        <input type="email" name="email" id="email" value="{{ old('email', $demoMode ? 'admin@ptqamsa.id' : '') }}" required autofocus
                            placeholder="nama@ptqamsa.id"
                            class="h-11 w-full rounded-xl border border-slate-300 bg-slate-50 pl-10 pr-3 text-sm font-medium text-slate-900 placeholder:text-slate-400 focus:border-amber-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500/30">
                    </div>
                </div>

                <div>
                    <label for="password" class="mb-1.5 block text-xs font-semibold text-slate-700">Kata Sandi</label>
                    <div class="relative">
                        <i class="fa-solid fa-lock pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                        <input type="password" name="password" id="password" value="{{ old('password', $demoMode ? \Database\Seeders\DatabaseSeeder::DEFAULT_PASSWORDS['admin'] : '') }}" required
                            placeholder="••••••••"
                            class="h-11 w-full rounded-xl border border-slate-300 bg-slate-50 pl-10 pr-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-amber-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500/30">
                    </div>
                </div>

                <label class="flex cursor-pointer items-center gap-2 text-xs text-slate-600">
                    <input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-amber-500">
                    <span>Ingat saya di perangkat ini</span>
                </label>

                <button type="submit" class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-amber-500 text-sm font-semibold text-slate-900 shadow-sm transition hover:bg-amber-600">
                    <i class="fa-solid fa-right-to-bracket"></i> Masuk ke Panel Admin
                </button>
            </form>

            <!-- Bantuan / Quick Test Box -->
            <div class="border-t border-slate-100 pt-5">
                @if($demoMode)
                <div class="space-y-2.5 rounded-xl border border-blue-100 bg-blue-50 p-4 text-xs text-slate-800">
                    <p class="flex items-center gap-2 font-bold text-blue-900">
                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-blue-600 text-[10px] text-white">
                            <i class="fa-solid fa-key"></i>
                        </span>
                        Akun Pengurus untuk Uji Coba:
                    </p>
                    @php($sandi = \Database\Seeders\DatabaseSeeder::DEFAULT_PASSWORDS)
                    <div class="space-y-1.5">
                        @foreach([
                            ['Administrator', null, 'admin@ptqamsa.id', $sandi['admin'], 'Akses Penuh', 'bg-rose-100 text-rose-700'],
                            ['Bendahara 1', 'Usth. Yuni Permatasari', 'bendahara1@ptqamsa.id', $sandi['bendahara'], 'Kasir & Laporan', 'bg-emerald-100 text-emerald-700'],
                            ['Bendahara 2', 'Usth. Revina', 'bendahara2@ptqamsa.id', $sandi['bendahara'], 'Kasir & Laporan', 'bg-emerald-100 text-emerald-700'],
                            ['Pengasuh Pesantren', 'KH. Drs. Aminullah Tsamud', 'pengasuh@ptqamsa.id', $sandi['pengasuh'], 'Hanya Melihat', 'bg-blue-100 text-blue-700'],
                            ["Kepala Muadalah Mu'allimien", 'KH. Drs. Zubaidi, MM', 'muadalah@ptqamsa.id', $sandi['pengasuh'], 'Hanya Melihat', 'bg-blue-100 text-blue-700'],
                            ['Direktur Yayasan', 'KH. Drs. Ahmadie Thaha, M.Si.', 'yayasan@ptqamsa.id', $sandi['pengasuh'], 'Hanya Melihat', 'bg-blue-100 text-blue-700'],
                        ] as [$role, $person, $email, $password, $badge, $badgeClass])
                            <div class="flex items-center justify-between rounded-lg border border-blue-100 bg-white p-2">
                                <div>
                                    <strong class="text-slate-900">{{ $role }}</strong>
                                    <span class="block text-[11px] text-slate-500">@if($person){{ $person }} &mdash; @endif<code>{{ $email }}</code> &middot; sandi <code>{{ $password }}</code></span>
                                </div>
                                <span class="rounded px-2 py-0.5 text-[10px] font-bold {{ $badgeClass }}">{{ $badge }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <div class="mt-4 text-center text-xs text-slate-500">
                    <a href="{{ url('/') }}" class="inline-flex items-center gap-1.5 font-semibold text-slate-700 hover:text-slate-900 hover:underline">
                        <i class="fa-solid fa-arrow-left"></i> Kembali ke Beranda
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
