@extends('layouts.app')

@section('title', 'Login Portal Wali Santri - PTQ Amsa001')

@section('content')
<div class="max-w-md mx-auto my-8">
    <div class="bg-white rounded-3xl shadow-xl overflow-hidden border-2 border-gold-400/40">
        <!-- Header Card dengan sentuhan gradient brand dan list emas -->
        <div class="bg-gradient-to-br from-brand-900 via-brand-800 to-brand-900 px-6 py-8 text-center text-white relative border-b-4 border-gold-400">
            <!-- Lingkaran Logo dengan list emas berkilau -->
            <div class="relative inline-block mb-3">
                <img src="{{ asset('images/logo-pesantren.png') }}" alt="Logo PTQ Amsa001" class="w-20 h-20 mx-auto object-contain rounded-2xl bg-white p-1.5 shadow-lg border-2 border-gold-400">
                <span class="absolute -bottom-1 -right-1 bg-gold-400 text-brand-900 text-[10px] font-extrabold px-1.5 py-0.5 rounded-full shadow">PTQ</span>
            </div>
            
            <h1 class="text-xl font-bold flex items-center justify-center space-x-1.5">
                <span>Portal Wali Santri</span>
                <i class="fa-solid fa-star text-gold-400 text-xs"></i>
            </h1>
            <p class="text-xs text-blue-200 mt-1">Cek Tagihan, Riwayat Pembayaran & Kuitansi Digital</p>
        </div>

        <!-- Form Login -->
        <div class="p-6 sm:p-8 space-y-6">
            <form action="{{ route('portal.login.post') }}" method="POST" class="space-y-5">
                @csrf

                <div>
                    <label for="nis" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                        Nomor Induk Santri (NIS)
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-gold-500">
                            <i class="fa-solid fa-id-card"></i>
                        </span>
                        <input type="text" name="nis" id="nis" value="{{ old('nis', '202601001') }}" required
                            placeholder="Contoh: 202601001"
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-gold-400 focus:border-gold-400 text-sm transition font-medium">
                    </div>
                </div>

                <div>
                    <label for="identifier" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                        No. WhatsApp Wali / PIN
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-gold-500">
                            <i class="fa-brands fa-whatsapp text-base"></i>
                        </span>
                        <input type="password" name="identifier" id="identifier" value="{{ old('identifier', '085692253516') }}" required
                            placeholder="Nomor WA terdaftar atau PIN 6 digit"
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-gold-400 focus:border-gold-400 text-sm transition">
                    </div>
                    <p class="text-[11px] text-gray-500 mt-1.5">
                        *Masukkan no. WA wali yang terdaftar atau PIN 6 digit (default: <code>123456</code>).
                    </p>
                </div>

                <!-- Tombol Masuk dengan aksen Emas Kuning Elegan -->
                <button type="submit" class="w-full py-3 px-4 bg-gradient-to-r from-gold-400 via-gold-500 to-amber-500 hover:from-gold-500 hover:to-amber-600 text-brand-900 font-extrabold rounded-xl shadow-md transition flex items-center justify-center space-x-2 text-sm border border-gold-300 transform active:scale-[0.99]">
                    <i class="fa-solid fa-right-to-bracket text-base"></i>
                    <span>Masuk ke Portal Santri</span>
                    <i class="fa-solid fa-arrow-right text-xs"></i>
                </button>
            </form>

            <!-- Bantuan / Quick Test Box -->
            <div class="pt-5 border-t border-gray-100">
                <div class="bg-amber-50/70 border border-gold-400/40 rounded-2xl p-4 text-xs text-brand-900 space-y-2.5">
                    <p class="font-bold flex items-center text-amber-900">
                        <span class="w-5 h-5 rounded-full bg-gold-400 text-brand-900 flex items-center justify-center text-[10px] mr-1.5 shadow-sm">
                            <i class="fa-solid fa-key"></i>
                        </span>
                        Akun Santri untuk Uji Coba:
                    </p>
                    <div class="space-y-1.5 text-gray-700">
                        <div class="flex items-center justify-between p-1.5 rounded-lg bg-white/80 border border-gold-200">
                            <div>
                                <strong class="text-brand-900">Muhammad Salman</strong> (Reguler)
                                <span class="block text-[11px] text-gray-500">NIS: <code>202601001</code> | WA: <code>085692253516</code></span>
                            </div>
                            <span class="text-[10px] font-bold text-amber-700 bg-amber-100 px-2 py-0.5 rounded">3 Bln Tagihan</span>
                        </div>
                        <div class="flex items-center justify-between p-1.5 rounded-lg bg-white/80 border border-gold-200">
                            <div>
                                <strong class="text-brand-900">Ahmad Iqbal</strong> (Beasiswa 50%)
                                <span class="block text-[11px] text-gray-500">NIS: <code>202601004</code> | WA: <code>085712345678</code></span>
                            </div>
                            <span class="text-[10px] font-bold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded">Beasiswa Putra</span>
                        </div>
                        <div class="flex items-center justify-between p-1.5 rounded-lg bg-white/80 border border-gold-200">
                            <div>
                                <strong class="text-brand-900">Naysila Aulia</strong> (Beasiswa 50%)
                                <span class="block text-[11px] text-gray-500">NIS: <code>202601005</code> | WA: <code>085898765432</code></span>
                            </div>
                            <span class="text-[10px] font-bold text-purple-700 bg-purple-100 px-2 py-0.5 rounded">Beasiswa Putri</span>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-4 text-xs text-gray-500 space-y-1.5">
                    <p>
                        Lupa PIN?
                        <a href="{{ route('portal.forgot-pin') }}" class="text-brand-900 font-bold hover:text-gold-600 hover:underline">Lihat cara reset PIN</a>
                    </p>
                    <p>
                        Mengalami kendala login?
                        <a href="https://wa.me/6285692253516" target="_blank" class="text-brand-900 font-bold hover:text-gold-600 hover:underline inline-flex items-center">
                            <i class="fa-brands fa-whatsapp text-emerald-600 mr-1"></i> Hubungi Bendahara
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
