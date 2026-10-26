@extends('layouts.app')

@section('title', 'Koreksi & Pembatalan Transaksi - PTQ Amsa001')

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-gray-200">
        <div>
            <div class="flex items-center space-x-2 text-xs text-gray-500 mb-1">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-brand-900">Admin</a>
                <span>/</span>
                <span class="text-gray-800 font-semibold">Koreksi Transaksi</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900 flex items-center">
                <i class="fa-solid fa-receipt text-brand-800 mr-2.5"></i> Koreksi &amp; Pembatalan Transaksi
            </h1>
            <p class="text-xs sm:text-sm text-gray-500 mt-0.5">
                Perbaiki data pembayaran yang salah input atau batalkan kuitansi. Seluruh aksi tercatat di Log Aktivitas.
            </p>
        </div>
    </div>

    <!-- Ringkasan -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-sm">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Kuitansi Aktif</p>
            <p class="text-2xl font-extrabold text-emerald-700 mt-1">{{ number_format($jumlahAktif, 0, ',', '.') }} <span class="text-sm font-semibold text-gray-500">kuitansi</span></p>
            <p class="text-xs text-gray-400 mt-1">Jumlah kuitansi aktif yang tersedia di database</p>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-sm">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Dibatalkan</p>
            <p class="text-2xl font-extrabold text-rose-700 mt-1">{{ number_format($jumlahDibatalkan, 0, ',', '.') }} <span class="text-sm font-semibold text-gray-500">kuitansi</span></p>
            <p class="text-xs text-gray-400 mt-1">Jumlah kuitansi yang berstatus dibatalkan</p>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-sm">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Kuitansi Putra &amp; Putri</p>
            <div class="mt-2 space-y-1.5 text-xs">
                <div class="flex items-center justify-between rounded-lg bg-blue-50/70 border border-blue-100 px-2.5 py-1.5">
                    <span class="font-semibold text-blue-800">Putra</span>
                    <span class="text-gray-700"><strong>{{ $putraAktif }}</strong> aktif &bull; <strong class="text-rose-700">{{ $putraBatal }}</strong> batal</span>
                </div>
                <div class="flex items-center justify-between rounded-lg bg-fuchsia-50/70 border border-fuchsia-100 px-2.5 py-1.5">
                    <span class="font-semibold text-fuchsia-800">Putri</span>
                    <span class="text-gray-700"><strong>{{ $putriAktif }}</strong> aktif &bull; <strong class="text-rose-700">{{ $putriBatal }}</strong> batal</span>
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-1.5">Jumlah status kuitansi per marhalah</p>
        </div>
    </div>

    <!-- Filter -->
    <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
        <form action="{{ route('admin.transactions') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end text-xs">
            <input type="hidden" name="sort_by" value="{{ $sortBy }}">
            <input type="hidden" name="sort_order" value="{{ $sortOrder }}">

            <div class="sm:col-span-2">
                <label class="block text-[11px] font-semibold text-gray-500 uppercase mb-1">Cari Kuitansi / Santri / NIS / Diterima Dari</label>
                <div class="relative">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Contoh: KWT-202610-0001 atau nama santri..."
                           class="w-full text-xs rounded-xl border-gray-300 py-1.5 pl-8 pr-3 focus:ring-brand-800 bg-gray-50">
                    <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-2 text-gray-400 text-xs"></i>
                </div>
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-gray-500 uppercase mb-1">Marhalah</label>
                <select name="kelompok" class="w-full rounded-xl border-gray-300 py-1.5 px-3 bg-gray-50 text-xs">
                    <option value="">-- Putra &amp; Putri --</option>
                    <option value="putra" {{ $kelompok === 'putra' ? 'selected' : '' }}>Putra</option>
                    <option value="putri" {{ $kelompok === 'putri' ? 'selected' : '' }}>Putri</option>
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-gray-500 uppercase mb-1">Status Kuitansi</label>
                <div class="flex space-x-2">
                    <select name="status" class="w-full rounded-xl border-gray-300 py-1.5 px-3 bg-gray-50 text-xs">
                        <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="voided" {{ $status === 'voided' ? 'selected' : '' }}>Dibatalkan</option>
                        <option value="all" {{ $status === 'all' ? 'selected' : '' }}>Semua</option>
                    </select>
                    <button type="submit" class="py-1.5 px-4 bg-brand-900 hover:bg-brand-800 text-white rounded-xl text-xs font-semibold shadow-sm transition whitespace-nowrap">
                        <i class="fa-solid fa-filter mr-1"></i> Filter
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Helper sortir kolom -->
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

    <!-- Tabel Transaksi -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs sm:text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 uppercase text-[11px] font-semibold border-b border-gray-200 select-none">
                        <th class="py-3 px-4 whitespace-nowrap">
                            <a href="{{ $sortUrl('receipt_number', $sortBy, $sortOrder) }}" class="inline-flex items-center hover:text-brand-900 transition" title="Urutkan No. Kuitansi">
                                <span>No. Kuitansi</span>{!! $sortIcon('receipt_number', $sortBy, $sortOrder) !!}
                            </a>
                        </th>
                        <th class="py-3 px-4 whitespace-nowrap">
                            <a href="{{ $sortUrl('payment_date', $sortBy, $sortOrder) }}" class="inline-flex items-center hover:text-brand-900 transition" title="Urutkan Tanggal">
                                <span>Tanggal</span>{!! $sortIcon('payment_date', $sortBy, $sortOrder) !!}
                            </a>
                        </th>
                        <th class="py-3 px-4">
                            <a href="{{ $sortUrl('student_name', $sortBy, $sortOrder) }}" class="inline-flex items-center hover:text-brand-900 transition" title="Urutkan Nama Santri">
                                <span>Santri</span>{!! $sortIcon('student_name', $sortBy, $sortOrder) !!}
                            </a>
                        </th>
                        <th class="py-3 px-4 text-center whitespace-nowrap">
                            <a href="{{ $sortUrl('kelompok', $sortBy, $sortOrder) }}" class="inline-flex items-center justify-center hover:text-brand-900 transition" title="Urutkan Marhalah">
                                <span>Marhalah</span>{!! $sortIcon('kelompok', $sortBy, $sortOrder) !!}
                            </a>
                        </th>
                        <th class="py-3 px-4">
                            <a href="{{ $sortUrl('invoice_title', $sortBy, $sortOrder) }}" class="inline-flex items-center hover:text-brand-900 transition" title="Urutkan Uraian Tagihan">
                                <span>Uraian Tagihan</span>{!! $sortIcon('invoice_title', $sortBy, $sortOrder) !!}
                            </a>
                        </th>
                        <th class="py-3 px-4 text-right whitespace-nowrap">
                            <a href="{{ $sortUrl('amount_paid', $sortBy, $sortOrder) }}" class="inline-flex items-center justify-end hover:text-brand-900 transition" title="Urutkan Nominal">
                                <span>Nominal</span>{!! $sortIcon('amount_paid', $sortBy, $sortOrder) !!}
                            </a>
                        </th>
                        <th class="py-3 px-4 text-center whitespace-nowrap">
                            <a href="{{ $sortUrl('status', $sortBy, $sortOrder) }}" class="inline-flex items-center justify-center hover:text-brand-900 transition" title="Urutkan Status">
                                <span>Status</span>{!! $sortIcon('status', $sortBy, $sortOrder) !!}
                            </a>
                        </th>
                        <th class="py-3 px-4 text-center whitespace-nowrap">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($payments as $pay)
                        <tr class="hover:bg-gray-50/50 transition {{ $pay->isVoided() ? 'bg-rose-50/30' : '' }}">
                            <td class="py-3.5 px-4 font-mono font-bold text-brand-900 whitespace-nowrap">
                                {{ $pay->receipt->receipt_number ?? $pay->payment_code }}
                            </td>
                            <td class="py-3.5 px-4 text-gray-500 text-xs whitespace-nowrap">
                                {{ $pay->payment_date ? $pay->payment_date->format('d/m/Y') : '-' }}
                            </td>
                            <td class="py-3.5 px-4">
                                <p class="font-bold text-gray-900">{{ $pay->student->nama_lengkap ?? '-' }}</p>
                                <p class="text-[11px] text-gray-400">NIS {{ $pay->student->nis ?? '-' }} • {{ $pay->student->kelas ?? '-' }}</p>
                            </td>
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                @if($pay->student)
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold {{ $pay->student->kelompok_badge }}">{{ $pay->student->kelompok }}</span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                <p class="text-xs font-medium text-gray-700">{{ $pay->invoice?->receipt_title ?? '-' }}</p>
                                <p class="text-[11px] text-gray-400">Diterima dari: {{ $pay->received_from ?: '-' }}</p>
                            </td>
                            <td class="py-3.5 px-4 text-right font-bold whitespace-nowrap {{ $pay->isVoided() ? 'text-gray-400 line-through' : 'text-emerald-700' }}">
                                Rp {{ number_format($pay->amount_paid, 0, ',', '.') }}
                            </td>
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                @if($pay->isVoided())
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-100 text-rose-800 border border-rose-200">DIBATALKAN</span>
                                    <p class="text-[10px] text-gray-400 mt-1">{{ $pay->voided_at?->format('d/m/Y H:i') }}</p>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-200">AKTIF</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                @if($pay->isVoided())
                                    <span class="text-[11px] text-gray-400" title="{{ $pay->void_reason }}">—</span>
                                @else
                                    <div class="inline-flex items-center space-x-1.5">
                                        <button type="button"
                                                data-payment="{{ json_encode([
                                                    'id' => $pay->id,
                                                    'code' => $pay->receipt->receipt_number ?? $pay->payment_code,
                                                    'amount' => (float) $pay->amount_paid,
                                                    'method' => $pay->payment_method,
                                                    'date' => $pay->payment_date?->format('Y-m-d'),
                                                    'received_from' => $pay->received_from,
                                                    'notes' => $pay->notes,
                                                    'student' => $pay->student->nama_lengkap ?? '-',
                                                    'origin' => $pay->student->kota_asal ?? '-',
                                                    'kelompok' => $pay->student->kelompok ?? '-',
                                                    'invoice' => $pay->invoice?->receipt_title ?? '-',
                                                ]) }}"
                                                onclick="openEditPayment(this)"
                                                class="inline-flex items-center px-2.5 py-1 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-800 text-[11px] font-semibold border border-amber-200 transition">
                                            <i class="fa-solid fa-pen-to-square mr-1"></i> Ubah
                                        </button>                                            <button type="button"
                                                data-payment="{{ json_encode([
                                                    'id' => $pay->id,
                                                    'code' => $pay->receipt->receipt_number ?? $pay->payment_code,
                                                    'student' => $pay->student->nama_lengkap ?? '-',
                                                    'origin' => $pay->student->kota_asal ?? '-',
                                                    'kelompok' => $pay->student->kelompok ?? '-',
                                                    'invoice' => $pay->invoice?->receipt_title ?? '-',
                                                    'received_from' => $pay->received_from ?: '-',
                                                    'method' => $pay->payment_method === 'cash' ? 'Tunai' : 'Transfer BSI',
                                                    'amount' => number_format((float) $pay->amount_paid, 0, ',', '.'),
                                                ]) }}"
                                                onclick="openVoidPayment(this)"
                                                class="inline-flex items-center px-2.5 py-1 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 text-[11px] font-semibold border border-rose-200 transition">
                                            <i class="fa-solid fa-ban mr-1"></i> Batalkan
                                        </button>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-gray-400 text-xs">
                                Tidak ada transaksi yang sesuai dengan filter ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($payments->hasPages())
            <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                {{ $payments->links() }}
            </div>
        @endif
    </div>

</div>

<!-- Modal Ubah Pembayaran -->
<div id="editPaymentModal" class="fixed inset-0 z-50 hidden bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden border border-gray-100">
        <div class="bg-gradient-to-r from-brand-900 to-brand-800 px-6 py-5 text-white flex items-center justify-between">
            <div>
                <h3 class="font-bold text-base">Perbaiki Data Pembayaran</h3>
                <p class="text-xs text-blue-200" id="editPaymentCode">-</p>
            </div>
            <button type="button" onclick="document.getElementById('editPaymentModal').classList.add('hidden')" class="text-gray-300 hover:text-white text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="editPaymentForm" method="POST" class="p-6 space-y-4 text-xs">
            @csrf
            @method('PATCH')

            <!-- Konteks santri & tagihan agar koreksi tidak salah baris -->
            <div class="p-3 bg-gray-50 border border-gray-200 rounded-xl space-y-1.5">
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <p class="text-[10px] font-bold uppercase text-gray-400">Nama Santri</p>
                        <p class="font-bold text-gray-900" id="editStudent">-</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase text-gray-400">Asal</p>
                        <p class="font-semibold text-gray-800" id="editOrigin">-</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase text-gray-400">Marhalah</p>
                        <p class="font-semibold text-gray-800" id="editKelompok">-</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase text-gray-400">Uraian Tagihan</p>
                        <p class="font-semibold text-gray-800" id="editInvoice">-</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-gray-700 uppercase mb-1">Nominal Bayar (Rp) <span class="text-rose-500">*</span></label>
                    <input type="number" name="amount_paid" id="editAmount" required min="1" step="1000"
                           class="w-full text-xs font-bold rounded-xl border-gray-300 py-2.5 px-3 focus:ring-brand-800">
                </div>
                <div>
                    <label class="block font-bold text-gray-700 uppercase mb-1">Tanggal Bayar <span class="text-rose-500">*</span></label>
                    <input type="date" name="payment_date" id="editDate" required
                           class="w-full text-xs rounded-xl border-gray-300 py-2.5 px-3 focus:ring-brand-800">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-gray-700 uppercase mb-1">Metode Bayar <span class="text-rose-500">*</span></label>
                    <select name="payment_method" id="editMethod" required class="w-full text-xs rounded-xl border-gray-300 py-2.5 px-3 focus:ring-brand-800">
                        <option value="transfer">Transfer Bank</option>
                        <option value="cash">Tunai / Cash</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-gray-700 uppercase mb-1">Diterima Dari <span class="text-rose-500">*</span></label>
                    <input type="text" name="received_from" id="editReceivedFrom" required maxlength="150"
                           class="w-full text-xs rounded-xl border-gray-300 py-2.5 px-3 focus:ring-brand-800">
                </div>
            </div>

            <div>
                <label class="block font-bold text-gray-700 uppercase mb-1">Catatan</label>
                <input type="text" name="notes" id="editNotes" maxlength="255"
                       class="w-full text-xs rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800">
            </div>

            <div class="p-3 bg-amber-50 border border-amber-100 rounded-xl text-[11px] text-amber-900">
                <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                Perubahan akan tercatat pada Log Aktivitas beserta nilai sebelum dan sesudahnya. Laporan kas, arus kas, dan sisa tunggakan otomatis dihitung ulang dari kuitansi yang masih aktif.
            </div>

            <div class="flex items-center justify-end space-x-2 pt-2">
                <button type="button" onclick="document.getElementById('editPaymentModal').classList.add('hidden')" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-semibold transition">Batal</button>
                <button type="submit" class="px-5 py-2 bg-brand-900 hover:bg-brand-800 text-white rounded-xl font-bold shadow-md transition">
                    <i class="fa-solid fa-floppy-disk mr-1.5"></i> Simpan Perbaikan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Batalkan Kuitansi -->
<div id="voidPaymentModal" class="fixed inset-0 z-50 hidden bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full overflow-hidden border border-gray-100">
        <div class="bg-gradient-to-r from-rose-800 to-rose-700 px-6 py-5 text-white flex items-center justify-between">
            <div>
                <h3 class="font-bold text-base">Batalkan Kuitansi</h3>
                <p class="text-xs text-rose-100" id="voidPaymentCode">-</p>
            </div>
            <button type="button" onclick="document.getElementById('voidPaymentModal').classList.add('hidden')" class="text-gray-200 hover:text-white text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="voidPaymentForm" method="POST" class="p-6 space-y-4 text-xs">
            @csrf
            @method('PATCH')

            <div class="p-3 bg-rose-50 border border-rose-100 rounded-xl text-[11px] text-rose-900 space-y-2">
                <div class="grid grid-cols-3 gap-2">
                    <div class="col-span-2">
                        <p class="text-[10px] font-bold uppercase text-rose-400">Nama Santri</p>
                        <p class="font-bold text-rose-900" id="voidStudent">-</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase text-rose-400">Marhalah</p>
                        <p class="font-semibold text-rose-900" id="voidKelompok">-</p>
                    </div>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase text-rose-400">Asal</p>
                    <p class="font-semibold text-rose-900" id="voidOrigin">-</p>
                </div>
                <div class="pt-2 border-t border-rose-200">
                    <p class="font-semibold" id="voidInvoice">-</p>
                    <p class="text-rose-700" id="voidReceived">-</p>
                    <p class="font-bold mt-1" id="voidPaymentSummary">-</p>
                </div>
                <p>Kuitansi tidak dihapus, tetapi ditandai <strong>DIBATALKAN</strong>. Status tagihan, laporan kas, dan arus kas langsung dihitung ulang.</p>
            </div>

            <div>
                <label class="block font-bold text-gray-700 uppercase mb-1">Alasan Pembatalan <span class="text-rose-500">*</span></label>
                <textarea name="void_reason" required rows="3" maxlength="255" placeholder="Contoh: Salah input nominal, seharusnya Rp 500.000"
                          class="w-full text-xs rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800"></textarea>
            </div>

            <div class="flex items-center justify-end space-x-2 pt-2">
                <button type="button" onclick="document.getElementById('voidPaymentModal').classList.add('hidden')" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-semibold transition">Tutup</button>
                <button type="submit" class="px-5 py-2 bg-rose-700 hover:bg-rose-800 text-white rounded-xl font-bold shadow-md transition">
                    <i class="fa-solid fa-ban mr-1.5"></i> Batalkan Kuitansi
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditPayment(button) {
        const data = JSON.parse(button.dataset.payment);
        document.getElementById('editPaymentForm').action = "{{ url('admin/transaksi') }}/" + data.id;
        document.getElementById('editPaymentCode').textContent = data.code || '-';
        document.getElementById('editAmount').value = data.amount;
        document.getElementById('editDate').value = data.date || '';
        document.getElementById('editMethod').value = data.method || 'transfer';
        document.getElementById('editReceivedFrom').value = data.received_from || '';
        document.getElementById('editNotes').value = data.notes || '';
        document.getElementById('editStudent').textContent = data.student || '-';
        document.getElementById('editOrigin').textContent = data.origin || '-';
        document.getElementById('editKelompok').textContent = data.kelompok || '-';
        document.getElementById('editInvoice').textContent = data.invoice || '-';
        document.getElementById('editPaymentModal').classList.remove('hidden');
    }

    function openVoidPayment(button) {
        const data = JSON.parse(button.dataset.payment);
        document.getElementById('voidPaymentForm').action = "{{ url('admin/transaksi') }}/" + data.id + "/void";
        document.getElementById('voidPaymentCode').textContent = data.code || '-';
        document.getElementById('voidStudent').textContent = data.student || '-';
        document.getElementById('voidOrigin').textContent = data.origin || '-';
        document.getElementById('voidKelompok').textContent = data.kelompok || '-';
        document.getElementById('voidInvoice').textContent = data.invoice || '-';
        document.getElementById('voidReceived').textContent = 'Diterima dari: ' + (data.received_from || '-') + ' (' + (data.method || '-') + ')';
        document.getElementById('voidPaymentSummary').textContent = 'Nominal: Rp ' + data.amount;
        document.getElementById('voidPaymentModal').classList.remove('hidden');
    }
</script>
@endsection
