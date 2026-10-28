@extends('layouts.app')

@section('title', 'Kas Keluar - PTQ Amsa001')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-gray-200">
        <div>
            <div class="flex items-center space-x-2 text-xs text-gray-500 mb-1">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-brand-900">Admin</a>
                <span>/</span>
                <a href="{{ route('admin.reports.cash') }}" class="hover:text-brand-900">Laporan Keuangan</a>
                <span>/</span>
                <span class="text-gray-800 font-semibold">Kas Keluar</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center">
                <i class="fa-solid fa-money-bill-trend-up text-rose-600 mr-2.5"></i> Kas Keluar
            </h1>
            <p class="text-xs sm:text-sm text-gray-500 mt-0.5">Catat dan pantau pengeluaran operasional pesantren</p>
        </div>
    </div>

    <!-- Filter -->
    <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-sm">
        <form method="GET" action="{{ route('admin.expenses') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
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
                <label class="block text-xs font-semibold text-gray-700 mb-1">Kategori</label>
                <select name="category" class="w-full text-xs rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800 focus:border-brand-800 bg-gray-50/50">
                    <option value="">-- Semua Kategori --</option>
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
                <a href="{{ route('admin.expenses') }}" class="py-2 px-3 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl text-xs font-semibold transition" title="Reset Filter">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Ringkasan -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-rose-500 to-rose-700 rounded-2xl p-5 text-white shadow-md">
            <p class="text-xs font-medium text-rose-100 uppercase tracking-wider">Total Kas Keluar</p>
            <p class="text-2xl font-black mt-1">Rp {{ number_format($totalPengeluaran, 0, ',', '.') }}</p>
            <p class="text-xs text-rose-200 mt-2"><i class="fa-solid fa-calendar-check mr-1.5"></i>{{ \Carbon\Carbon::parse($startDate)->format('d M') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</p>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-sm">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Jumlah Transaksi</p>
            <p class="text-2xl font-extrabold text-gray-900 mt-1">{{ $totalTransaksi }} <span class="text-xs font-normal text-gray-500">pengeluaran</span></p>
            <p class="text-xs text-gray-400 mt-2">Tidak termasuk yang dibatalkan</p>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-sm">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Pengeluaran Tunai</p>
            <p class="text-xl font-extrabold text-gray-900 mt-1">Rp {{ number_format($totalCash, 0, ',', '.') }}</p>
            <p class="text-xs text-gray-400 mt-2">Dibayar langsung dari kasir</p>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-sm">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Pengeluaran Transfer</p>
            <p class="text-xl font-extrabold text-gray-900 mt-1">Rp {{ number_format($totalTransfer, 0, ',', '.') }}</p>
            <p class="text-xs text-gray-400 mt-2">Melalui rekening bank atau dompet digital</p>
        </div>
    </div>

    @if($categoryBreakdown->isNotEmpty())
        <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-sm">
            <h3 class="text-xs font-bold text-gray-600 uppercase tracking-wider mb-3 flex items-center">
                <i class="fa-solid fa-chart-pie text-rose-600 mr-2"></i> Rincian Kas Keluar per Kategori
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach($categoryBreakdown as $label => $row)
                    <div class="rounded-xl bg-gray-50 border border-gray-100 p-3 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-gray-700">{{ $label }}</p>
                            <p class="text-[11px] text-gray-400">{{ $row['count'] }} transaksi</p>
                        </div>
                        <p class="text-sm font-bold text-rose-700">Rp {{ number_format($row['total'], 0, ',', '.') }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Form Catat Pengeluaran -->
    <div id="catat" data-write class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden scroll-mt-20">
        <div class="px-6 py-4 bg-gray-50/70 border-b border-gray-100">
            <h2 class="text-base font-bold text-gray-800 flex items-center">
                <i class="fa-solid fa-plus-circle text-brand-800 mr-2"></i> Catat Pengeluaran Baru
            </h2>
            <p class="text-xs text-gray-500 mt-0.5">Isi data pengeluaran operasional dengan lengkap agar laporan kas tetap akurat.</p>
        </div>
        <form action="{{ route('admin.expenses.store') }}" method="POST" class="p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @csrf
            <div>
                <label for="expense_date" class="block text-xs font-semibold text-gray-700 mb-1">Tanggal <span class="text-rose-500">*</span></label>
                <input type="date" name="expense_date" id="expense_date" value="{{ old('expense_date', now()->toDateString()) }}" required class="w-full text-sm rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800 focus:border-brand-800">
            </div>
            <div>
                <label for="category" class="block text-xs font-semibold text-gray-700 mb-1">Kategori <span class="text-rose-500">*</span></label>
                <select name="category" id="category" required class="w-full text-sm rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800 focus:border-brand-800">
                    <option value="">-- Pilih Kategori --</option>
                    @foreach($categories as $option)
                        <option value="{{ $option->value }}" {{ old('category') === $option->value ? 'selected' : '' }}>{{ $option->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="amount" class="block text-xs font-semibold text-gray-700 mb-1">Nominal (Rp) <span class="text-rose-500">*</span></label>
                <input type="number" name="amount" id="amount" value="{{ old('amount') }}" min="1" step="0.01" required placeholder="Contoh: 250000" class="w-full text-sm rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800 focus:border-brand-800">
            </div>
            <div>
                <label for="payment_method" class="block text-xs font-semibold text-gray-700 mb-1">Metode <span class="text-rose-500">*</span></label>
                <select name="payment_method" id="payment_method" required onchange="toggleExpenseTransferFields()" class="w-full text-sm rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800 focus:border-brand-800">
                    <option value="cash" {{ old('payment_method', 'cash') === 'cash' ? 'selected' : '' }}>Tunai</option>
                    <option value="transfer" {{ old('payment_method') === 'transfer' ? 'selected' : '' }}>Transfer (Bank / Dompet Digital)</option>
                </select>
            </div>
            <div id="expense-transfer-fields" class="lg:col-span-4 grid grid-cols-1 sm:grid-cols-2 gap-4 bg-blue-50/60 border border-blue-100 rounded-xl p-4 {{ old('payment_method') === 'transfer' ? '' : 'hidden' }}">
                <p class="sm:col-span-2 text-[11px] text-blue-800">
                    <i class="fa-solid fa-building-columns mr-1"></i>
                    Catat lewat mana uangnya keluar: rekening bank atau dompet digital. Pilih Lainnya bila tidak ada di daftar.
                </p>
                <x-transfer-channel-fields :channel="old('transfer_channel')" :other="old('transfer_channel_other', '')" :required="old('payment_method') === 'transfer'" />
            </div>
            <div class="lg:col-span-2">
                <label for="description" class="block text-xs font-semibold text-gray-700 mb-1">Uraian Pengeluaran <span class="text-rose-500">*</span></label>
                <input type="text" name="description" id="description" value="{{ old('description') }}" maxlength="255" required placeholder="Contoh: Pembelian alat kebersihan asrama" class="w-full text-sm rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800 focus:border-brand-800">
            </div>
            <div>
                <label for="recipient" class="block text-xs font-semibold text-gray-700 mb-1">Penerima</label>
                <input type="text" name="recipient" id="recipient" value="{{ old('recipient') }}" maxlength="150" placeholder="Nama toko / petugas" class="w-full text-sm rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800 focus:border-brand-800">
            </div>
            <div>
                <label for="notes" class="block text-xs font-semibold text-gray-700 mb-1">Catatan</label>
                <input type="text" name="notes" id="notes" value="{{ old('notes') }}" maxlength="1000" placeholder="Nomor nota / keterangan tambahan" class="w-full text-sm rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800 focus:border-brand-800">
            </div>
            <div class="lg:col-span-4 flex justify-end">
                <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-sm font-semibold shadow-sm transition">
                    <i class="fa-solid fa-save mr-2"></i> Simpan Pengeluaran
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

    <!-- Daftar Pengeluaran -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 bg-gray-50/70 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-base font-bold text-gray-800 flex items-center">
                <i class="fa-solid fa-list-ul text-brand-800 mr-2"></i> Daftar Kas Keluar
            </h2>
            <span class="text-xs text-gray-500">{{ $expenses->count() }} baris</span>
        </div>

        @if($expenses->isEmpty())
            <div class="p-10 text-center text-gray-400 text-sm">
                <i class="fa-solid fa-receipt text-4xl text-gray-300 block mb-2"></i>
                Belum ada pengeluaran pada periode ini.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs sm:text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-gray-600 uppercase text-[11px] font-semibold border-b border-gray-200 select-none">
                            <th class="py-3 px-3"><a href="{{ $sortUrl('expense_date') }}" class="inline-flex items-center hover:text-brand-900" title="Urutkan Tanggal">Tanggal{!! $sortIcon('expense_date') !!}</a></th>
                            <th class="py-3 px-3"><a href="{{ $sortUrl('expense_code') }}" class="inline-flex items-center hover:text-brand-900" title="Urutkan Kode">Kode{!! $sortIcon('expense_code') !!}</a></th>
                            <th class="py-3 px-3"><a href="{{ $sortUrl('category') }}" class="inline-flex items-center hover:text-brand-900" title="Urutkan Kategori">Kategori{!! $sortIcon('category') !!}</a></th>
                            <th class="py-3 px-3"><a href="{{ $sortUrl('description') }}" class="inline-flex items-center hover:text-brand-900" title="Urutkan Uraian">Uraian{!! $sortIcon('description') !!}</a></th>
                            <th class="py-3 px-3 text-right"><a href="{{ $sortUrl('amount') }}" class="inline-flex items-center justify-end hover:text-brand-900" title="Urutkan Nominal">Nominal{!! $sortIcon('amount') !!}</a></th>
                            <th class="py-3 px-3"><a href="{{ $sortUrl('payment_method') }}" class="inline-flex items-center hover:text-brand-900" title="Urutkan Metode">Metode{!! $sortIcon('payment_method') !!}</a></th>
                            <th class="py-3 px-3"><a href="{{ $sortUrl('recipient') }}" class="inline-flex items-center hover:text-brand-900" title="Urutkan Penerima">Penerima{!! $sortIcon('recipient') !!}</a></th>
                            <th class="py-3 px-3"><a href="{{ $sortUrl('recorded_by') }}" class="inline-flex items-center hover:text-brand-900" title="Urutkan Pencatat">Dicatat Oleh{!! $sortIcon('recorded_by') !!}</a></th>
                            <th class="py-3 px-3 text-center"><a href="{{ $sortUrl('status') }}" class="inline-flex items-center justify-center hover:text-brand-900" title="Urutkan Status">Status{!! $sortIcon('status') !!}</a></th>
                            <th class="py-3 px-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($expenses as $expense)
                            <tr class="hover:bg-gray-50/50 {{ $expense->isVoided() ? 'opacity-60' : '' }}">
                                <td class="py-3 px-3 text-gray-600 whitespace-nowrap">{{ $expense->expense_date?->format('d/m/Y') ?? '-' }}</td>
                                <td class="py-3 px-3 font-mono text-[11px] text-gray-600">{{ $expense->expense_code }}</td>
                                <td class="py-3 px-3">
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold {{ $expense->category->badgeClass() }}">
                                        {{ $expense->category->label() }}
                                    </span>
                                </td>
                                <td class="py-3 px-3">
                                    <p class="font-semibold text-gray-800">{{ $expense->description }}</p>
                                    @if($expense->notes)
                                        <p class="text-[10px] text-gray-400">{{ $expense->notes }}</p>
                                    @endif
                                    @if($expense->isVoided())
                                        <p class="text-[10px] text-rose-600 mt-0.5">Dibatalkan: {{ $expense->void_reason }}</p>
                                    @endif
                                </td>
                                <td class="py-3 px-3 text-right font-bold {{ $expense->isVoided() ? 'text-gray-400 line-through' : 'text-rose-700' }}">
                                    Rp {{ number_format((float) $expense->amount, 0, ',', '.') }}
                                </td>
                                <td class="py-3 px-3 text-[11px] text-gray-600 whitespace-nowrap">{{ $expense->paymentMethodLabel() }}</td>
                                <td class="py-3 px-3 text-gray-700">{{ $expense->recipient ?: '-' }}</td>
                                <td class="py-3 px-3 text-gray-600">{{ $expense->recordedBy?->name ?? '-' }}</td>
                                <td class="py-3 px-3 text-center">
                                    @if($expense->isVoided())
                                        <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-gray-200 text-gray-600">Dibatalkan</span>
                                    @else
                                        <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800">Tercatat</span>
                                    @endif
                                </td>
                                <td class="py-3 px-3 text-center">
                                    @if(! $expense->isVoided() && $expense->bisyarahPayment)
                                        <a href="{{ route('admin.bisyarah', ['period' => sprintf('%04d-%02d', $expense->bisyarahPayment->period_year, $expense->bisyarahPayment->period_month)]) }}#guru-{{ $expense->bisyarahPayment->teacher_id }}"
                                           title="Kas keluar ini berasal dari modul Bisyarah. Ubah atau batalkan lewat halaman Bisyarah Bulanan."
                                           class="inline-flex items-center px-2.5 py-1.5 rounded-lg bg-violet-50 hover:bg-violet-100 text-violet-700 text-[11px] font-semibold border border-violet-200 transition">
                                            <i class="fa-solid fa-chalkboard-user mr-1"></i> Bisyarah
                                        </a>
                                    @elseif(! $expense->isVoided())
                                        <form action="{{ route('admin.expenses.void', $expense) }}" method="POST" class="inline"
                                              onsubmit="const reason = prompt('Alasan pembatalan {{ $expense->expense_code }}:'); if (!reason || !reason.trim()) { return false; } this.void_reason.value = reason.trim(); return true;">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="void_reason" value="">
                                            <button type="submit" class="inline-flex items-center px-2.5 py-1.5 rounded-lg bg-gray-100 hover:bg-rose-50 text-gray-600 hover:text-rose-700 text-[11px] font-semibold border border-gray-200 transition">
                                                <i class="fa-solid fa-ban mr-1"></i> Batalkan
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-[10px] text-gray-400">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="bg-blue-50/60 border border-blue-100 rounded-xl p-4 text-[11px] text-blue-800">
        <p><strong>Catatan:</strong> Pengeluaran yang dibatalkan tetap tersimpan untuk keperluan audit, tetapi tidak dihitung dalam total kas keluar. Gunakan pembatalan jika ada kesalahan input, lalu catat ulang data yang benar. Klik judul kolom pada tabel untuk mengurutkan data.</p>
    </div>
</div>

<script>
    // Pilihan bank / dompet digital hanya muncul (dan wajib) untuk metode transfer.
    function toggleExpenseTransferFields() {
        const method = document.getElementById('payment_method');
        const box = document.getElementById('expense-transfer-fields');
        if (!method || !box) return;

        const isTransfer = method.value === 'transfer';
        box.classList.toggle('hidden', !isTransfer);

        const channel = box.querySelector('[data-transfer-channel]');
        if (channel) {
            channel.required = isTransfer;
            toggleTransferChannelOther(channel);
        }
    }

    document.addEventListener('DOMContentLoaded', toggleExpenseTransferFields);
</script>
@endsection
