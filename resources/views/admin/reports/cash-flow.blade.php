@extends('layouts.app')

@section('title', 'Laporan Arus Kas - PTQ Amsa001')

@section('content')
@php
    $rupiah = fn ($value) => ($value < 0 ? '-Rp ' : 'Rp ').number_format(abs((float) $value), 0, ',', '.');
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
                <i class="fa-solid fa-scale-balanced text-emerald-600 mr-2.5"></i> Laporan Arus Kas
            </h1>
            <p class="text-xs sm:text-sm text-gray-500 mt-0.5">Kas masuk dan kas keluar dalam satu buku kas dengan saldo berjalan</p>
        </div>

        <!-- Navigasi laporan (Kas Keluar sudah ada di menu utama, tidak diulang di sini) -->
        @include('admin.reports._tabs')
    </div>

    <!-- Filter Form Bar -->
    <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-sm">
        <form method="GET" action="{{ route('admin.reports.cash-flow') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
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

            <div class="flex items-center space-x-2">
                <button type="submit" class="w-full py-2 px-4 bg-brand-900 hover:bg-brand-800 text-white rounded-xl text-xs font-semibold shadow-sm transition">
                    <i class="fa-solid fa-filter mr-1"></i> Terapkan
                </button>
                <a href="{{ route('admin.reports.cash-flow') }}" class="py-2 px-3 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl text-xs font-semibold transition" title="Reset Filter">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Ringkasan Saldo -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-sm">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Saldo Awal</p>
            <p class="text-2xl font-extrabold text-gray-900 mt-1">{{ $rupiah($openingBalance) }}</p>
            <p class="text-xs text-gray-400 mt-2">Sebelum {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }}</p>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-emerald-200 shadow-sm">
            <p class="text-xs font-semibold text-emerald-700 uppercase tracking-wider">Kas Masuk</p>
            <p class="text-2xl font-extrabold text-emerald-700 mt-1">{{ $rupiah($totalMasuk) }}</p>
            <p class="text-xs text-gray-400 mt-2">{{ $jumlahMasuk }} kuitansi sah</p>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-rose-200 shadow-sm">
            <p class="text-xs font-semibold text-rose-700 uppercase tracking-wider">Kas Keluar</p>
            <p class="text-2xl font-extrabold text-rose-700 mt-1">{{ $rupiah($totalKeluar) }}</p>
            <p class="text-xs text-gray-400 mt-2">{{ $jumlahKeluar }} pengeluaran sah</p>
        </div>

        <div class="bg-gradient-to-br from-brand-800 to-brand-900 rounded-2xl p-5 text-white shadow-md">
            <p class="text-xs font-medium text-brand-200 uppercase tracking-wider">Saldo Akhir</p>
            <p class="text-2xl font-black mt-1">{{ $rupiah($closingBalance) }}</p>
            <p class="text-xs text-brand-200 mt-2 flex items-center">
                <i class="fa-solid fa-calendar-check mr-1.5"></i>
                {{ \Carbon\Carbon::parse($startDate)->format('d M') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
            </p>
        </div>
    </div>

    <!-- Buku Kas -->
    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h2 class="text-sm font-bold text-gray-900"><i class="fa-solid fa-book-open text-brand-800 mr-1.5"></i> Buku Kas Periode</h2>
                <p class="text-xs text-gray-500 mt-0.5">Urut tanggal; penerimaan dicatat sebelum pengeluaran pada tanggal yang sama.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 text-[11px] font-semibold">
                <span class="px-2.5 py-1 rounded-lg bg-amber-50 text-amber-700">Tunai masuk: {{ $rupiah($cashMasuk) }}</span>
                <span class="px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-700">Transfer masuk: {{ $rupiah($transferMasuk) }}</span>
                <span class="px-2.5 py-1 rounded-lg bg-rose-50 text-rose-700">Tunai keluar: {{ $rupiah($cashKeluar) }}</span>
                <span class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-700">Transfer keluar: {{ $rupiah($transferKeluar) }}</span>
            </div>
        </div>

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

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-gray-600">
                <thead class="bg-gray-100 text-gray-700 font-bold uppercase text-[11px] tracking-wider border-b border-gray-200 select-none">
                    <tr>
                        <th class="py-3 px-4">
                            <a href="{{ $sortUrl('date', $sortBy, $sortOrder) }}" class="inline-flex items-center hover:text-brand-900 transition" title="Urutkan Tanggal">
                                <span>Tanggal</span>{!! $sortIcon('date', $sortBy, $sortOrder) !!}
                            </a>
                        </th>
                        <th class="py-3 px-4">
                            <a href="{{ $sortUrl('code', $sortBy, $sortOrder) }}" class="inline-flex items-center hover:text-brand-900 transition" title="Urutkan Dokumen">
                                <span>Dokumen</span>{!! $sortIcon('code', $sortBy, $sortOrder) !!}
                            </a>
                        </th>
                        <th class="py-3 px-4">
                            <a href="{{ $sortUrl('description', $sortBy, $sortOrder) }}" class="inline-flex items-center hover:text-brand-900 transition" title="Urutkan Uraian">
                                <span>Uraian</span>{!! $sortIcon('description', $sortBy, $sortOrder) !!}
                            </a>
                        </th>
                        <th class="py-3 px-4">
                            <a href="{{ $sortUrl('party', $sortBy, $sortOrder) }}" class="inline-flex items-center hover:text-brand-900 transition" title="Urutkan Pihak">
                                <span>Pihak</span>{!! $sortIcon('party', $sortBy, $sortOrder) !!}
                            </a>
                        </th>
                        <th class="py-3 px-4 text-center">
                            <a href="{{ $sortUrl('method', $sortBy, $sortOrder) }}" class="inline-flex items-center justify-center hover:text-brand-900 transition" title="Urutkan Metode">
                                <span>Metode</span>{!! $sortIcon('method', $sortBy, $sortOrder) !!}
                            </a>
                        </th>
                        <th class="py-3 px-4 text-right">
                            <a href="{{ $sortUrl('masuk', $sortBy, $sortOrder) }}" class="inline-flex items-center justify-end hover:text-brand-900 transition" title="Urutkan Kas Masuk">
                                <span>Masuk</span>{!! $sortIcon('masuk', $sortBy, $sortOrder) !!}
                            </a>
                        </th>
                        <th class="py-3 px-4 text-right">
                            <a href="{{ $sortUrl('keluar', $sortBy, $sortOrder) }}" class="inline-flex items-center justify-end hover:text-brand-900 transition" title="Urutkan Kas Keluar">
                                <span>Keluar</span>{!! $sortIcon('keluar', $sortBy, $sortOrder) !!}
                            </a>
                        </th>
                        <th class="py-3 px-4 text-right">
                            <a href="{{ $sortUrl('saldo', $sortBy, $sortOrder) }}" class="inline-flex items-center justify-end hover:text-brand-900 transition" title="Urutkan Saldo">
                                <span>Saldo</span>{!! $sortIcon('saldo', $sortBy, $sortOrder) !!}
                            </a>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($entries as $entry)
                        <tr class="hover:bg-blue-50/40 transition">
                            <td class="py-3 px-4 whitespace-nowrap">{{ $entry['date']->format('d/m/Y') }}</td>
                            <td class="py-3 px-4 font-mono font-semibold text-brand-900 whitespace-nowrap">{{ $entry['code'] }}</td>
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold {{ $entry['direction'] === 'masuk' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                    {{ $entry['direction'] === 'masuk' ? 'KAS MASUK' : 'KAS KELUAR' }}
                                </span>
                                <p class="text-xs text-gray-700 mt-0.5">{{ $entry['description'] }}</p>
                            </td>
                            <td class="py-3 px-4">{{ $entry['party'] }}</td>
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                @if($entry['method'] === 'transfer')
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
                                {{ $entry['direction'] === 'masuk' ? $rupiah($entry['amount']) : '-' }}
                            </td>
                            <td class="py-3 px-4 text-right font-bold text-rose-600 whitespace-nowrap">
                                {{ $entry['direction'] === 'keluar' ? $rupiah($entry['amount']) : '-' }}
                            </td>
                            <td class="py-3 px-4 text-right font-semibold text-gray-900 whitespace-nowrap">
                                {{ $rupiah($entry['running_balance']) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-gray-400">
                                <i class="fa-solid fa-inbox text-4xl mb-2 text-gray-300 block"></i>
                                Belum ada mutasi kas pada periode filter yang dipilih.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($entries->isNotEmpty())
                    <tfoot class="bg-gray-50 border-t-2 border-gray-200 font-bold text-gray-800 text-xs">
                        <tr>
                            <td colspan="5" class="py-3 px-4 text-right uppercase tracking-wider">Total Mutasi &amp; Saldo Akhir:</td>
                            <td class="py-3 px-4 text-right text-emerald-700">{{ $rupiah($totalMasuk) }}</td>
                            <td class="py-3 px-4 text-right text-rose-700">{{ $rupiah($totalKeluar) }}</td>
                            <td class="py-3 px-4 text-right text-brand-900 font-extrabold">{{ $rupiah($closingBalance) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    <!-- Catatan Tahap Berikutnya -->
    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r-xl text-xs text-blue-800 flex items-start space-x-3">
        <i class="fa-solid fa-circle-info text-blue-600 text-base mt-0.5"></i>
        <p>
            Kerangka laporan arus kas ini baru menampilkan mutasi dan saldo berjalan.
            Ekspor CSV/PDF serta rekap saldo per metode pembayaran akan ditambahkan pada tahap berikutnya.
        </p>
    </div>
</div>
@endsection
