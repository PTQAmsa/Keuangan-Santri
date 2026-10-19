@extends('layouts.app')

@section('title', 'Laporan Rekapitulasi Tunggakan Santri - PTQ Amsa001')

@section('content')
@php
    // Pengasuh (read-only) tidak boleh melihat tombol aksi cepat keuangan.
    $canManageFinances = auth()->user()->role?->canManageFinances();
@endphp
<div class="space-y-6">

    <!-- Header & Breadcrumbs / Tabs -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-gray-200">
        <div>
            <div class="flex items-center space-x-2 text-xs text-gray-500 mb-1">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-brand-900">Admin</a>
                <span>/</span>
                <span class="text-gray-800 font-semibold">Laporan Keuangan</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center">
                <i class="fa-solid fa-clock-rotate-left text-rose-600 mr-2.5"></i> Laporan Rekapitulasi Tunggakan
            </h1>
            <p class="text-xs sm:text-sm text-gray-500 mt-0.5">Daftar santri yang memiliki tagihan belum lunas / angsuran aktif</p>
        </div>

        <!-- Quick Switch Tab -->
        <div class="flex items-center bg-gray-100 p-1 rounded-xl self-start md:self-auto text-xs font-semibold">
            <a href="{{ route('admin.reports.cash') }}" class="px-4 py-2 rounded-lg text-gray-600 hover:text-brand-900 transition">
                <i class="fa-solid fa-money-bill-wave mr-1 text-emerald-600"></i> Kas Masuk
            </a>
            <a href="{{ route('admin.reports.arrears') }}" class="px-4 py-2 rounded-lg bg-white text-brand-900 shadow-sm">
                <i class="fa-solid fa-clock-rotate-left mr-1 text-rose-500"></i> Rekap Tunggakan
            </a>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-sm">
        <form method="GET" action="{{ route('admin.reports.arrears') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Cari Santri / NIS</label>
                <div class="relative">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Nama santri atau NIS..." class="w-full text-xs rounded-xl border-gray-300 py-2 pl-8 pr-3 focus:ring-brand-800 focus:border-brand-800 bg-gray-50/50">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-gray-400 text-xs"></i>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Filter Kelas</label>
                <select name="kelas" class="w-full text-xs rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800 focus:border-brand-800 bg-gray-50/50">
                    <option value="">-- Semua Kelas --</option>
                    @foreach($availableClasses as $kls)
                        <option value="{{ $kls }}" {{ $selectedClass == $kls ? 'selected' : '' }}>
                            Kelas {{ $kls }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Kategori Biaya</label>
                <select name="category_id" class="w-full text-xs rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800 focus:border-brand-800 bg-gray-50/50">
                    <option value="">-- Semua Kategori --</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center space-x-2">
                <button type="submit" class="w-full py-2 px-4 bg-brand-900 hover:bg-brand-800 text-white rounded-xl text-xs font-semibold shadow-sm transition">
                    <i class="fa-solid fa-filter mr-1"></i> Terapkan
                </button>
                <a href="{{ route('admin.reports.arrears') }}" class="py-2 px-3 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl text-xs font-semibold transition" title="Reset Filter">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Ringkasan Statistik KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <!-- Total Tunggakan Keseluruhan -->
        <div class="bg-gradient-to-br from-rose-500 to-rose-700 rounded-2xl p-5 text-white shadow-md">
            <p class="text-xs font-medium text-rose-100 uppercase tracking-wider">Total Nilai Tunggakan</p>
            <p class="text-3xl font-black mt-1">Rp {{ number_format($totalNilaiTunggakan, 0, ',', '.') }}</p>
            <p class="text-xs text-rose-200 mt-2 flex items-center">
                <i class="fa-solid fa-shield-halved mr-1.5"></i>
                Seluruh santri aktif yang belum lunas
            </p>
        </div>

        <!-- Total Santri Menunggak -->
        <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Santri Menunggak</p>
                <p class="text-3xl font-extrabold text-gray-900 mt-1">{{ $totalSantriMenunggak }} <span class="text-xs font-normal text-gray-500">Santri</span></p>
                <p class="text-xs text-gray-400 mt-1">Memiliki sisa tagihan aktif</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-2xl">
                <i class="fa-solid fa-user-xmark"></i>
            </div>
        </div>

        <!-- Total Lembar Tagihan -->
        <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Tagihan Tertunggak</p>
                <p class="text-3xl font-extrabold text-gray-900 mt-1">{{ $totalTagihanTertunggak }} <span class="text-xs font-normal text-gray-500">Invoice</span></p>
                <p class="text-xs text-gray-400 mt-1">Status Belum Bayar / Dicicil</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-2xl">
                <i class="fa-solid fa-file-invoice"></i>
            </div>
        </div>
    </div>

    <!-- Action Export Toolbar & Data Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200/80 overflow-hidden">
        <div class="p-4 sm:p-5 bg-gray-50/70 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h2 class="text-sm font-bold text-gray-900">Daftar Santri & Rincian Tunggakan</h2>
                <p class="text-xs text-gray-500">Menampilkan {{ $totalSantriMenunggak }} santri dengan tagihan aktif</p>
            </div>

            <!-- Export Buttons -->
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.reports.arrears.csv', request()->query()) }}" class="inline-flex items-center px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-sm transition">
                    <i class="fa-solid fa-file-excel mr-1.5"></i> Export Excel (CSV)
                </a>
                <a href="{{ route('admin.reports.arrears.pdf', request()->query()) }}" target="_blank" class="inline-flex items-center px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-semibold shadow-sm transition">
                    <i class="fa-solid fa-file-pdf mr-1.5"></i> Unduh PDF
                </a>
                <button onclick="window.print()" class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 text-gray-700 hover:bg-gray-100 rounded-xl text-xs font-semibold shadow-sm transition">
                    <i class="fa-solid fa-print mr-1.5"></i> Cetak
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-gray-600">
                <thead class="bg-gray-100 text-gray-700 font-bold uppercase text-[11px] tracking-wider border-b border-gray-200">
                    <tr>
                        <th class="py-3 px-4 text-center">No</th>
                        <th class="py-3 px-4">Santri & Kelas</th>
                        <th class="py-3 px-4">Wali Santri & Kontak</th>
                        <th class="py-3 px-4">Rincian Tagihan Tertunggak</th>
                        <th class="py-3 px-4 text-right">Total Tunggakan</th>
                        @if($canManageFinances)
                            <th class="py-3 px-4 text-center">Aksi Cepat</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @php $no = 1; @endphp
                    @forelse($studentArrears as $item)
                        @php
                            $cleanPhone = preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $item['student']->no_whatsapp_wali));
                            $waText = "Assalamu'alaikum Warahmatullahi Wabarakatuh,\nBapak/Ibu {$item['student']->nama_wali} (Wali dari {$item['student']->nama_lengkap}).\n\nKami menginformasikan rekapitulasi kewajiban keuangan di Pesantren Tadabbur Al-Qur'an Amsa001 saat ini memiliki total tunggakan sebesar *Rp " . number_format($item['total_tunggakan'], 0, ',', '.') . "*.\n\nUntuk rincian dan pembayaran, dapat dilihat langsung di Portal Wali Santri atau menghubungi Bendahara Pesantren.\nJazakumullah khairan katsiran.";
                            $waUrl = "https://wa.me/{$cleanPhone}?text=" . urlencode($waText);
                        @endphp
                        <tr class="hover:bg-rose-50/20 transition">
                            <td class="py-3 px-4 text-center text-gray-400 font-mono">{{ $no++ }}</td>
                            <td class="py-3 px-4">
                                <p class="font-bold text-gray-900">{{ $item['student']->nama_lengkap }}</p>
                                <p class="text-[11px] text-gray-400">NIS: {{ $item['student']->nis }} • Kelas {{ $item['student']->kelas }}</p>
                                @if($item['student']->diskon_beasiswa > 0)
                                    <span class="inline-block mt-0.5 px-2 py-0.2 rounded text-[10px] font-semibold bg-blue-50 text-blue-700">
                                        Beasiswa {{ $item['student']->diskon_beasiswa }}%
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                <p class="font-semibold text-gray-800">{{ $item['student']->nama_wali ?? '-' }}</p>
                                <p class="text-[11px] text-gray-500 font-mono">{{ $item['student']->no_whatsapp_wali ?? '-' }}</p>
                                @if($item['student']->no_whatsapp_wali && $canManageFinances)
                                    <a href="{{ $waUrl }}" target="_blank" class="inline-flex items-center text-[10px] font-semibold text-emerald-600 hover:text-emerald-700 hover:underline mt-1">
                                        <i class="fa-brands fa-whatsapp mr-1 text-xs"></i> Kirim Reminder WA
                                    </a>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                <div class="space-y-1">
                                    @foreach($item['invoices'] as $inv)
                                        <div class="flex items-center justify-between text-[11px] p-1.5 rounded bg-gray-50 border border-gray-100">
                                            <div class="truncate max-w-xs pr-2">
                                                <span class="font-semibold text-gray-700">{{ $inv->title }}</span>
                                                <span class="text-gray-400 block text-[10px]">Tempo: {{ $inv->due_date ? $inv->due_date->format('d/m/Y') : '-' }}</span>
                                            </div>
                                            <div class="text-right whitespace-nowrap">
                                                <span class="font-bold text-rose-600">Rp {{ number_format($inv->remaining_amount, 0, ',', '.') }}</span>
                                                @if($inv->status == 'partial')
                                                    <span class="block text-[9px] text-amber-600 font-semibold">(Cicilan)</span>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                            <td class="py-3 px-4 text-right font-extrabold text-rose-600 text-sm whitespace-nowrap">
                                Rp {{ number_format($item['total_tunggakan'], 0, ',', '.') }}
                            </td>
                            @if($canManageFinances)
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    <a href="{{ route('admin.cashier', ['student_id' => $item['student']->id]) }}" class="inline-flex items-center px-3 py-1.5 rounded-xl bg-brand-900 hover:bg-brand-800 text-white text-xs font-semibold shadow-sm transition">
                                        <i class="fa-solid fa-cash-register mr-1.5 text-gold-400"></i> Bayar di Kasir
                                    </a>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canManageFinances ? 6 : 5 }}" class="py-12 text-center text-gray-400">
                                <i class="fa-solid fa-circle-check text-4xl mb-2 text-emerald-400 block"></i>
                                MasyaAllah, tidak ada santri yang memiliki tunggakan untuk filter ini!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($studentArrears->isNotEmpty())
                    <tfoot class="bg-gray-50 border-t-2 border-gray-200 font-bold text-gray-800 text-xs">
                        <tr>
                            <td colspan="4" class="py-3 px-4 text-right uppercase tracking-wider">Total Seluruh Tunggakan:</td>
                            <td class="py-3 px-4 text-right text-rose-700 font-black text-base">
                                Rp {{ number_format($totalNilaiTunggakan, 0, ',', '.') }}
                            </td>
                            @if($canManageFinances)
                                <td></td>
                            @endif
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

</div>
@endsection
