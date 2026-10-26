@extends('layouts.app')

@section('title', 'Admin Bendahara - Keuangan PTQ Amsa001')

@section('content')
<div class="space-y-6">

    <!-- Header & Action Buttons -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-gray-200">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Dashboard Kasir & Bendahara</h1>
            <p class="text-xs sm:text-sm text-gray-500 mt-0.5">Pengelolaan Keuangan & Tagihan Santri PTQ Amsa001 Gadog</p>
        </div>
    </div>

    <!-- Ringkasan Statistik -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <!-- Total Santri Aktif -->
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Santri Aktif Terdaftar</p>
                    <p class="text-3xl font-extrabold text-brand-900 mt-1">{{ $totalSantri }}</p>
                    <p class="text-xs text-gray-400 mt-1">Aktif per {{ now()->translatedFormat('F Y') }}</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-brand-800 flex items-center justify-center text-2xl">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-2 mt-4 text-center">
                <div class="rounded-xl bg-blue-50/70 border border-blue-100 py-2">
                    <p class="text-[10px] font-bold uppercase text-blue-700">Putra</p>
                    <p class="text-lg font-extrabold text-blue-800">{{ $totalPutra }}</p>
                </div>
                <div class="rounded-xl bg-fuchsia-50/70 border border-fuchsia-100 py-2">
                    <p class="text-[10px] font-bold uppercase text-fuchsia-700">Putri</p>
                    <p class="text-lg font-extrabold text-fuchsia-800">{{ $totalPutri }}</p>
                </div>
            </div>
        </div>

        <!-- Uang Masuk Bulan Ini -->
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Pemasukan Bulan Ini</p>
                    <p class="text-2xl font-extrabold text-emerald-600 mt-1">
                        Rp {{ number_format($totalMasukBulanIni, 0, ',', '.') }}
                    </p>
                    <p class="text-xs text-gray-400 mt-1">{{ now()->translatedFormat('F Y') }} &bull; keseluruhan</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl">
                    <i class="fa-solid fa-hand-holding-dollar"></i>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-2 mt-4">
                <div class="rounded-xl bg-blue-50/70 border border-blue-100 px-3 py-2">
                    <p class="text-[10px] font-bold uppercase text-blue-700">Putra</p>
                    <p class="text-sm font-extrabold text-blue-800">Rp {{ number_format($masukPutra, 0, ',', '.') }}</p>
                </div>
                <div class="rounded-xl bg-fuchsia-50/70 border border-fuchsia-100 px-3 py-2">
                    <p class="text-[10px] font-bold uppercase text-fuchsia-700">Putri</p>
                    <p class="text-sm font-extrabold text-fuchsia-800">Rp {{ number_format($masukPutri, 0, ',', '.') }}</p>
                </div>
            </div>
            <a href="{{ route('admin.reports.cash') }}" class="text-xs text-emerald-700 font-semibold hover:underline mt-3 inline-flex items-center">
                Lihat Laporan Kas &rarr;
            </a>
        </div>

        <!-- Total Tunggakan Tagihan -->
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Tunggakan Santri Bulan Ini</p>
                    <p class="text-2xl font-extrabold text-rose-600 mt-1">
                        Rp {{ number_format($totalTunggakan, 0, ',', '.') }}
                    </p>
                    <p class="text-xs text-gray-400 mt-1">s.d. {{ now()->translatedFormat('F Y') }}</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-2xl">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-2 mt-4">
                <div class="rounded-xl bg-blue-50/70 border border-blue-100 px-3 py-2">
                    <p class="text-[10px] font-bold uppercase text-blue-700">Putra</p>
                    <p class="text-sm font-extrabold text-blue-800">Rp {{ number_format($tunggakanPutra, 0, ',', '.') }}</p>
                </div>
                <div class="rounded-xl bg-fuchsia-50/70 border border-fuchsia-100 px-3 py-2">
                    <p class="text-[10px] font-bold uppercase text-fuchsia-700">Putri</p>
                    <p class="text-sm font-extrabold text-fuchsia-800">Rp {{ number_format($tunggakanPutri, 0, ',', '.') }}</p>
                </div>
            </div>
            <a href="{{ route('admin.reports.arrears') }}" class="text-xs text-rose-700 font-semibold hover:underline mt-3 inline-flex items-center">
                Lihat Rekap Tunggakan &rarr;
            </a>
        </div>
    </div>

    @if(auth()->user()->role?->canManageFinances())
    <!-- Modul 1-Klik Generate Tagihan SPP Massal -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200/80 rounded-2xl p-6 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-1">
                <h2 class="text-base font-bold text-brand-900 flex items-center">
                    <i class="fa-solid fa-wand-magic-sparkles text-gold-500 mr-2"></i> Auto-Generate Tagihan SPP Bulanan Massal
                </h2>
                <p class="text-xs text-gray-600 max-w-2xl">
                    Buat tagihan SPP rutin (Rp 1.350.000) otomatis untuk semua santri aktif dalam 1 klik. Sistem secara cerdas akan langsung menerapkan potongan beasiswa (misal santri yatim/dhuafa potong 50% = Rp 675.000).
                </p>
            </div>
            <form action="{{ route('admin.generate-spp') }}" method="POST" class="flex items-center gap-2 self-start md:self-auto">
                @csrf
                <select name="month" class="text-xs rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800 bg-white shadow-sm">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $m == now()->month ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::createFromDate(now()->year, $m, 1)->translatedFormat('F') }}
                        </option>
                    @endfor
                </select>
                <input type="number" name="year" value="{{ now()->year }}" class="w-20 text-xs rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800 bg-white shadow-sm">
                <button type="submit" onclick="return confirm('Terbitkan tagihan SPP untuk seluruh santri aktif?')" class="px-4 py-2 bg-brand-900 hover:bg-brand-800 text-white text-xs font-semibold rounded-xl shadow transition whitespace-nowrap">
                    Terbitkan Tagihan
                </button>
            </form>
        </div>
    </div>
    @endif

    <!-- Dua Kolom: Transaksi Terkini & Tagihan Belum Lunas -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Kolom Kiri: Riwayat Transaksi Terkini -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 bg-gray-50/70 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-sm font-bold text-gray-800 flex items-center">
                    <i class="fa-solid fa-receipt text-emerald-600 mr-2"></i> Transaksi Masuk Terkini
                </h3>
                <span class="text-xs text-gray-400">8 Terakhir</span>
            </div>
            <div class="divide-y divide-gray-100 text-xs sm:text-sm">
                @forelse($recentPayments as $pay)
                    <div class="p-4 hover:bg-gray-50/50 flex items-center justify-between">
                        <div class="space-y-0.5">
                            <p class="font-bold text-gray-900">{{ $pay->student->nama_lengkap }} ({{ $pay->student->kelas }})</p>
                            <p class="text-xs text-gray-500">{{ $pay->invoice?->receipt_title ?? '-' }}</p>
                            <p class="text-[11px] font-mono text-gray-400">{{ $pay->receipt->receipt_number ?? '-' }} • {{ $pay->payment_date->format('d/m/Y') }}</p>
                        </div>
                        <div class="text-right space-y-1">
                            <p class="font-extrabold text-emerald-600">Rp {{ number_format($pay->amount_paid, 0, ',', '.') }}</p>
                            @if($pay->receipt)
                                <a href="{{ route('receipt.download', $pay->receipt->id) }}" target="_blank" class="inline-flex items-center text-[11px] text-brand-800 hover:underline font-semibold">
                                    <i class="fa-solid fa-file-pdf mr-1 text-gold-500"></i> PDF Kuitansi
                                </a>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="p-6 text-center text-gray-400 text-xs">Belum ada transaksi pembayaran.</div>
                @endforelse
            </div>
        </div>

        <!-- Kolom Kanan: Tagihan Aktif Menunggu Bayar -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 bg-gray-50/70 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-sm font-bold text-gray-800 flex items-center">
                    <i class="fa-solid fa-hourglass-half text-amber-500 mr-2"></i> Tagihan Belum Lunas Terkini
                </h3>
                @if(auth()->user()->role?->canManageFinances())
                    <a href="{{ route('admin.invoices', ['status' => 'unpaid']) }}" class="text-xs text-brand-700 hover:underline font-semibold">Lihat Semua</a>
                @endif
            </div>
            <div class="divide-y divide-gray-100 text-xs sm:text-sm">
                @forelse($recentInvoices as $inv)
                    <div class="p-4 hover:bg-gray-50/50 flex items-center justify-between">
                        <div class="space-y-0.5">
                            <p class="font-bold text-gray-900">{{ $inv->student->nama_lengkap }} ({{ $inv->student->kelas }})</p>
                            <p class="text-xs text-gray-500">{{ $inv->title }}</p>
                            <p class="text-[11px] text-gray-400">Jatuh Tempo: {{ $inv->due_date ? $inv->due_date->format('d/m/Y') : '-' }}</p>
                        </div>
                        <div class="text-right space-y-1">
                            <p class="font-extrabold text-rose-600">Rp {{ number_format($inv->remaining_amount, 0, ',', '.') }}</p>
                            @if(auth()->user()->role?->canManageFinances())
                                <a href="{{ route('admin.cashier', ['student_id' => $inv->student_id]) }}" class="inline-flex items-center px-2 py-0.5 rounded bg-brand-50 text-brand-800 hover:bg-brand-100 text-[11px] font-semibold transition">
                                    Bayar di Kasir &rarr;
                                </a>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="p-6 text-center text-gray-400 text-xs">Semua tagihan lunas!</div>
                @endforelse
            </div>
        </div>

    </div>

</div>
@endsection
