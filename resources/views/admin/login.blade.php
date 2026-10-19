@extends('layouts.app')

@section('title', 'Login Pengurus - Keuangan PTQ Amsa001')

@section('content')
<div class="max-w-md mx-auto my-8">
    <div class="bg-white rounded-3xl shadow-xl overflow-hidden border-2 border-brand-900/20">
        <!-- Header Card dengan sentuhan gradient brand dan list emas -->
        <div class="bg-gradient-to-br from-brand-900 via-brand-800 to-brand-900 px-6 py-8 text-center text-white relative border-b-4 border-gold-400">
            <div class="relative inline-block mb-3">
                <img src="{{ asset('images/logo-pesantren.png') }}" alt="Logo PTQ Amsa001" class="w-20 h-20 mx-auto object-contain rounded-2xl bg-white p-1.5 shadow-lg border-2 border-gold-400">
                <span class="absolute -bottom-1 -right-1 bg-brand-900 text-gold-400 text-[10px] font-extrabold px-1.5 py-0.5 rounded-full shadow border border-gold-400">ADM</span>
            </div>

            <h1 class="text-xl font-bold flex items-center justify-center space-x-1.5">
                <span>Panel Pengurus &amp; Bendahara</span>
            </h1>
            <p class="text-xs text-blue-200 mt-1">Kasir, Tagihan, Laporan &amp; Rekap Keuangan Pesantren</p>
        </div>

        <!-- Form Login -->
        <div class="p-6 sm:p-8 space-y-6">
            {{-- Nilai default di bawah hanya untuk mempermudah demo; hapus sebelum produksi. --}}
            <form action="{{ route('admin.login.post') }}" method="POST" class="space-y-5">
                @csrf

                <div>
                    <label for="email" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                        Email Pengurus
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-gold-500">
                            <i class="fa-solid fa-envelope"></i>
                        </span>
                        <input type="email" name="email" id="email" value="{{ old('email', 'admin@ptqamsa.id') }}" required autofocus
                            placeholder="nama@ptqamsa.id"
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-gold-400 focus:border-gold-400 text-sm transition font-medium">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                        Kata Sandi
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-gold-500">
                            <i class="fa-solid fa-lock"></i>
                        </span>
                        <input type="password" name="password" id="password" value="{{ old('password', 'password') }}" required
                            placeholder="••••••••"
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-gold-400 focus:border-gold-400 text-sm transition">
                    </div>
                </div>

                <label class="flex items-center space-x-2 text-xs text-gray-600 cursor-pointer">
                    <input type="checkbox" name="remember" value="1" class="rounded border-gray-300 text-brand-800 focus:ring-gold-400">
                    <span>Ingat saya di perangkat ini</span>
                </label>

                <!-- Tombol Masuk dengan aksen Emas Kuning Elegan -->
                <button type="submit" class="w-full py-3 px-4 bg-gradient-to-r from-gold-400 via-gold-500 to-amber-500 hover:from-gold-500 hover:to-amber-600 text-brand-900 font-extrabold rounded-xl shadow-md transition flex items-center justify-center space-x-2 text-sm border border-gold-300 transform active:scale-[0.99]">
                    <i class="fa-solid fa-right-to-bracket text-base"></i>
                    <span>Masuk ke Panel Admin</span>
                    <i class="fa-solid fa-arrow-right text-xs"></i>
                </button>
            </form>

            <!-- Bantuan / Quick Test Box -->
            <div class="pt-5 border-t border-gray-100">
                <div class="bg-blue-50/70 border border-brand-800/20 rounded-2xl p-4 text-xs text-brand-900 space-y-2.5">
                    <p class="font-bold flex items-center text-brand-900">
                        <span class="w-5 h-5 rounded-full bg-brand-900 text-gold-400 flex items-center justify-center text-[10px] mr-1.5 shadow-sm">
                            <i class="fa-solid fa-key"></i>
                        </span>
                        Akun Pengurus untuk Uji Coba (sandi: <code>password</code>):
                    </p>
                    <div class="space-y-1.5 text-gray-700">
                        <div class="flex items-center justify-between p-1.5 rounded-lg bg-white/80 border border-brand-100">
                            <div>
                                <strong class="text-brand-900">Administrator</strong>
                                <span class="block text-[11px] text-gray-500"><code>admin@ptqamsa.id</code></span>
                            </div>
                            <span class="text-[10px] font-bold text-rose-700 bg-rose-100 px-2 py-0.5 rounded">Akses Penuh</span>
                        </div>
                        <div class="flex items-center justify-between p-1.5 rounded-lg bg-white/80 border border-brand-100">
                            <div>
                                <strong class="text-brand-900">Bendahara 1</strong>
                                <span class="block text-[11px] text-gray-500"><code>bendahara1@ptqamsa.id</code></span>
                            </div>
                            <span class="text-[10px] font-bold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded">Kasir &amp; Laporan</span>
                        </div>
                        <div class="flex items-center justify-between p-1.5 rounded-lg bg-white/80 border border-brand-100">
                            <div>
                                <strong class="text-brand-900">Bendahara 2</strong>
                                <span class="block text-[11px] text-gray-500"><code>bendahara2@ptqamsa.id</code></span>
                            </div>
                            <span class="text-[10px] font-bold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded">Kasir &amp; Laporan</span>
                        </div>
                        <div class="flex items-center justify-between p-1.5 rounded-lg bg-white/80 border border-brand-100">
                            <div>
                                <strong class="text-brand-900">Pengasuh Pesantren</strong>
                                <span class="block text-[11px] text-gray-500">KH. Drs. Aminullah Tsamud &mdash; <code>pengasuh@ptqamsa.id</code></span>
                            </div>
                            <span class="text-[10px] font-bold text-blue-700 bg-blue-100 px-2 py-0.5 rounded">Hanya Melihat</span>
                        </div>
                        <div class="flex items-center justify-between p-1.5 rounded-lg bg-white/80 border border-brand-100">
                            <div>
                                <strong class="text-brand-900">Kepala Muadalah Mu'allimien</strong>
                                <span class="block text-[11px] text-gray-500">KH. Drs. Zubaidi, MM &mdash; <code>muadalah@ptqamsa.id</code></span>
                            </div>
                            <span class="text-[10px] font-bold text-blue-700 bg-blue-100 px-2 py-0.5 rounded">Hanya Melihat</span>
                        </div>
                        <div class="flex items-center justify-between p-1.5 rounded-lg bg-white/80 border border-brand-100">
                            <div>
                                <strong class="text-brand-900">Direktur Yayasan</strong>
                                <span class="block text-[11px] text-gray-500">KH. Drs. Ahmadie Thaha, M.Si. &mdash; <code>yayasan@ptqamsa.id</code></span>
                            </div>
                            <span class="text-[10px] font-bold text-blue-700 bg-blue-100 px-2 py-0.5 rounded">Hanya Melihat</span>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-4 text-xs text-gray-500">
                    <a href="{{ url('/') }}" class="text-brand-900 font-bold hover:text-gold-600 hover:underline inline-flex items-center">
                        <i class="fa-solid fa-arrow-left mr-1"></i> Kembali ke Beranda
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
