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
        <a href="{{ route('admin.invoices') }}" class="px-3.5 py-2 bg-white border border-gray-300 hover:bg-gray-100 text-gray-700 rounded-xl text-xs font-semibold shadow-sm transition">
            <i class="fa-solid fa-file-invoice mr-1.5"></i> Daftar Tagihan
        </a>
    </div>

    <!-- Ringkasan -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-sm">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Kuitansi Aktif</p>
            <p class="text-xl font-extrabold text-emerald-700 mt-1">Rp {{ number_format($totalAktif, 0, ',', '.') }}</p>
            <p class="text-xs text-gray-400 mt-1">Dihitung dari kuitansi yang belum dibatalkan</p>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-sm">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Dibatalkan</p>
            <p class="text-xl font-extrabold text-rose-700 mt-1">Rp {{ number_format($totalDibatalkan, 0, ',', '.') }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ $jumlahDibatalkan }} kuitansi dibatalkan</p>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-sm">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Peran yang Berhak</p>
            <p class="text-sm font-bold text-brand-900 mt-1">Administrator &amp; Bendahara</p>
            <p class="text-xs text-gray-400 mt-1">Pengasuh hanya dapat melihat laporan</p>
        </div>
    </div>

    <!-- Filter -->
    <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
        <form action="{{ route('admin.transactions') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end text-xs">
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

    <!-- Tabel Transaksi -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs sm:text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 uppercase text-[11px] font-semibold border-b border-gray-200">
                        <th class="py-3 px-4 whitespace-nowrap">No. Kuitansi</th>
                        <th class="py-3 px-4 whitespace-nowrap">Tanggal</th>
                        <th class="py-3 px-4">Santri</th>
                        <th class="py-3 px-4 text-center whitespace-nowrap">Marhalah</th>
                        <th class="py-3 px-4">Uraian Tagihan</th>
                        <th class="py-3 px-4 text-right whitespace-nowrap">Nominal</th>
                        <th class="py-3 px-4 text-center whitespace-nowrap">Status</th>
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
                                <p class="text-xs font-medium text-gray-700">{{ $pay->invoice->title ?? '-' }}</p>
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
                                                ]) }}"
                                                onclick="openEditPayment(this)"
                                                class="inline-flex items-center px-2.5 py-1 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-800 text-[11px] font-semibold border border-amber-200 transition">
                                            <i class="fa-solid fa-pen-to-square mr-1"></i> Ubah
                                        </button>
                                        <button type="button"
                                                data-payment="{{ json_encode([
                                                    'id' => $pay->id,
                                                    'code' => $pay->receipt->receipt_number ?? $pay->payment_code,
                                                    'student' => $pay->student->nama_lengkap ?? '-',
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
                Perubahan akan tercatat pada Log Aktivitas beserta nilai sebelum dan sesudahnya.
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

            <div class="p-3 bg-rose-50 border border-rose-100 rounded-xl text-[11px] text-rose-900 space-y-1">
                <p id="voidPaymentSummary">-</p>
                <p>Kuitansi tidak dihapus, tetapi ditandai <strong>DIBATALKAN</strong>. Status tagihan akan dihitung ulang.</p>
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
        document.getElementById('editPaymentModal').classList.remove('hidden');
    }

    function openVoidPayment(button) {
        const data = JSON.parse(button.dataset.payment);
        document.getElementById('voidPaymentForm').action = "{{ url('admin/transaksi') }}/" + data.id + "/void";
        document.getElementById('voidPaymentCode').textContent = data.code || '-';
        document.getElementById('voidPaymentSummary').textContent = data.student + ' — Rp ' + data.amount;
        document.getElementById('voidPaymentModal').classList.remove('hidden');
    }
</script>
@endsection
