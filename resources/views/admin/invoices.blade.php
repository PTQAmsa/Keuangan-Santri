@extends('layouts.app')

@section('title', 'Rekapitulasi Tagihan Santri - PTQ Amsa001')

@section('content')
<div class="space-y-6">

    <!-- Header & Action Buttons -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-gray-200">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Rekapitulasi Semua Tagihan Santri</h1>
            <p class="text-xs sm:text-sm text-gray-500 mt-0.5">Daftar seluruh invoice (SPP, Wakaf, Fasilitas, Buku/Kitab & Iuran Kondisional/Rihlah)</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <!-- Tombol Buka Modal Buat Tagihan Baru -->
            <button type="button" onclick="openInvoiceModal()" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-sm transition flex items-center">
                <i class="fa-solid fa-plus-circle mr-1.5 text-sm"></i> Terbitkan Tagihan Baru (Kitab / Iuran)
            </button>
            <a href="{{ route('admin.cashier') }}" class="px-3.5 py-2 bg-brand-900 hover:bg-brand-800 text-white rounded-xl text-xs font-semibold shadow-sm transition">
                <i class="fa-solid fa-cash-register mr-1.5 text-gold-400"></i> Meja Kasir
            </a>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
        <form action="{{ route('admin.invoices') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end text-xs">
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
                <label class="block text-[11px] font-semibold text-gray-500 uppercase mb-1">Status Pembayaran</label>
                <select name="status" class="w-full rounded-xl border-gray-300 py-1.5 px-3 bg-gray-50 text-xs">
                    <option value="">-- Semua Status --</option>
                    <option value="unpaid" {{ request('status') === 'unpaid' ? 'selected' : '' }}>Belum Bayar (Unpaid)</option>
                    <option value="partial" {{ request('status') === 'partial' ? 'selected' : '' }}>Dicicil (Partial)</option>
                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Lunas (Paid)</option>
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

            <div class="flex items-center space-x-2 sm:col-span-2 lg:col-span-2">
                <button type="submit" class="py-1.5 px-4 bg-brand-900 hover:bg-brand-800 text-white rounded-xl text-xs font-semibold shadow-sm transition">
                    <i class="fa-solid fa-filter mr-1"></i> Terapkan Filter
                </button>
                <a href="{{ route('admin.invoices') }}" class="py-1.5 px-3 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl text-xs font-semibold transition" title="Reset">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Helper Sort URL Macro -->
    @php
        function sortUrl($column, $currentSort, $currentOrder) {
            $order = ($currentSort === $column && $currentOrder === 'asc') ? 'desc' : 'asc';
            return request()->fullUrlWithQuery(['sort_by' => $column, 'sort_order' => $order]);
        }

        function sortIcon($column, $currentSort, $currentOrder) {
            if ($currentSort !== $column) {
                return '<i class="fa-solid fa-sort text-gray-300 ml-1"></i>';
            }
            return $currentOrder === 'asc' 
                ? '<i class="fa-solid fa-arrow-up-short-wide text-brand-900 ml-1"></i>' 
                : '<i class="fa-solid fa-arrow-down-wide-short text-brand-900 ml-1"></i>';
        }
    @endphp

    <!-- Tabel Invoices dengan Sorting Tiap Kolom -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs sm:text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 uppercase text-[11px] font-semibold border-b border-gray-200 select-none">
                        <!-- Kolom Tanggal Paling Kiri -->
                        <th class="py-3 px-4 whitespace-nowrap">
                            <a href="{{ sortUrl('created_at', $sortBy, $sortOrder) }}" class="flex items-center hover:text-brand-900 transition" title="Urutkan Tanggal">
                                <span>Tanggal</span>
                                {!! sortIcon('created_at', $sortBy, $sortOrder) !!}
                            </a>
                        </th>

                        <!-- No. Tagihan -->
                        <th class="py-3 px-4 whitespace-nowrap">
                            <a href="{{ sortUrl('invoice_number', $sortBy, $sortOrder) }}" class="flex items-center hover:text-brand-900 transition" title="Urutkan No. Tagihan">
                                <span>No. Tagihan</span>
                                {!! sortIcon('invoice_number', $sortBy, $sortOrder) !!}
                            </a>
                        </th>

                        <!-- Nama Santri / Kelas -->
                        <th class="py-3 px-4 whitespace-nowrap">
                            <a href="{{ sortUrl('student_name', $sortBy, $sortOrder) }}" class="flex items-center hover:text-brand-900 transition" title="Urutkan Nama Santri">
                                <span>Nama Santri / Kelas</span>
                                {!! sortIcon('student_name', $sortBy, $sortOrder) !!}
                            </a>
                        </th>

                        <!-- Uraian Pos Tagihan -->
                        <th class="py-3 px-4 whitespace-nowrap">
                            <a href="{{ sortUrl('title', $sortBy, $sortOrder) }}" class="flex items-center hover:text-brand-900 transition" title="Urutkan Uraian">
                                <span>Uraian Pos Tagihan</span>
                                {!! sortIcon('title', $sortBy, $sortOrder) !!}
                            </a>
                        </th>

                        <!-- Total Tagihan -->
                        <th class="py-3 px-4 whitespace-nowrap text-right">
                            <a href="{{ sortUrl('amount', $sortBy, $sortOrder) }}" class="inline-flex items-center hover:text-brand-900 transition" title="Urutkan Total">
                                <span>Total (Rp)</span>
                                {!! sortIcon('amount', $sortBy, $sortOrder) !!}
                            </a>
                        </th>

                        <!-- Sisa Tagihan -->
                        <th class="py-3 px-4 whitespace-nowrap text-right">
                            <a href="{{ sortUrl('remaining_amount', $sortBy, $sortOrder) }}" class="inline-flex items-center hover:text-brand-900 transition" title="Urutkan Sisa Tagihan">
                                <span>Sisa Tagihan</span>
                                {!! sortIcon('remaining_amount', $sortBy, $sortOrder) !!}
                            </a>
                        </th>

                        <!-- Jatuh Tempo -->
                        <th class="py-3 px-4 whitespace-nowrap">
                            <a href="{{ sortUrl('due_date', $sortBy, $sortOrder) }}" class="flex items-center hover:text-brand-900 transition" title="Urutkan Jatuh Tempo">
                                <span>Jatuh Tempo</span>
                                {!! sortIcon('due_date', $sortBy, $sortOrder) !!}
                            </a>
                        </th>

                        <!-- Status -->
                        <th class="py-3 px-4 text-center whitespace-nowrap">
                            <a href="{{ sortUrl('status', $sortBy, $sortOrder) }}" class="inline-flex items-center hover:text-brand-900 transition" title="Urutkan Status">
                                <span>Status</span>
                                {!! sortIcon('status', $sortBy, $sortOrder) !!}
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
                                <p class="font-bold text-gray-900">{{ $inv->student->nama_lengkap ?? '-' }}</p>
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
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-rose-100 text-rose-800 border border-rose-200">BELUM BAYAR</span>
                                @endif
                            </td>

                            <!-- Aksi Kasir -->
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                @if($inv->status !== 'paid')
                                    <a href="{{ route('admin.cashier', ['student_id' => $inv->student_id]) }}"
                                       class="inline-flex items-center px-3 py-1 bg-brand-900 hover:bg-brand-800 text-white rounded-lg text-xs font-semibold shadow-sm transition">
                                        <i class="fa-solid fa-cash-register mr-1 text-gold-400"></i> Bayar
                                    </a>
                                @else
                                    <span class="text-xs text-emerald-600 font-semibold"><i class="fa-solid fa-circle-check mr-1"></i> Lunas</span>
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
                            {{ $cat->name }} (Standar: Rp {{ number_format($cat->default_amount, 0, ',', '.') }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- 2. Target Santri -->
            <div>
                <label class="block font-bold text-gray-700 uppercase mb-1">Target Santri Penerima Tagihan <span class="text-rose-500">*</span></label>
                <div class="grid grid-cols-3 gap-2 mb-2">
                    <label class="flex items-center p-2 rounded-xl border border-gray-200 cursor-pointer hover:bg-gray-50">
                        <input type="radio" name="target_type" value="all" checked onchange="toggleTargetFields(this.value)" class="text-brand-900 focus:ring-brand-800">
                        <span class="ml-1.5 font-semibold text-gray-800">Semua Santri</span>
                    </label>
                    <label class="flex items-center p-2 rounded-xl border border-gray-200 cursor-pointer hover:bg-gray-50">
                        <input type="radio" name="target_type" value="class" onchange="toggleTargetFields(this.value)" class="text-brand-900 focus:ring-brand-800">
                        <span class="ml-1.5 font-semibold text-gray-800">Per Kelas</span>
                    </label>
                    <label class="flex items-center p-2 rounded-xl border border-gray-200 cursor-pointer hover:bg-gray-50">
                        <input type="radio" name="target_type" value="single" onchange="toggleTargetFields(this.value)" class="text-brand-900 focus:ring-brand-800">
                        <span class="ml-1.5 font-semibold text-gray-800">1 Santri</span>
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

                <!-- Pilihan Santri Spesifik (Jika target_type = single) -->
                <div id="targetStudentDiv" class="hidden mb-2">
                    <select name="student_id" class="w-full text-xs rounded-xl border-gray-300 py-2 px-3 bg-gray-50">
                        <option value="">-- Pilih Santri --</option>
                        @foreach($students as $s)
                            <option value="{{ $s->id }}">{{ $s->nama_lengkap }} (Kelas {{ $s->kelas }})</option>
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
                <p>Setelah form disimpan, data langsung tersimpan di database resmi dan tagihan otomatis muncul di <strong>Portal Wali Santri</strong> serta di <strong>Meja Kasir</strong>.</p>
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

    function closeInvoiceModal() {
        document.getElementById('invoiceModal').classList.add('hidden');
    }

    function toggleTargetFields(targetType) {
        document.getElementById('targetClassDiv').classList.toggle('hidden', targetType !== 'class');
        document.getElementById('targetStudentDiv').classList.toggle('hidden', targetType !== 'single');
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
</script>
@endsection
