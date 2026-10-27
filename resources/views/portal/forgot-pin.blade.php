@extends('layouts.app')

@section('title', 'Lupa PIN Portal - PTQ Amsa001')

@section('content')
<div class="mx-auto my-6 max-w-md sm:my-10">
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-lg">
        <!-- Kepala kartu -->
        <div class="bg-gradient-to-r from-slate-900 via-blue-950 to-slate-900 px-6 py-7 text-center text-white">
            <span class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-amber-500 text-slate-900 shadow">
                <i class="fa-solid fa-key text-lg"></i>
            </span>
            <h1 class="text-lg font-bold">Lupa PIN Portal</h1>
            <p class="mt-1 text-xs text-blue-200">Minta bantuan reset PIN kepada Bendahara</p>
        </div>

        <div class="space-y-5 p-6 text-sm sm:p-8">
            <div class="rounded-xl border border-blue-100 bg-blue-50 p-4 text-xs text-blue-900">
                <p class="mb-1 flex items-center gap-2 font-bold"><i class="fa-brands fa-whatsapp text-emerald-600"></i> Cara masuk tanpa PIN</p>
                <p class="leading-relaxed">Pada halaman login, isi <strong>NIS</strong> lalu kolom kedua dengan <strong>nomor WhatsApp wali</strong> yang terdaftar di pesantren. Sistem akan meminta Anda membuat PIN baru.</p>
            </div>

            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-900">
                <p class="mb-1 flex items-center gap-2 font-bold"><i class="fa-solid fa-circle-info text-amber-600"></i> Bila nomor WhatsApp juga tidak bisa dipakai</p>
                <p class="leading-relaxed">Kirim permintaan di bawah ini, lalu hubungi Bendahara Pesantren. Petugas akan memverifikasi identitas Anda dan memberikan PIN sementara.</p>
            </div>

            <form action="{{ route('portal.forgot-pin.post') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label for="nis" class="mb-1.5 block text-xs font-semibold text-slate-700">NIS Santri</label>
                    <div class="relative">
                        <i class="fa-solid fa-id-card pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                        <input type="text" name="nis" id="nis" value="{{ old('nis') }}" required
                               placeholder="Contoh: 2026001"
                               class="h-11 w-full rounded-xl border border-slate-300 bg-slate-50 pl-10 pr-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-amber-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500/30">
                    </div>
                </div>

                <div>
                    <label for="nama_wali" class="mb-1.5 block text-xs font-semibold text-slate-700">Nama Wali Santri</label>
                    <div class="relative">
                        <i class="fa-solid fa-user pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                        <input type="text" name="nama_wali" id="nama_wali" value="{{ old('nama_wali') }}" required
                               placeholder="Nama sesuai data pesantren"
                               class="h-11 w-full rounded-xl border border-slate-300 bg-slate-50 pl-10 pr-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-amber-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500/30">
                    </div>
                </div>

                <button type="submit" class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-amber-500 text-sm font-semibold text-slate-900 shadow-sm transition hover:bg-amber-600">
                    <i class="fa-solid fa-paper-plane"></i> Kirim Permintaan Reset PIN
                </button>
            </form>

            <div class="space-y-2 border-t border-slate-100 pt-4 text-center text-xs text-slate-500">
                <a href="https://wa.me/6285692253516?text={{ urlencode('Assalamu\'alaikum, saya wali santri dan lupa PIN Portal. Mohon bantuan reset PIN.') }}" target="_blank" rel="noopener"
                   class="inline-flex items-center gap-1.5 font-semibold text-emerald-700 hover:underline">
                    <i class="fa-brands fa-whatsapp"></i> Hubungi Bendahara via WhatsApp
                </a>
                <div>
                    <a href="{{ url('/') }}" class="inline-flex items-center gap-1.5 font-semibold text-slate-700 hover:text-slate-900 hover:underline">
                        <i class="fa-solid fa-arrow-left"></i> Kembali ke Halaman Depan
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
