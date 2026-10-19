@extends('layouts.app')

@section('title', 'Laporan Penerimaan Kas - PTQ Amsa001')

@section('content')
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
                <i class="fa-solid fa-file-invoice-dollar text-emerald-600 mr-2.5"></i> Laporan Penerimaan Kas
            </h1>
            <p class="text-xs sm:text-sm text-gray-500 mt-0.5">Rekapitulasi seluruh dana masuk pembayaran santri PTQ Amsa001</p>
        </div>

        <!-- Quick Switch Tab -->
        <div class="flex items-center bg-gray-100 p-1 rounded-xl self-start md:self-auto text-xs font-semibold">
            <a href="{{ route('admin.reports.cash') }}" class="px-4 py-2 rounded-lg bg-white text-brand-900 shadow-sm">
                <i class="fa-solid fa-money-bill-wave mr-1 text-emerald-600"></i> Kas Masuk
            </a>
            <a href="{{ route('admin.reports.arrears') }}" class="px-4 py-2 rounded-lg text-gray-600 hover:text-brand-900 transition">
                <i class="fa-solid fa-clock-rotate-left mr-1 text-rose-500"></i> Rekap Tunggakan
            </a>
        </div>
    </div>

    <!-- Filter Form Bar -->
    <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-sm">
        <form method="GET" action="{{ route('admin.reports.cash') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
            <!-- Menjaga urutan kolom tetap tersimpan saat filter diterapkan -->
            <input type="hidden" name="sort_by" value="{{ $sortBy }}">
            <input type="hidden" name="sort_order" value="{{ $sortOrder }}">

            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Dari Tanggal</label>
                <input type="date" name="start_date" value="{{ $startDate }}" class="w-full text-xs rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800 focus:border-brand-800 bg-gray-50/50">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Sampai Tanggal</label>
                <input type="date" name="end_date" value="{{ $endDate }}" class="w-full text-xs rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800 focus:border-brand-800 bg-gray-50/50">
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

            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Metode Bayar</label>
                <select name="payment_method" class="w-full text-xs rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800 focus:border-brand-800 bg-gray-50/50">
                    <option value="">-- Semua Metode --</option>
                    <option value="cash" {{ $paymentMethod == 'cash' ? 'selected' : '' }}>Tunai / Kasir</option>
                    <option value="transfer" {{ $paymentMethod == 'transfer' ? 'selected' : '' }}>Transfer Bank</option>
                </select>
            </div>

            <div class="flex items-center space-x-2">
                <button type="submit" class="w-full py-2 px-4 bg-brand-900 hover:bg-brand-800 text-white rounded-xl text-xs font-semibold shadow-sm transition">
                    <i class="fa-solid fa-filter mr-1"></i> Filter
                </button>
                <a href="{{ route('admin.reports.cash') }}" class="py-2 px-3 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl text-xs font-semibold transition" title="Reset Filter">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Ringkasan Statistik KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Penerimaan -->
        <div class="bg-gradient-to-br from-emerald-500 to-emerald-700 rounded-2xl p-5 text-white shadow-md">
            <p class="text-xs font-medium text-emerald-100 uppercase tracking-wider">Total Kas Masuk</p>
            <p class="text-2xl font-black mt-1">Rp {{ number_format($totalPenerimaan, 0, ',', '.') }}</p>
            <p class="text-xs text-emerald-200 mt-2 flex items-center">
                <i class="fa-solid fa-calendar-check mr-1.5"></i>
                {{ \Carbon\Carbon::parse($startDate)->format('d M') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
            </p>
        </div>

        <!-- Total Transaksi -->
        <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Jumlah Transaksi</p>
                    <p class="text-2xl font-extrabold text-gray-900 mt-1">{{ $totalTransaksi }} <span class="text-xs font-normal text-gray-500">Kuitansi</span></p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-brand-800 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-receipt"></i>
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-2">Kuitansi terbit dan terverifikasi</p>
        </div>

        <!-- Total Cash -->
        <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Penerimaan Tunai</p>
                    <p class="text-xl font-extrabold text-gray-900 mt-1">Rp {{ number_format($totalCash, 0, ',', '.') }}</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-hand-holding-dollar"></i>
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-2">Diterima langsung di meja kasir</p>
        </div>

        <!-- Total Transfer -->
        <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Transfer Rekening</p>
                    <p class="text-xl font-extrabold text-gray-900 mt-1">Rp {{ number_format($totalTransfer, 0, ',', '.') }}</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-building-columns"></i>
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-2">Bank Syariah Indonesia (BSI)</p>
        </div>
    </div>

    <!-- Breakdown per Kategori Biaya -->
    @if($categoryBreakdown->isNotEmpty())
        <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-sm">
            <h3 class="text-xs font-bold text-gray-600 uppercase tracking-wider mb-3 flex items-center">
                <i class="fa-solid fa-chart-pie text-brand-700 mr-2"></i> Rincian Dana per Komponen Pembiayaan
            </h3>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                @foreach($categoryBreakdown as $catName => $data)
                    <div class="p-3 rounded-xl bg-gray-50 border border-gray-100 flex flex-col justify-between">
                        <span class="text-xs font-semibold text-gray-600 truncate">{{ $catName }}</span>
                        <div class="mt-2">
                            <span class="text-sm font-bold text-gray-900">Rp {{ number_format($data['total'], 0, ',', '.') }}</span>
                            <span class="text-[11px] text-gray-500 block">{{ $data['count'] }} transaksi</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Helper tautan & ikon sortir kolom (closure agar aman dari deklarasi ulang) -->
    @php
        $sortUrl = function ($column, $currentSort, $currentOrder) {
            $order = ($currentSort === $column && $currentOrder === 'asc') ? 'desc' : 'asc';

            return request()->fullUrlWithQuery(['sort_by' => $column, 'sort_order' => $order]);
        };

        $sortIcon = function ($column, $currentSort, $currentOrder) {
            if ($currentSort !== $column) {
                return '<i class="fa-solid fa-sort text-gray-300 ml-1"></i>';
            }

            return $currentOrder === 'asc'
                ? '<i class="fa-solid fa-arrow-up-short-wide text-brand-900 ml-1"></i>'
                : '<i class="fa-solid fa-arrow-down-wide-short text-brand-900 ml-1"></i>';
        };
    @endphp

    <!-- Action Export Toolbar & Data Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200/80 overflow-hidden">
        <div class="p-4 sm:p-5 bg-gray-50/70 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h2 class="text-sm font-bold text-gray-900">Rincian Transaksi Pembayaran</h2>
                <p class="text-xs text-gray-500">Menampilkan {{ $payments->count() }} transaksi dalam periode terpilih</p>
            </div>

            <!-- Export Buttons -->
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.reports.cash.csv', request()->query()) }}" class="inline-flex items-center px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-sm transition">
                    <i class="fa-solid fa-file-excel mr-1.5"></i> Export Excel (CSV)
                </a>
                <a href="{{ route('admin.reports.cash.pdf', request()->query()) }}" target="_blank" class="inline-flex items-center px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-semibold shadow-sm transition">
                    <i class="fa-solid fa-file-pdf mr-1.5"></i> Unduh PDF
                </a>
                <button onclick="window.print()" class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 text-gray-700 hover:bg-gray-100 rounded-xl text-xs font-semibold shadow-sm transition">
                    <i class="fa-solid fa-print mr-1.5"></i> Cetak
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-gray-600">
                <thead class="bg-gray-100 text-gray-700 font-bold uppercase text-[11px] tracking-wider border-b border-gray-200 select-none">
                    <tr>
                        <th class="py-3 px-4 text-center">
                            <a href="{{ $sortUrl('id', $sortBy, $sortOrder) }}" class="inline-flex items-center justify-center hover:text-brand-900 transition" title="Urutkan No">
                                <span>No</span>{!! $sortIcon('id', $sortBy, $sortOrder) !!}
                            </a>
                        </th>
                        <th class="py-3 px-4">
                            <a href="{{ $sortUrl('receipt_number', $sortBy, $sortOrder) }}" class="inline-flex items-center hover:text-brand-900 transition" title="Urutkan No Kuitansi">
                                <span>No Kuitansi</span>{!! $sortIcon('receipt_number', $sortBy, $sortOrder) !!}
                            </a>
                        </th>
                        <th class="py-3 px-4">
                            <a href="{{ $sortUrl('payment_date', $sortBy, $sortOrder) }}" class="inline-flex items-center hover:text-brand-900 transition" title="Urutkan Tanggal">
                                <span>Tanggal</span>{!! $sortIcon('payment_date', $sortBy, $sortOrder) !!}
                            </a>
                        </th>
                        <th class="py-3 px-4">
                            <a href="{{ $sortUrl('student_name', $sortBy, $sortOrder) }}" class="inline-flex items-center hover:text-brand-900 transition" title="Urutkan Nama Santri">
                                <span>Santri</span>{!! $sortIcon('student_name', $sortBy, $sortOrder) !!}
                            </a>
                        </th>
                        <th class="py-3 px-4">
                            <a href="{{ $sortUrl('category', $sortBy, $sortOrder) }}" class="inline-flex items-center hover:text-brand-900 transition" title="Urutkan Komponen Tagihan">
                                <span>Komponen Tagihan</span>{!! $sortIcon('category', $sortBy, $sortOrder) !!}
                            </a>
                        </th>
                        <th class="py-3 px-4 text-center">
                            <a href="{{ $sortUrl('payment_method', $sortBy, $sortOrder) }}" class="inline-flex items-center justify-center hover:text-brand-900 transition" title="Urutkan Metode">
                                <span>Metode</span>{!! $sortIcon('payment_method', $sortBy, $sortOrder) !!}
                            </a>
                        </th>
                        <th class="py-3 px-4 text-right">
                            <a href="{{ $sortUrl('amount_paid', $sortBy, $sortOrder) }}" class="inline-flex items-center justify-end hover:text-brand-900 transition" title="Urutkan Nominal Bayar">
                                <span>Nominal Bayar</span>{!! $sortIcon('amount_paid', $sortBy, $sortOrder) !!}
                            </a>
                        </th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($payments as $index => $pay)
                        <tr class="hover:bg-blue-50/40 transition">
                            <td class="py-3 px-4 text-center text-gray-400 font-mono">{{ $index + 1 }}</td>
                            <td class="py-3 px-4 font-mono font-semibold text-brand-900">
                                {{ $pay->receipt->receipt_number ?? '-' }}
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                {{ $pay->payment_date ? $pay->payment_date->format('d/m/Y') : '-' }}
                            </td>
                            <td class="py-3 px-4">
                                <p class="font-bold text-gray-900">{{ $pay->student->nama_lengkap ?? '-' }}</p>
                                <p class="text-[11px] text-gray-400">NIS: {{ $pay->student->nis ?? '-' }} • Kelas {{ $pay->student->kelas ?? '-' }}</p>
                            </td>
                            <td class="py-3 px-4">
                                <span class="inline-block px-2 py-0.5 rounded text-[10px] font-semibold bg-gray-100 text-gray-700 mb-0.5">
                                    {{ $pay->invoice->category->name ?? 'Tagihan' }}
                                </span>
                                <p class="text-xs text-gray-600 truncate max-w-xs">{{ $pay->invoice->title ?? '-' }}</p>
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if($pay->payment_method == 'transfer')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-indigo-50 text-indigo-700">
                                        <i class="fa-solid fa-building-columns mr-1"></i> Transfer
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700">
                                        <i class="fa-solid fa-hand-holding-dollar mr-1"></i> Tunai
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right font-bold text-emerald-600 whitespace-nowrap">
                                Rp {{ number_format($pay->amount_paid, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                @if($pay->receipt)
                                    <a href="{{ route('receipt.download', $pay->receipt->id) }}" target="_blank" class="inline-flex items-center px-2.5 py-1 rounded-lg bg-brand-50 hover:bg-brand-100 text-brand-900 text-xs font-semibold transition" title="Unduh Kuitansi PDF">
                                        <i class="fa-solid fa-file-pdf mr-1 text-gold-500"></i> PDF
                                    </a>
                                @else
                                    <span class="text-gray-300">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-gray-400">
                                <i class="fa-solid fa-inbox text-4xl mb-2 text-gray-300 block"></i>
                                Tidak ditemukan data transaksi pada periode filter yang dipilih.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($payments->isNotEmpty())
                    <tfoot class="bg-gray-50 border-t-2 border-gray-200 font-bold text-gray-800 text-xs">
                        <tr>
                            <td colspan="6" class="py-3 px-4 text-right uppercase tracking-wider">Total Penerimaan Kas:</td>
                            <td class="py-3 px-4 text-right text-emerald-700 font-extrabold text-sm">
                                Rp {{ number_format($totalPenerimaan, 0, ',', '.') }}
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

</div>
@endsection
