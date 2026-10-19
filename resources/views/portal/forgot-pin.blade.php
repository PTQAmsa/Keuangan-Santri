@extends('layouts.app')

@section('title', 'Lupa PIN Portal - PTQ Amsa001')

@section('content')
<div class="max-w-md mx-auto my-8">
    <div class="bg-white rounded-3xl shadow-xl overflow-hidden border-2 border-gold-400/40">
        <div class="bg-gradient-to-br from-brand-900 via-brand-800 to-brand-900 px-6 py-7 text-center text-white border-b-4 border-gold-400">
            <div class="w-12 h-12 mx-auto rounded-2xl bg-gold-400 text-brand-900 flex items-center justify-center text-xl mb-2 shadow">
                <i class="fa-solid fa-key"></i>
            </div>
            <h1 class="text-lg font-bold">Lupa PIN Portal</h1>
            <p class="text-xs text-blue-200 mt-1">Minta bantuan reset PIN kepada Bendahara</p>
        </div>

        <div class="p-6 sm:p-8 space-y-5 text-sm">
            <div class="p-4 bg-blue-50 border border-blue-100 rounded-xl text-xs text-blue-900 space-y-1.5">
                <p class="font-bold">Cara masuk tanpa PIN:</p>
                <p>Pada halaman login, isi <strong>NIS</strong> lalu kolom kedua dengan <strong>nomor WhatsApp wali</strong> yang terdaftar di pesantren. Sistem akan meminta Anda membuat PIN baru.</p>
            </div>

            <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-900 space-y-1.5">
                <p class="font-bold">Bila nomor WhatsApp juga tidak bisa dipakai:</p>
                <p>Kirim permintaan di bawah ini, lalu hubungi Bendahara Pesantren. Petugas akan memverifikasi identitas Anda dan memberikan PIN sementara.</p>
            </div>

            <form action="{{ route('portal.forgot-pin.post') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label for="nis" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">NIS Santri</label>
                    <input type="text" name="nis" id="nis" value="{{ old('nis') }}" required
                           placeholder="Contoh: 202601001"
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-gold-400 focus:border-gold-400 text-sm">
                </div>

                <div>
                    <label for="nama_wali" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Nama Wali Santri</label>
                    <input type="text" name="nama_wali" id="nama_wali" value="{{ old('nama_wali') }}" required
                           placeholder="Nama sesuai data pesantren"
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-gold-400 focus:border-gold-400 text-sm">
                </div>

                <button type="submit" class="w-full py-3 px-4 bg-brand-900 hover:bg-brand-800 text-white font-bold rounded-xl shadow-md transition flex items-center justify-center space-x-2 text-sm">
                    <i class="fa-solid fa-paper-plane"></i>
                    <span>Kirim Permintaan Reset PIN</span>
                </button>
            </form>

            <div class="text-center text-xs text-gray-500 pt-2 border-t border-gray-100 space-y-2">
                <a href="https://wa.me/6285692253516?text={{ urlencode('Assalamu\'alaikum, saya wali santri dan lupa PIN Portal. Mohon bantuan reset PIN.') }}" target="_blank"
                   class="inline-flex items-center text-emerald-700 font-bold hover:underline">
                    <i class="fa-brands fa-whatsapp mr-1.5"></i> Hubungi Bendahara via WhatsApp
                </a>
                <div>
                    <a href="{{ route('portal.login') }}" class="text-brand-900 font-bold hover:underline">
                        <i class="fa-solid fa-arrow-left mr-1"></i> Kembali ke Login
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
