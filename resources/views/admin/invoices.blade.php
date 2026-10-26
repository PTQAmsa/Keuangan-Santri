@extends('layouts.app')

@section('title', 'Rekapitulasi Tagihan Santri - PTQ Amsa001')

@section('content')
<div class="space-y-6">

    <!-- Header & Action Buttons -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-gray-200">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Rekapitulasi Semua Tagihan Santri</h1>
            <p class="text-xs sm:text-sm text-gray-500 mt-0.5">Daftar seluruh invoice (SPP, Wakaf, Fasilitas, Buku/Kitab & Iuran Kondisional)</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <!-- Tombol Buka Modal Buat Tagihan Baru -->
            <button type="button" onclick="openInvoiceModal()" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-sm transition flex items-center">
                <i class="fa-solid fa-plus-circle mr-1.5 text-sm"></i> Terbitkan Tagihan Baru
            </button>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
        <form action="{{ route('admin.invoices') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 items-end text-xs">
            <!-- Hidden sort fields to preserve order during filter -->
            <input type="hidden" name="sort_by" value="{{ $sortBy }}">
            <input type="hidden" name="sort_order" value="{{ $sortOrder }}">

            <div>
                <label class="block text-[11px] font-semibold text-gray-500 uppercase mb-1">Cari Tagihan / Santri</label>
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="No invoice, judul, nama..."
                           class="w-full text-xs rounded-xl border-gray-300 py-1.5 pl-8 pr-3 focus:ring-brand-800 bg-gray-50">
                    <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-2 text-gray-400 text-xs"></i>
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-gray-500 uppercase mb-1">Marhalah</label>
                <select name="kelompok" class="w-full rounded-xl border-gray-300 py-1.5 px-3 bg-gray-50 text-xs">
                    <option value="">-- Putra &amp; Putri --</option>
                    <option value="putra" {{ request('kelompok') === 'putra' ? 'selected' : '' }}>Putra</option>
                    <option value="putri" {{ request('kelompok') === 'putri' ? 'selected' : '' }}>Putri</option>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-gray-500 uppercase mb-1">Kelas</label>
                <select name="kelas" class="w-full rounded-xl border-gray-300 py-1.5 px-3 bg-gray-50 text-xs">
                    <option value="">-- Semua Kelas --</option>
                    @foreach($classes as $kelas)
                        <option value="{{ $kelas }}" {{ request('kelas') === $kelas ? 'selected' : '' }}>Kelas {{ $kelas }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-gray-500 uppercase mb-1">Status Pembayaran</label>
                <select name="status" class="w-full rounded-xl border-gray-300 py-1.5 px-3 bg-gray-50 text-xs">
                    <option value="">-- Semua Status --</option>
                    <option value="unpaid" {{ request('status') === 'unpaid' ? 'selected' : '' }}>Belum Bayar (Unpaid)</option>
                    <option value="partial" {{ request('status') === 'partial' ? 'selected' : '' }}>Dicicil (Partial)</option>
                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Lunas (Paid)</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-gray-500 uppercase mb-1">Pos Kategori</label>
                <select name="category_id" class="w-full rounded-xl border-gray-300 py-1.5 px-3 bg-gray-50 text-xs">
                    <option value="">-- Semua Kategori --</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center space-x-2">
                <button type="submit" class="py-1.5 px-4 bg-brand-900 hover:bg-brand-800 text-white rounded-xl text-xs font-semibold shadow-sm transition">
                    <i class="fa-solid fa-filter mr-1"></i> Terapkan Filter
                </button>
                <a href="{{ route('admin.invoices') }}" class="py-1.5 px-3 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl text-xs font-semibold transition" title="Reset">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Helper Sort URL (closure agar tidak bentrok saat view dirender berulang) -->
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

    <!-- Tabel Invoices dengan Sorting Tiap Kolom -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs sm:text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 uppercase text-[11px] font-semibold border-b border-gray-200 select-none">
                        <!-- Kolom Tanggal Paling Kiri -->
                        <th class="py-3 px-4 whitespace-nowrap">
                            <a href="{{ $sortUrl('created_at', $sortBy, $sortOrder) }}" class="flex items-center hover:text-brand-900 transition" title="Urutkan Tanggal">
                                <span>Tanggal</span>
                                {!! $sortIcon('created_at', $sortBy, $sortOrder) !!}
                            </a>
                        </th>

                        <!-- No. Tagihan -->
                        <th class="py-3 px-4 whitespace-nowrap">
                            <a href="{{ $sortUrl('invoice_number', $sortBy, $sortOrder) }}" class="flex items-center hover:text-brand-900 transition" title="Urutkan No. Tagihan">
                                <span>No. Tagihan</span>
                                {!! $sortIcon('invoice_number', $sortBy, $sortOrder) !!}
                            </a>
                        </th>

                        <!-- Nama Santri / Kelas -->
                        <th class="py-3 px-4 whitespace-nowrap">
                            <a href="{{ $sortUrl('student_name', $sortBy, $sortOrder) }}" class="flex items-center hover:text-brand-900 transition" title="Urutkan Nama Santri">
                                <span>Nama Santri / Kelas</span>
                                {!! $sortIcon('student_name', $sortBy, $sortOrder) !!}
                            </a>
                        </th>

                        <!-- Uraian Pos Tagihan -->
                        <th class="py-3 px-4 whitespace-nowrap">
                            <a href="{{ $sortUrl('title', $sortBy, $sortOrder) }}" class="flex items-center hover:text-brand-900 transition" title="Urutkan Uraian">
                                <span>Uraian Pos Tagihan</span>
                                {!! $sortIcon('title', $sortBy, $sortOrder) !!}
                            </a>
                        </th>

                        <!-- Total Tagihan -->
                        <th class="py-3 px-4 whitespace-nowrap text-right">
                            <a href="{{ $sortUrl('amount', $sortBy, $sortOrder) }}" class="inline-flex items-center hover:text-brand-900 transition" title="Urutkan Total">
                                <span>Total (Rp)</span>
                                {!! $sortIcon('amount', $sortBy, $sortOrder) !!}
                            </a>
                        </th>

                        <!-- Sisa Tagihan -->
                        <th class="py-3 px-4 whitespace-nowrap text-right">
                            <a href="{{ $sortUrl('remaining_amount', $sortBy, $sortOrder) }}" class="inline-flex items-center hover:text-brand-900 transition" title="Urutkan Sisa Tagihan">
                                <span>Sisa Tagihan</span>
                                {!! $sortIcon('remaining_amount', $sortBy, $sortOrder) !!}
                            </a>
                        </th>

                        <!-- Jatuh Tempo -->
                        <th class="py-3 px-4 whitespace-nowrap">
                            <a href="{{ $sortUrl('due_date', $sortBy, $sortOrder) }}" class="flex items-center hover:text-brand-900 transition" title="Urutkan Jatuh Tempo">
                                <span>Jatuh Tempo</span>
                                {!! $sortIcon('due_date', $sortBy, $sortOrder) !!}
                            </a>
                        </th>

                        <!-- Status -->
                        <th class="py-3 px-4 text-center whitespace-nowrap">
                            <a href="{{ $sortUrl('status', $sortBy, $sortOrder) }}" class="inline-flex items-center hover:text-brand-900 transition" title="Urutkan Status">
                                <span>Status</span>
                                {!! $sortIcon('status', $sortBy, $sortOrder) !!}
                            </a>
                        </th>

                        <th class="py-3 px-4 text-center whitespace-nowrap">Aksi Kasir</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($invoices as $inv)
                        <tr class="hover:bg-gray-50/50 transition">
                            <!-- Kolom Tanggal Paling Kiri -->
                            <td class="py-3.5 px-4 whitespace-nowrap text-gray-500 font-mono text-xs">
                                {{ $inv->created_at ? $inv->created_at->format('d/m/Y') : '-' }}
                            </td>

                            <!-- No. Tagihan -->
                            <td class="py-3.5 px-4 font-mono font-medium text-brand-900 text-xs whitespace-nowrap">
                                {{ $inv->invoice_number }}
                            </td>

                            <!-- Nama Santri & Kelas -->
                            <td class="py-3.5 px-4">
                                <p class="font-bold text-gray-900 flex items-center gap-1.5">
                                    {{ $inv->student->nama_lengkap ?? '-' }}
                                    @if($inv->student)
                                        <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold {{ $inv->student->kelompok_badge }}">{{ $inv->student->kelompok }}</span>
                                    @endif
                                </p>
                                <p class="text-[11px] text-gray-400">NIS: {{ $inv->student->nis ?? '-' }} • Kelas {{ $inv->student->kelas ?? '-' }}</p>
                            </td>

                            <!-- Uraian Pos Tagihan -->
                            <td class="py-3.5 px-4">
                                <span class="inline-block px-2 py-0.5 rounded text-[10px] font-semibold bg-gray-100 text-gray-700 mb-0.5">
                                    {{ $inv->category->name ?? 'Tagihan' }}
                                </span>
                                <p class="font-medium text-gray-800 text-xs">{{ $inv->title }}</p>
                            </td>

                            <!-- Total -->
                            <td class="py-3.5 px-4 text-right font-medium text-gray-700 whitespace-nowrap">
                                Rp {{ number_format($inv->amount, 0, ',', '.') }}
                            </td>

                            <!-- Sisa Tagihan -->
                            <td class="py-3.5 px-4 text-right font-bold whitespace-nowrap {{ $inv->remaining_amount > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                Rp {{ number_format($inv->remaining_amount, 0, ',', '.') }}
                            </td>

                            <!-- Jatuh Tempo -->
                            <td class="py-3.5 px-4 text-gray-500 text-xs whitespace-nowrap">
                                {{ $inv->due_date ? $inv->due_date->format('d/m/Y') : '-' }}
                            </td>

                            <!-- Status -->
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                @if($inv->status === 'paid')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-200">LUNAS</span>
                                @elseif($inv->status === 'partial')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-200">DICICIL</span>
                                @elseif($inv->status === 'cancelled')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-gray-200 text-gray-700 border border-gray-300">DIBATALKAN</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-rose-100 text-rose-800 border border-rose-200">BELUM BAYAR</span>
                                @endif
                            </td>

                            <!-- Aksi Kasir -->
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                @if($inv->status === 'cancelled')
                                    <span class="text-xs text-gray-400 font-semibold"><i class="fa-solid fa-ban mr-1"></i> Dibatalkan</span>
                                @else
                                    <div class="inline-flex items-center space-x-1.5">
                                        @if($inv->status !== 'paid')
                                            <a href="{{ route('admin.cashier', ['student_id' => $inv->student_id]) }}"
                                               class="inline-flex items-center px-2.5 py-1 bg-brand-900 hover:bg-brand-800 text-white rounded-lg text-[11px] font-semibold shadow-sm transition">
                                                <i class="fa-solid fa-cash-register mr-1 text-gold-400"></i> Bayar
                                            </a>
                                        @endif

                                        <button type="button"
                                                data-invoice="{{ json_encode([
                                                    'id' => $inv->id,
                                                    'number' => $inv->invoice_number,
                                                    'title' => $inv->title,
                                                    'amount' => (float) $inv->amount,
                                                    'due_date' => $inv->due_date?->format('Y-m-d'),
                                                    'notes' => $inv->notes,
                                                    'paid' => (float) $inv->paid_amount,
                                                    'edit_count' => (int) $inv->edit_count,
                                                    'student' => $inv->student->nama_lengkap ?? '-',
                                                    'nis' => $inv->student->nis ?? '-',
                                                ]) }}"
                                                onclick="openEditInvoice(this)"
                                                class="inline-flex items-center px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-800 rounded-lg text-[11px] font-semibold border border-amber-200 transition" title="Ubah tagihan">
                                            <i class="fa-solid fa-pen-to-square mr-1"></i> Ubah
                                        </button>

                                        @if($inv->paid_amount <= 0)
                                            <button type="button"
                                                    data-invoice="{{ json_encode([
                                                        'id' => $inv->id,
                                                        'number' => $inv->invoice_number,
                                                        'title' => $inv->title,
                                                        'student' => $inv->student->nama_lengkap ?? '-',
                                                        'nis' => $inv->student->nis ?? '-',
                                                        'amount' => (float) $inv->amount,
                                                    ]) }}"
                                                    onclick="openCancelInvoice(this)"
                                                    class="inline-flex items-center px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-lg text-[11px] font-semibold border border-rose-200 transition" title="Batalkan tagihan">
                                                <i class="fa-solid fa-ban mr-1"></i>
                                            </button>
                                            <form action="{{ route('admin.invoices.destroy', $inv) }}" method="POST" class="inline"
                                                  onsubmit="return confirm('Hapus tagihan {{ $inv->invoice_number }} secara permanen? Tindakan ini tercatat di Log Aktivitas.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex items-center px-2 py-1 bg-gray-50 hover:bg-gray-200 text-gray-600 rounded-lg text-[11px] font-semibold border border-gray-200 transition" title="Hapus tagihan">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="p-8 text-center text-gray-400 text-xs">Tidak ada data tagihan yang sesuai dengan filter atau kata kunci.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($invoices->hasPages())
            <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                {{ $invoices->links() }}
            </div>
        @endif
    </div>

</div>

<!-- Modal Form Terbitkan Tagihan Baru (Uang Kitab & Iuran Kondisional) -->
<div id="invoiceModal" class="fixed inset-0 z-50 hidden bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden border border-gray-100 animate-in fade-in zoom-in-95 duration-200">
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-brand-900 to-brand-800 px-6 py-5 text-white flex items-center justify-between">
            <div class="flex items-center space-x-2.5">
                <div class="w-9 h-9 rounded-xl bg-gold-400 text-brand-900 font-bold flex items-center justify-center text-sm shadow">
                    <i class="fa-solid fa-file-invoice"></i>
                </div>
                <div>
                    <h3 class="font-bold text-base">Terbitkan Tagihan Baru</h3>
                    <p class="text-xs text-blue-200">Uang Kitab, Iuran Rihlah, Wisuda, atau Tagihan Lainnya</p>
                </div>
            </div>
            <button type="button" onclick="closeInvoiceModal()" class="text-gray-300 hover:text-white text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Modal Form -->
        <form action="{{ route('admin.invoices.store') }}" method="POST" class="p-6 space-y-4 text-xs">
            @csrf

            <!-- 1. Kategori Tagihan -->
            <div>
                <label class="block font-bold text-gray-700 uppercase mb-1">Pos Kategori Pembayaran <span class="text-rose-500">*</span></label>
                <select name="payment_category_id" id="modalCategory" required onchange="applyCategoryDefault(this)"
                        class="w-full text-xs rounded-xl border-gray-300 py-2.5 px-3 focus:ring-brand-800 bg-white">
                    <option value="">-- Pilih Pos Kategori --</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" data-amount="{{ (int)$cat->default_amount }}" data-name="{{ $cat->name }}">
                            {{ $cat->name }} (Rp {{ number_format($cat->default_amount, 0, ',', '.') }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- 2. Target Santri -->
            <div>
                <label class="block font-bold text-gray-700 uppercase mb-1">Target Santri Penerima Tagihan <span class="text-rose-500">*</span></label>
                <div class="grid grid-cols-2 gap-2 mb-2">
                    <label class="flex items-center p-2 rounded-xl border border-gray-200 cursor-pointer hover:bg-gray-50">
                        <input type="radio" name="target_type" value="all" checked onchange="toggleTargetFields(this.value)" class="text-brand-900 focus:ring-brand-800">
                        <span class="ml-1.5 font-semibold text-gray-800">Semua Santri</span>
                    </label>
                    <label class="flex items-center p-2 rounded-xl border border-gray-200 cursor-pointer hover:bg-gray-50">
                        <input type="radio" name="target_type" value="class" onchange="toggleTargetFields(this.value)" class="text-brand-900 focus:ring-brand-800">
                        <span class="ml-1.5 font-semibold text-gray-800">Per Kelas</span>
                    </label>
                    <label class="flex items-center p-2 rounded-xl border border-gray-200 cursor-pointer hover:bg-gray-50">
                        <input type="radio" name="target_type" value="putra" onchange="toggleTargetFields(this.value)" class="text-brand-900 focus:ring-brand-800">
                        <span class="ml-1.5 font-semibold text-gray-800">Marhalah Putra</span>
                    </label>
                    <label class="flex items-center p-2 rounded-xl border border-gray-200 cursor-pointer hover:bg-gray-50">
                        <input type="radio" name="target_type" value="putri" onchange="toggleTargetFields(this.value)" class="text-brand-900 focus:ring-brand-800">
                        <span class="ml-1.5 font-semibold text-gray-800">Marhalah Putri</span>
                    </label>
                </div>

                <!-- Pilihan Kelas (Jika target_type = class) -->
                <div id="targetClassDiv" class="hidden mb-2">
                    <select name="target_class" class="w-full text-xs rounded-xl border-gray-300 py-2 px-3 bg-gray-50">
                        <option value="">-- Pilih Kelas --</option>
                        @foreach($classes as $c)
                            <option value="{{ $c }}">Kelas {{ $c }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- 3. Judul / Uraian Tagihan -->
            <div>
                <label class="block font-bold text-gray-700 uppercase mb-1">Judul / Uraian Tagihan <span class="text-rose-500">*</span></label>
                <input type="text" name="title" id="modalTitle" required placeholder="Contoh: Pengadaan Kitab Kuning Semester Genap"
                       class="w-full text-xs rounded-xl border-gray-300 py-2.5 px-3 focus:ring-brand-800">
            </div>

            <!-- 4. Nominal Tagihan & Jatuh Tempo -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-gray-700 uppercase mb-1">Nominal Biaya (Rp) <span class="text-rose-500">*</span></label>
                    <input type="number" name="amount" id="modalAmount" required min="1000" step="1000" placeholder="450000"
                           class="w-full text-xs font-bold text-gray-900 rounded-xl border-gray-300 py-2.5 px-3 focus:ring-brand-800">
                </div>
                <div>
                    <label class="block font-bold text-gray-700 uppercase mb-1">Batas Waktu / Jatuh Tempo <span class="text-rose-500">*</span></label>
                    <input type="date" name="due_date" required value="{{ now()->addMonth()->format('Y-m-d') }}"
                           class="w-full text-xs rounded-xl border-gray-300 py-2.5 px-3 focus:ring-brand-800">
                </div>
            </div>

            <!-- 5. Catatan Tambahan -->
            <div>
                <label class="block font-bold text-gray-700 uppercase mb-1">Catatan Tambahan (Opsional)</label>
                <input type="text" name="notes" placeholder="Contoh: Paket 5 kitab kurikulum Muadalah"
                       class="w-full text-xs rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800">
            </div>

            <!-- Notice Otomatisasi -->
            <div class="p-3 bg-blue-50 rounded-xl border border-blue-100 flex items-start space-x-2 text-[11px] text-blue-800">
                <i class="fa-solid fa-circle-check text-blue-600 mt-0.5"></i>
                <p>Setelah form disimpan, data langsung tersimpan di database resmi dan tagihan otomatis muncul di <strong>Portal Wali Santri</strong> serta di menu <strong>Kasir</strong>.</p>
            </div>

            <!-- Modal Action Buttons -->
            <div class="flex items-center justify-end space-x-2 pt-2">
                <button type="button" onclick="closeInvoiceModal()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-semibold transition">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 bg-brand-900 hover:bg-brand-800 text-white rounded-xl font-bold shadow-md transition flex items-center">
                    <i class="fa-solid fa-paper-plane mr-1.5 text-xs"></i> Simpan & Terbitkan Tagihan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openInvoiceModal() {
        document.getElementById('invoiceModal').classList.remove('hidden');
    }

    // Dibuka dari menu "Buat Tagihan Baru" (?tambah=1): modal langsung tampil.
    @if(request()->boolean('tambah'))
        document.addEventListener('DOMContentLoaded', openInvoiceModal);
    @endif

    function closeInvoiceModal() {
        document.getElementById('invoiceModal').classList.add('hidden');
    }

    function toggleTargetFields(targetType) {
        document.getElementById('targetClassDiv').classList.toggle('hidden', targetType !== 'class');
    }

    function applyCategoryDefault(select) {
        const option = select.options[select.selectedIndex];
        const defaultAmount = option.getAttribute('data-amount');
        const catName = option.getAttribute('data-name');

        if (defaultAmount && defaultAmount > 0) {
            document.getElementById('modalAmount').value = defaultAmount;
        }
        if (catName) {
            document.getElementById('modalTitle').value = catName;
        }
    }

    // Modal Ubah Tagihan
    function openEditInvoice(button) {
        const data = JSON.parse(button.dataset.invoice);
        document.getElementById('editInvoiceForm').action = "{{ url('admin/tagihan') }}/" + data.id;
        document.getElementById('editInvoiceNumber').textContent = data.number || '-';
        document.getElementById('editInvoiceStudent').textContent = data.student || '-';
        document.getElementById('editInvoiceNis').textContent = data.nis || '-';
        document.getElementById('editInvoiceTitle').value = data.title || '';
        document.getElementById('editInvoiceAmount').value = data.amount;
        document.getElementById('editInvoiceDueDate').value = data.due_date || '';
        document.getElementById('editInvoiceNotes').value = data.notes || '';

        const amountInput = document.getElementById('editInvoiceAmount');
        const amountInfo = document.getElementById('editInvoiceAmountInfo');
        const syncAmountInfo = () => {
            amountInfo.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(amountInput.value || 0);
        };
        amountInput.oninput = syncAmountInfo;
        syncAmountInfo();

        document.getElementById('editInvoicePaidInfo').textContent = data.paid > 0
            ? 'Sudah dibayar: Rp ' + new Intl.NumberFormat('id-ID').format(data.paid)
            : 'Belum ada pembayaran.';

        // Kuota perubahan hanya ditampilkan setelah tombol Ubah diklik.
        const editCount = data.edit_count || 0;
        const sisa = Math.max(0, 2 - editCount);
        const quotaBox = document.getElementById('editInvoiceQuota');
        const quotaText = document.getElementById('editInvoiceQuotaText');
        const submit = document.getElementById('editInvoiceSubmit');

        if (sisa === 0) {
            quotaBox.className = 'p-3 bg-rose-50 border border-rose-200 rounded-xl text-[11px] text-rose-900';
            quotaText.textContent = 'Tagihan ini sudah diubah 2 kali. Data tagihan tidak dapat diubah lagi; gunakan pembatalan lalu terbitkan tagihan pengganti bila perlu.';
            submit.disabled = true;
            submit.classList.add('disabled:bg-gray-300', 'disabled:cursor-not-allowed');
        } else {
            quotaBox.className = 'p-3 bg-blue-50 border border-blue-100 rounded-xl text-[11px] text-blue-900';
            quotaText.textContent = 'Sisa kesempatan ubah data: ' + sisa + ' kali';
            submit.disabled = false;
        }

        document.getElementById('editInvoiceModal').classList.remove('hidden');
    }

    // Modal Batalkan Tagihan
    function openCancelInvoice(button) {
        const data = JSON.parse(button.dataset.invoice);
        document.getElementById('cancelInvoiceForm').action = "{{ url('admin/tagihan') }}/" + data.id + "/batal";
        document.getElementById('cancelInvoiceNumber').textContent = data.number || '-';
        document.getElementById('cancelInvoiceSummary').textContent = data.title || '-';
        document.getElementById('cancelInvoiceStudent').textContent = data.student || '-';
        document.getElementById('cancelInvoiceNis').textContent = data.nis || '-';
        document.getElementById('cancelInvoiceAmount').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(data.amount || 0);
        document.getElementById('cancelInvoiceModal').classList.remove('hidden');
    }
</script>

<!-- Modal Ubah Tagihan -->
<div id="editInvoiceModal" class="fixed inset-0 z-50 hidden bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden border border-gray-100">
        <div class="bg-gradient-to-r from-brand-900 to-brand-800 px-6 py-5 text-white flex items-center justify-between">
            <div>
                <h3 class="font-bold text-base">Ubah Data Tagihan</h3>
                <p class="text-xs text-blue-200" id="editInvoiceNumber">-</p>
            </div>
            <button type="button" onclick="document.getElementById('editInvoiceModal').classList.add('hidden')" class="text-gray-300 hover:text-white text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="editInvoiceForm" method="POST" class="p-6 space-y-4 text-xs">
            @csrf
            @method('PATCH')

            <div class="p-3 bg-amber-50 border border-amber-100 rounded-xl text-[11px] text-amber-900">
                <div class="grid grid-cols-2 gap-2 mb-2">
                    <div>
                        <p class="text-[10px] font-bold uppercase text-amber-500">Nama Santri</p>
                        <p class="font-bold text-amber-900" id="editInvoiceStudent">-</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase text-amber-500">NIS</p>
                        <p class="font-semibold text-amber-900" id="editInvoiceNis">-</p>
                    </div>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase text-amber-500">Nominal Tagihan</p>
                    <p class="font-bold text-amber-900" id="editInvoiceAmountInfo">-</p>
                </div>
            </div>

            <div>
                <label class="block font-bold text-gray-700 uppercase mb-1">Uraian Tagihan <span class="text-rose-500">*</span></label>
                <input type="text" name="title" id="editInvoiceTitle" required maxlength="150"
                       class="w-full text-xs rounded-xl border-gray-300 py-2.5 px-3 focus:ring-brand-800">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-gray-700 uppercase mb-1">Nominal Tagihan (Rp) <span class="text-rose-500">*</span></label>
                    <input type="number" name="amount" id="editInvoiceAmount" required min="1000" step="1000"
                           class="w-full text-xs font-bold rounded-xl border-gray-300 py-2.5 px-3 focus:ring-brand-800">
                    <p class="text-[11px] text-gray-500 mt-1" id="editInvoicePaidInfo">-</p>
                </div>
                <div>
                    <label class="block font-bold text-gray-700 uppercase mb-1">Jatuh Tempo <span class="text-rose-500">*</span></label>
                    <input type="date" name="due_date" id="editInvoiceDueDate" required
                           class="w-full text-xs rounded-xl border-gray-300 py-2.5 px-3 focus:ring-brand-800">
                </div>
            </div>

            <div>
                <label class="block font-bold text-gray-700 uppercase mb-1">Catatan</label>
                <input type="text" name="notes" id="editInvoiceNotes" maxlength="255"
                       class="w-full text-xs rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800">
            </div>

            <div class="p-3 bg-amber-50 border border-amber-100 rounded-xl text-[11px] text-amber-900">
                <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                Nominal tidak boleh lebih kecil dari total yang sudah dibayar. Perubahan tercatat di Log Aktivitas.
            </div>

            <div class="p-3 bg-blue-50 border border-blue-100 rounded-xl text-[11px] text-blue-900" id="editInvoiceQuota">
                <i class="fa-solid fa-circle-info mr-1"></i>
                <span id="editInvoiceQuotaText">Sisa kesempatan ubah data: 2 kali</span>
            </div>

            <div class="flex items-center justify-end space-x-2 pt-2">
                <button type="button" onclick="document.getElementById('editInvoiceModal').classList.add('hidden')" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-semibold transition">Batal</button>
                <button type="submit" id="editInvoiceSubmit" class="px-5 py-2 bg-brand-900 hover:bg-brand-800 text-white rounded-xl font-bold shadow-md transition disabled:bg-gray-300 disabled:cursor-not-allowed">
                    <i class="fa-solid fa-floppy-disk mr-1.5"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Batalkan Tagihan -->
<div id="cancelInvoiceModal" class="fixed inset-0 z-50 hidden bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full overflow-hidden border border-gray-100">
        <div class="bg-gradient-to-r from-rose-800 to-rose-700 px-6 py-5 text-white flex items-center justify-between">
            <div>
                <h3 class="font-bold text-base">Batalkan Tagihan</h3>
                <p class="text-xs text-rose-100" id="cancelInvoiceNumber">-</p>
            </div>
            <button type="button" onclick="document.getElementById('cancelInvoiceModal').classList.add('hidden')" class="text-gray-200 hover:text-white text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="cancelInvoiceForm" method="POST" class="p-6 space-y-4 text-xs">
            @csrf
            @method('PATCH')

            <div class="p-3 bg-rose-50 border border-rose-100 rounded-xl text-[11px] text-rose-900">
                <div class="grid grid-cols-2 gap-2 mb-2">
                    <div>
                        <p class="text-[10px] font-bold uppercase text-rose-400">Nama Santri</p>
                        <p class="font-bold text-rose-900" id="cancelInvoiceStudent">-</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase text-rose-400">NIS</p>
                        <p class="font-semibold text-rose-900" id="cancelInvoiceNis">-</p>
                    </div>
                </div>
                <div class="mb-2">
                    <p class="text-[10px] font-bold uppercase text-rose-400">Nominal Tagihan</p>
                    <p class="font-bold text-rose-900" id="cancelInvoiceAmount">-</p>
                </div>
                <div class="pt-2 border-t border-rose-200">
                    <p class="font-semibold" id="cancelInvoiceSummary">-</p>
                    <p class="mt-1">Tagihan berstatus <strong>DIBATALKAN</strong> tidak akan dihitung sebagai tunggakan dan tidak muncul di portal wali.</p>
                </div>
            </div>

            <div>
                <label class="block font-bold text-gray-700 uppercase mb-1">Alasan Pembatalan <span class="text-rose-500">*</span></label>
                <textarea name="cancel_reason" required rows="3" maxlength="255" placeholder="Contoh: Santri batal mondok / tagihan salah terbit"
                          class="w-full text-xs rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800"></textarea>
            </div>

            <div class="flex items-center justify-end space-x-2 pt-2">
                <button type="button" onclick="document.getElementById('cancelInvoiceModal').classList.add('hidden')" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-semibold transition">Tutup</button>
                <button type="submit" class="px-5 py-2 bg-rose-700 hover:bg-rose-800 text-white rounded-xl font-bold shadow-md transition">
                    <i class="fa-solid fa-ban mr-1.5"></i> Batalkan Tagihan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
