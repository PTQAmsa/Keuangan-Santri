@extends('layouts.app')

@section('title', 'Pemasukan Lainnya - PTQ Amsa001')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-gray-200">
        <div>
            <div class="flex items-center space-x-2 text-xs text-gray-500 mb-1">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-brand-900">Admin</a>
                <span>/</span>
                <span class="text-gray-800 font-semibold">Kas Masuk</span>
                <span>/</span>
                <span class="text-gray-800 font-semibold">Pemasukan Lainnya</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center">
                <i class="fa-solid fa-hand-holding-heart text-emerald-600 mr-2.5"></i> Pemasukan Lainnya
            </h1>
            <p class="text-xs sm:text-sm text-gray-500 mt-0.5">Donasi, infaq, hibah, hasil unit usaha, sewa, dan pemasukan lain di luar pembayaran santri</p>
        </div>
        @include('admin.incomes._tabs')
    </div>

    <!-- Filter -->
    <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-sm">
        <form method="GET" action="{{ route('admin.incomes') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
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
                <label class="block text-xs font-semibold text-gray-700 mb-1">Jenis Pemasukan</label>
                <select name="category" class="w-full text-xs rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800 focus:border-brand-800 bg-gray-50/50">
                    <option value="">-- Semua Jenis --</option>
                    @foreach($categories as $option)
                        <option value="{{ $option->value }}" {{ $category?->value === $option->value ? 'selected' : '' }}>
                            {{ $option->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center space-x-2">
                <button type="submit" class="w-full py-2 px-4 bg-brand-900 hover:bg-brand-800 text-white rounded-xl text-xs font-semibold shadow-sm transition">
                    <i class="fa-solid fa-filter mr-1"></i> Filter
                </button>
                <a href="{{ route('admin.incomes') }}" class="py-2 px-3 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl text-xs font-semibold transition" title="Reset Filter">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Ringkasan -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-emerald-500 to-emerald-700 rounded-2xl p-5 text-white shadow-md">
            <p class="text-xs font-medium text-emerald-100 uppercase tracking-wider">Total Pemasukan Lainnya</p>
            <p class="text-2xl font-black mt-1">Rp {{ number_format($totalPemasukan, 0, ',', '.') }}</p>
            <p class="text-xs text-emerald-200 mt-2"><i class="fa-solid fa-calendar-check mr-1.5"></i>{{ \Carbon\Carbon::parse($startDate)->format('d M') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</p>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-sm">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Jumlah Transaksi</p>
            <p class="text-2xl font-extrabold text-gray-900 mt-1">{{ $totalTransaksi }} <span class="text-xs font-normal text-gray-500">pemasukan</span></p>
            <p class="text-xs text-gray-400 mt-2">Tidak termasuk yang dibatalkan</p>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-sm">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Diterima Tunai</p>
            <p class="text-xl font-extrabold text-gray-900 mt-1">Rp {{ number_format($totalCash, 0, ',', '.') }}</p>
            <p class="text-xs text-gray-400 mt-2">Masuk ke kas tunai pesantren</p>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-sm">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Diterima Transfer</p>
            <p class="text-xl font-extrabold text-gray-900 mt-1">Rp {{ number_format($totalTransfer, 0, ',', '.') }}</p>
            <p class="text-xs text-gray-400 mt-2">Masuk ke rekening pesantren</p>
        </div>
    </div>

    @if($categoryBreakdown->isNotEmpty())
        <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-sm">
            <h3 class="text-xs font-bold text-gray-600 uppercase tracking-wider mb-3 flex items-center">
                <i class="fa-solid fa-chart-pie text-emerald-600 mr-2"></i> Rincian per Jenis Pemasukan
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach($categoryBreakdown as $label => $row)
                    <div class="rounded-xl bg-gray-50 border border-gray-100 p-3 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-gray-700">{{ $label }}</p>
                            <p class="text-[11px] text-gray-400">{{ $row['count'] }} transaksi</p>
                        </div>
                        <p class="text-sm font-bold text-emerald-700">Rp {{ number_format($row['total'], 0, ',', '.') }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Form Catat Pemasukan -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 bg-gray-50/70 border-b border-gray-100">
            <h2 class="text-base font-bold text-gray-800 flex items-center">
                <i class="fa-solid fa-plus-circle text-brand-800 mr-2"></i> Catat Pemasukan Baru
            </h2>
            <p class="text-xs text-gray-500 mt-0.5">Untuk pembayaran santri gunakan tab Kasir Santri agar kuitansi dan tagihan ikut terbarui.</p>
        </div>
        <form action="{{ route('admin.incomes.store') }}" method="POST" class="p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @csrf
            <div>
                <label for="income_date" class="block text-xs font-semibold text-gray-700 mb-1">Tanggal <span class="text-rose-500">*</span></label>
                <input type="date" name="income_date" id="income_date" value="{{ old('income_date', now()->toDateString()) }}" required class="w-full text-sm rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800 focus:border-brand-800">
            </div>
            <div>
                <label for="category" class="block text-xs font-semibold text-gray-700 mb-1">Jenis Pemasukan <span class="text-rose-500">*</span></label>
                <select name="category" id="category" required class="w-full text-sm rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800 focus:border-brand-800">
                    <option value="">-- Pilih Jenis --</option>
                    @foreach($categories as $option)
                        <option value="{{ $option->value }}" {{ old('category') === $option->value ? 'selected' : '' }}>{{ $option->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="amount" class="block text-xs font-semibold text-gray-700 mb-1">Nominal (Rp) <span class="text-rose-500">*</span></label>
                <input type="number" name="amount" id="amount" value="{{ old('amount') }}" min="1" step="0.01" required placeholder="Contoh: 500000" class="w-full text-sm rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800 focus:border-brand-800">
            </div>
            <div>
                <label for="payment_method" class="block text-xs font-semibold text-gray-700 mb-1">Metode <span class="text-rose-500">*</span></label>
                <select name="payment_method" id="payment_method" required class="w-full text-sm rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800 focus:border-brand-800">
                    <option value="cash" {{ old('payment_method', 'cash') === 'cash' ? 'selected' : '' }}>Tunai</option>
                    <option value="transfer" {{ old('payment_method') === 'transfer' ? 'selected' : '' }}>Transfer Bank</option>
                </select>
            </div>
            <div class="lg:col-span-2">
                <label for="description" class="block text-xs font-semibold text-gray-700 mb-1">Uraian Pemasukan <span class="text-rose-500">*</span></label>
                <input type="text" name="description" id="description" value="{{ old('description') }}" maxlength="255" required placeholder="Contoh: Donasi pembangunan asrama putri" class="w-full text-sm rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800 focus:border-brand-800">
            </div>
            <div>
                <label for="source" class="block text-xs font-semibold text-gray-700 mb-1">Sumber / Pemberi <span class="text-rose-500">*</span></label>
                <input type="text" name="source" id="source" value="{{ old('source') }}" maxlength="150" required placeholder="Nama donatur / unit usaha" class="w-full text-sm rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800 focus:border-brand-800">
            </div>
            <div>
                <label for="notes" class="block text-xs font-semibold text-gray-700 mb-1">Catatan</label>
                <input type="text" name="notes" id="notes" value="{{ old('notes') }}" maxlength="1000" placeholder="Keterangan tambahan" class="w-full text-sm rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800 focus:border-brand-800">
            </div>
            <div class="lg:col-span-4 flex justify-end">
                <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-semibold shadow-sm transition">
                    <i class="fa-solid fa-save mr-2"></i> Simpan Pemasukan
                </button>
            </div>
        </form>
    </div>

    @php
        $sortUrl = function ($column) use ($sortBy, $sortOrder) {
            $order = ($sortBy === $column && $sortOrder === 'asc') ? 'desc' : 'asc';

            return request()->fullUrlWithQuery(['sort_by' => $column, 'sort_order' => $order]);
        };

        $sortIcon = function ($column) use ($sortBy, $sortOrder) {
            if ($sortBy !== $column) {
                return '<i class="fa-solid fa-sort text-gray-300 ml-1"></i>';
            }

            return $sortOrder === 'asc'
                ? '<i class="fa-solid fa-arrow-up-short-wide text-brand-900 ml-1"></i>'
                : '<i class="fa-solid fa-arrow-down-wide-short text-brand-900 ml-1"></i>';
        };
    @endphp

    <!-- Daftar Pemasukan -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 bg-gray-50/70 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-base font-bold text-gray-800 flex items-center">
                <i class="fa-solid fa-list-ul text-brand-800 mr-2"></i> Daftar Pemasukan Lainnya
            </h2>
            <span class="text-xs text-gray-500">{{ $incomes->count() }} baris</span>
        </div>

        @if($incomes->isEmpty())
            <div class="p-10 text-center text-gray-400 text-sm">
                <i class="fa-solid fa-hand-holding-heart text-4xl text-gray-300 block mb-2"></i>
                Belum ada pemasukan lainnya pada periode ini.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs sm:text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-gray-600 uppercase text-[11px] font-semibold border-b border-gray-200 select-none">
                            <th class="py-3 px-3"><a href="{{ $sortUrl('income_date') }}" class="inline-flex items-center hover:text-brand-900" title="Urutkan Tanggal">Tanggal{!! $sortIcon('income_date') !!}</a></th>
                            <th class="py-3 px-3"><a href="{{ $sortUrl('income_code') }}" class="inline-flex items-center hover:text-brand-900" title="Urutkan Kode">Kode{!! $sortIcon('income_code') !!}</a></th>
                            <th class="py-3 px-3"><a href="{{ $sortUrl('category') }}" class="inline-flex items-center hover:text-brand-900" title="Urutkan Jenis">Jenis{!! $sortIcon('category') !!}</a></th>
                            <th class="py-3 px-3"><a href="{{ $sortUrl('description') }}" class="inline-flex items-center hover:text-brand-900" title="Urutkan Uraian">Uraian{!! $sortIcon('description') !!}</a></th>
                            <th class="py-3 px-3 text-right"><a href="{{ $sortUrl('amount') }}" class="inline-flex items-center justify-end hover:text-brand-900" title="Urutkan Nominal">Nominal{!! $sortIcon('amount') !!}</a></th>
                            <th class="py-3 px-3"><a href="{{ $sortUrl('payment_method') }}" class="inline-flex items-center hover:text-brand-900" title="Urutkan Metode">Metode{!! $sortIcon('payment_method') !!}</a></th>
                            <th class="py-3 px-3"><a href="{{ $sortUrl('source') }}" class="inline-flex items-center hover:text-brand-900" title="Urutkan Sumber">Sumber{!! $sortIcon('source') !!}</a></th>
                            <th class="py-3 px-3"><a href="{{ $sortUrl('recorded_by') }}" class="inline-flex items-center hover:text-brand-900" title="Urutkan Pencatat">Dicatat Oleh{!! $sortIcon('recorded_by') !!}</a></th>
                            <th class="py-3 px-3 text-center"><a href="{{ $sortUrl('status') }}" class="inline-flex items-center justify-center hover:text-brand-900" title="Urutkan Status">Status{!! $sortIcon('status') !!}</a></th>
                            <th class="py-3 px-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($incomes as $income)
                            <tr class="hover:bg-gray-50/50 {{ $income->isVoided() ? 'opacity-60' : '' }}">
                                <td class="py-3 px-3 text-gray-600 whitespace-nowrap">{{ $income->income_date?->format('d/m/Y') ?? '-' }}</td>
                                <td class="py-3 px-3 font-mono text-[11px] text-gray-600">{{ $income->income_code }}</td>
                                <td class="py-3 px-3">
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold {{ $income->category->badgeClass() }}">
                                        {{ $income->category->label() }}
                                    </span>
                                </td>
                                <td class="py-3 px-3">
                                    <p class="font-semibold text-gray-800">{{ $income->description }}</p>
                                    @if($income->notes)
                                        <p class="text-[10px] text-gray-400">{{ $income->notes }}</p>
                                    @endif
                                    @if($income->isVoided())
                                        <p class="text-[10px] text-rose-600 mt-0.5">Dibatalkan: {{ $income->void_reason }}</p>
                                    @endif
                                </td>
                                <td class="py-3 px-3 text-right font-bold {{ $income->isVoided() ? 'text-gray-400 line-through' : 'text-emerald-700' }}">
                                    Rp {{ number_format((float) $income->amount, 0, ',', '.') }}
                                </td>
                                <td class="py-3 px-3 text-[11px] text-gray-600">{{ $income->payment_method === 'cash' ? 'Tunai' : 'Transfer Bank' }}</td>
                                <td class="py-3 px-3 text-gray-700">{{ $income->source ?: '-' }}</td>
                                <td class="py-3 px-3 text-gray-600">{{ $income->recordedBy?->name ?? '-' }}</td>
                                <td class="py-3 px-3 text-center">
                                    @if($income->isVoided())
                                        <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-gray-200 text-gray-600">Dibatalkan</span>
                                    @else
                                        <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">Tercatat</span>
                                    @endif
                                </td>
                                <td class="py-3 px-3 text-center">
                                    @unless($income->isVoided())
                                        <form action="{{ route('admin.incomes.void', $income) }}" method="POST" class="inline"
                                              onsubmit="const reason = prompt('Alasan pembatalan {{ $income->income_code }}:'); if (!reason || !reason.trim()) { return false; } this.void_reason.value = reason.trim(); return true;">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="void_reason" value="">
                                            <button type="submit" class="inline-flex items-center px-2.5 py-1.5 rounded-lg bg-gray-100 hover:bg-rose-50 text-gray-600 hover:text-rose-700 text-[11px] font-semibold border border-gray-200 transition">
                                                <i class="fa-solid fa-ban mr-1"></i> Batalkan
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-[10px] text-gray-400">-</span>
                                    @endunless
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded-r-lg shadow-sm text-xs sm:text-sm text-blue-900">
        <p><strong>Catatan:</strong> Pemasukan di sini langsung dihitung di laporan arus kas, dashboard, dan tutup buku bersama pembayaran santri. Pemasukan yang dibatalkan tetap tersimpan untuk audit, tetapi tidak dihitung dalam total. Klik judul kolom untuk mengurutkan data.</p>
    </div>
</div>
@endsection
