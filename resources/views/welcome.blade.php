@extends('layouts.app')

@section('title', 'Sistem Keuangan & Tagihan - PTQ Amsa001')

@section('content')
<div class="space-y-12 my-4">

    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-brand-900 via-brand-800 to-blue-900 rounded-3xl p-8 sm:p-12 text-white shadow-2xl relative overflow-hidden">
        <div class="relative z-10 max-w-3xl space-y-5">
            <div class="flex items-center space-x-3">
                <img src="{{ asset('images/logo-pesantren.png') }}" alt="Logo PTQ Amsa001" class="w-14 h-14 object-contain rounded-2xl bg-white p-1 shadow-md">
                <div class="inline-flex items-center px-3.5 py-1.5 rounded-full bg-gold-400/20 border border-gold-400/30 text-gold-400 text-xs font-semibold">
                    PTQ Amsa001 Gadog - Megamendung, Bogor
                </div>
            </div>
            <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight leading-tight">
                Sistem Keuangan & Tagihan Santri Terpadu
            </h1>
            <p class="text-sm sm:text-base text-blue-200 leading-relaxed max-w-2xl">
                Layanan pencatatan pembayaran SPP bulanan, sarpras asrama, wakaf pembangunan bertahap, serta penerbitan kuitansi digital ber-QR Code untuk wali santri.
            </p>

            <div class="pt-4 flex flex-wrap gap-3">
                <a href="{{ route('portal.login') }}" class="px-6 py-3.5 bg-gold-400 hover:bg-gold-500 text-brand-900 font-bold rounded-2xl shadow-lg transition flex items-center space-x-2 text-sm">
                    <i class="fa-solid fa-user-shield text-base"></i>
                    <span>Masuk Portal Wali Santri</span>
                </a>
            </div>
        </div>

        <div class="absolute right-0 bottom-0 opacity-10 pointer-events-none transform translate-x-12 translate-y-12">
            <i class="fa-solid fa-coins text-[260px]"></i>
        </div>
    </div>

    <!-- Rincian Parameter Biaya Resmi PTQ Amsa001 (From FAQ) -->
    <div class="space-y-6">
        <div class="text-center max-w-2xl mx-auto space-y-1">
            <h2 class="text-2xl font-bold text-gray-900">Skema Pembiayaan Resmi PTQ Amsa001</h2>
            <p class="text-xs sm:text-sm text-gray-500">Berdasarkan ketetapan resmi Pesantren Tadabbur Al-Qur'an Amsa001 Gadog</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <!-- Card 1: Pembiayaan Awal Santri Baru -->
            <div class="bg-white rounded-2xl p-6 sm:p-7 border border-gray-100 shadow-sm space-y-4">
                <div class="flex items-center space-x-3 pb-3 border-b border-gray-100">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-brand-900 flex items-center justify-center text-lg font-bold">
                        <i class="fa-solid fa-door-open"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 text-base">Pembiayaan Awal (Santri Baru)</h3>
                        <p class="text-xs text-gray-400">Dibayarkan saat pendaftaran & masuk asrama</p>
                    </div>
                </div>

                <div class="space-y-3 text-xs sm:text-sm">
                    <div class="flex justify-between items-center py-2 border-b border-gray-50">
                        <span class="text-gray-600">1. Biaya Formulir Pendaftaran</span>
                        <span class="font-bold text-gray-900">Rp 200.000,-</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-50">
                        <span class="text-gray-600">2. Fasilitas Kamar (Lemari, Kasur, Bantal, Selimut & Sprei)</span>
                        <span class="font-bold text-gray-900">Rp 1.000.000,-</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-50">
                        <div>
                            <span class="text-gray-600">3. Wakaf Pembangunan</span>
                            <span class="block text-[11px] text-amber-600 font-medium">(Bisa dicicil 2x @Rp 2.500.000)</span>
                        </div>
                        <span class="font-bold text-gray-900">Rp 5.000.000,-</span>
                    </div>
                </div>
            </div>

            <!-- Card 2: Iuran Rutin Bulanan -->
            <div class="bg-white rounded-2xl p-6 sm:p-7 border border-gray-100 shadow-sm space-y-4">
                <div class="flex items-center space-x-3 pb-3 border-b border-gray-100">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-lg font-bold">
                        <i class="fa-solid fa-calendar-check"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 text-base">Iuran Rutin Bulanan</h3>
                        <p class="text-xs text-gray-400">Jatuh tempo setiap tanggal 10 tiap bulan</p>
                    </div>
                </div>

                <div class="space-y-3 text-xs sm:text-sm">
                    <div class="flex justify-between items-center py-2 border-b border-gray-50">
                        <span class="text-gray-600">1. SPP Pendidikan</span>
                        <span class="font-bold text-gray-900">Rp 600.000,-</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-50">
                        <span class="text-gray-600">2. Dapur / Konsumsi (Makan 3x Sehari)</span>
                        <span class="font-bold text-gray-900">Rp 550.000,-</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-50">
                        <span class="text-gray-600">3. Fasilitas Laundry Pakaian</span>
                        <span class="font-bold text-gray-900">Rp 200.000,-</span>
                    </div>
                    <div class="flex justify-between items-center py-2.5 px-3 bg-blue-50 rounded-xl font-bold text-brand-900">
                        <span>Total Paket Iuran Bulanan</span>
                        <span class="text-base text-brand-900">Rp 1.350.000,- / bln</span>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Fitur Unggulan Sistem Keuangan -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 pt-4">
        <div class="bg-white rounded-2xl p-6 border border-gray-100 text-center space-y-3">
            <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 mx-auto flex items-center justify-center text-xl">
                <i class="fa-solid fa-handshake-angle"></i>
            </div>
            <h3 class="font-bold text-gray-900 text-base">Skema Cicilan & Beasiswa</h3>
            <p class="text-xs text-gray-500 leading-relaxed">
                Mendukung pembayaran wakaf dicicil 2 tahap serta pemotongan otomatis beasiswa santri yatim/dhuafa (potongan beasiswa 50% & bebas wakaf).
            </p>
        </div>

        <div class="bg-white rounded-2xl p-6 border border-gray-100 text-center space-y-3">
            <div class="w-12 h-12 rounded-2xl bg-brand-50 text-brand-800 mx-auto flex items-center justify-center text-xl">
                <i class="fa-solid fa-qrcode"></i>
            </div>
            <h3 class="font-bold text-gray-900 text-base">Kuitansi Digital QR Code</h3>
            <p class="text-xs text-gray-500 leading-relaxed">
                Tanda terima resmi berformat PDF ber-Kop PTQ Amsa001 yang dilengkapi kode verifikasi publik untuk menjamin keaslian bukti pembayaran.
            </p>
        </div>

        <div class="bg-white rounded-2xl p-6 border border-gray-100 text-center space-y-3">
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 mx-auto flex items-center justify-center text-xl">
                <i class="fa-brands fa-whatsapp"></i>
            </div>
            <h3 class="font-bold text-gray-900 text-base">Notifikasi WhatsApp Gateway</h3>
            <p class="text-xs text-gray-500 leading-relaxed">
                Pengingat jatuh tempo tagihan dan konfirmasi kuitansi otomatis langsung terkirim ke WhatsApp wali santri tanpa repot manual.
            </p>
        </div>
    </div>

</div>
@endsection
