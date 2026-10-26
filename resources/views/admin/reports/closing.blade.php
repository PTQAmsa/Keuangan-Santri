@extends('layouts.app')

@section('title', 'Tutup Buku Bulanan - PTQ Amsa001')

@section('content')
<div class="space-y-6">

    <!-- Header & Tabs -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-gray-200">
        <div>
            <div class="flex items-center space-x-2 text-xs text-gray-500 mb-1">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-brand-900">Admin</a>
                <span>/</span>
                <span class="text-gray-800 font-semibold">Laporan Keuangan</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center">
                <i class="fa-solid fa-lock text-slate-700 mr-2.5"></i> Tutup Buku Bulanan
            </h1>
            <p class="text-xs sm:text-sm text-gray-500 mt-0.5">Simpan snapshot laporan dan kunci transaksi kas per bulan</p>
        </div>

        @include('admin.reports._tabs')
    </div>

    <!-- Penjelasan kebijakan -->
    <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 flex items-start space-x-3">
        <div class="w-9 h-9 rounded-xl bg-slate-200 text-slate-700 flex items-center justify-center flex-shrink-0">
            <i class="fa-solid fa-shield-halved"></i>
        </div>
        <div class="text-xs sm:text-sm text-slate-700">
            <p class="font-semibold">Apa yang terjadi saat buku ditutup?</p>
            <p class="mt-0.5 text-slate-600">
                Angka kas masuk, kas keluar, dan saldo akhir bulan itu disimpan sebagai snapshot. Pembayaran, koreksi/pembatalan kuitansi,
                dan kas keluar bertanggal bulan tersebut tidak dapat diubah lagi sampai Administrator membuka kembali periodenya dengan alasan.
                Penerbitan tagihan tetap dapat dilakukan karena tidak mengubah kas.
            </p>
        </div>
    </div>

    <!-- Navigasi Tahun -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center space-x-2">
            <a href="{{ route('admin.reports.closing', ['year' => $year - 1]) }}"
               class="w-9 h-9 rounded-xl bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 flex items-center justify-center shadow-sm {{ $year <= 2025 ? 'pointer-events-none opacity-40' : '' }}"
               aria-label="Tahun sebelumnya">
                <i class="fa-solid fa-chevron-left text-xs"></i>
            </a>
            <div class="px-4 py-2 bg-white border border-gray-200 rounded-xl shadow-sm text-sm font-bold text-gray-900">
                Tahun {{ $year }}
            </div>
            <a href="{{ route('admin.reports.closing', ['year' => $year + 1]) }}"
               class="w-9 h-9 rounded-xl bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 flex items-center justify-center shadow-sm {{ $year >= now()->year ? 'pointer-events-none opacity-40' : '' }}"
               aria-label="Tahun berikutnya">
                <i class="fa-solid fa-chevron-right text-xs"></i>
            </a>
        </div>
        <p class="text-xs text-gray-500">
            {{ $closedCount }} dari 12 bulan pada {{ $year }} sudah ditutup.
        </p>
    </div>

    <!-- Ringkasan Tahun -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-emerald-500 to-emerald-700 rounded-2xl p-5 text-white shadow-md">
            <p class="text-xs font-medium text-emerald-100 uppercase tracking-wider">Kas Masuk {{ $year }}</p>
            <p class="text-2xl font-black mt-1">Rp {{ number_format($totals['in'], 0, ',', '.') }}</p>
            <p class="text-xs text-emerald-200 mt-2"><i class="fa-solid fa-arrow-down mr-1.5"></i>Pembayaran santri aktif</p>
        </div>
        <div class="bg-gradient-to-br from-rose-500 to-rose-700 rounded-2xl p-5 text-white shadow-md">
            <p class="text-xs font-medium text-rose-100 uppercase tracking-wider">Kas Keluar {{ $year }}</p>
            <p class="text-2xl font-black mt-1">Rp {{ number_format($totals['out'], 0, ',', '.') }}</p>
            <p class="text-xs text-rose-200 mt-2"><i class="fa-solid fa-arrow-up mr-1.5"></i>Pengeluaran operasional aktif</p>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-sm">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Saldo per 31 Des {{ $year }}</p>
            <p class="text-xl font-extrabold {{ $saldoTahun < 0 ? 'text-rose-700' : 'text-gray-900' }} mt-1">
                Rp {{ number_format($saldoTahun, 0, ',', '.') }}
            </p>
            <p class="text-xs text-gray-400 mt-2">Kas masuk dikurangi kas keluar</p>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-sm">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Bulan Ditutup</p>
            <p class="text-2xl font-extrabold text-gray-900 mt-1">{{ $closedCount }} <span class="text-xs font-normal text-gray-500">/ 12 bulan</span></p>
            <p class="text-xs text-gray-400 mt-2">Bisa dibuka kembali oleh Administrator</p>
        </div>
    </div>

    <!-- Tabel Bulanan -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 bg-gray-50/70 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-base font-bold text-gray-800 flex items-center">
                <i class="fa-solid fa-calendar-days text-brand-800 mr-2"></i> Status Buku {{ $year }}
            </h2>
            <span class="text-xs text-gray-500">12 periode</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs sm:text-sm">
                <thead class="bg-gray-50 text-[11px] uppercase tracking-wider text-gray-500">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Bulan</th>
                        <th class="px-4 py-3 font-semibold text-right">Kas Masuk</th>
                        <th class="px-4 py-3 font-semibold text-right">Kas Keluar</th>
                        <th class="px-4 py-3 font-semibold text-right">Saldo Akhir</th>
                        <th class="px-4 py-3 font-semibold text-center">Transaksi</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                        <th class="px-5 py-3 font-semibold text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($rows as $row)
                        @php
                            $closing = $row['closing'];
                            $isClosed = $closing?->isClosed() ?? false;
                            $isReopened = $closing && $closing->reopened_at !== null;
                            $summary = $row['summary'];
                        @endphp
                        <tr class="hover:bg-gray-50/60 transition {{ $row['is_future'] ? 'opacity-60' : '' }}">
                            <td class="px-5 py-3.5">
                                <p class="font-semibold text-gray-900">{{ $row['label'] }}</p>
                                <p class="text-[11px] text-gray-400">{{ $row['start']->format('d/m/Y') }} &ndash; {{ $row['start']->copy()->endOfMonth()->format('d/m/Y') }}</p>
                            </td>
                            <td class="px-4 py-3.5 text-right whitespace-nowrap font-medium text-emerald-700">
                                Rp {{ number_format($summary['total_in'], 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3.5 text-right whitespace-nowrap font-medium text-rose-700">
                                Rp {{ number_format($summary['total_out'], 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3.5 text-right whitespace-nowrap font-bold {{ $summary['closing_balance'] < 0 ? 'text-rose-700' : 'text-gray-900' }}">
                                Rp {{ number_format($summary['closing_balance'], 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3.5 text-center text-[11px] text-gray-500 whitespace-nowrap">
                                {{ $summary['payment_count'] }} masuk<br>{{ $summary['expense_count'] }} keluar
                            </td>
                            <td class="px-4 py-3.5">
                                @if($row['is_future'])
                                    <span class="inline-flex px-2 py-0.5 rounded-full bg-gray-100 text-gray-500 border border-gray-200 text-[11px] font-semibold">Belum Berjalan</span>
                                @elseif($isClosed)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-slate-200 text-slate-800 text-[11px] font-semibold">
                                        <i class="fa-solid fa-lock mr-1 text-[9px]"></i> Ditutup
                                    </span>
                                    <p class="text-[11px] text-gray-500 mt-1">
                                        {{ $closing->closedBy?->name ?? '-' }}<br>
                                        {{ $closing->closed_at?->translatedFormat('d M Y H:i') }}
                                    </p>
                                @elseif($isReopened)
                                    <span class="inline-flex px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[11px] font-semibold">
                                        <i class="fa-solid fa-lock-open mr-1 text-[9px]"></i> Dibuka Kembali
                                    </span>
                                    <p class="text-[11px] text-gray-500 mt-1" title="{{ $closing->reopen_reason }}">
                                        {{ $closing->reopenedBy?->name ?? '-' }} &bull;
                                        {{ $closing->reopened_at?->translatedFormat('d M Y H:i') }}
                                    </p>
                                @else
                                    <span class="inline-flex px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[11px] font-semibold">
                                        <i class="fa-solid fa-lock-open mr-1 text-[9px]"></i> Terbuka
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                @if($row['is_future'])
                                    <span class="text-[11px] text-gray-400">&mdash;</span>
                                @elseif($isClosed)
                                    @if(auth()->user()->role?->isAdmin())
                                        <button type="button"
                                                data-reopen-url="{{ route('admin.reports.closing.reopen', $closing) }}"
                                                data-period="{{ $row['label'] }} {{ $year }}"
                                                onclick="openReopenModal(this)"
                                                class="px-2.5 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 rounded-lg text-[11px] font-semibold transition">
                                            <i class="fa-solid fa-lock-open mr-1"></i> Buka Kembali
                                        </button>
                                    @else
                                        <span class="text-[11px] text-gray-400">Khusus Administrator</span>
                                    @endif
                                @elseif(auth()->user()->role?->canManageFinances())
                                    <button type="button"
                                            data-close-month="{{ $row['month'] }}"
                                            data-period="{{ $row['label'] }} {{ $year }}"
                                            data-total-in="{{ number_format($summary['total_in'], 0, ',', '.') }}"
                                            data-total-out="{{ number_format($summary['total_out'], 0, ',', '.') }}"
                                            data-balance="{{ number_format($summary['closing_balance'], 0, ',', '.') }}"
                                            data-counts="{{ $summary['payment_count'] }} masuk / {{ $summary['expense_count'] }} keluar"
                                            onclick="openClosingModal(this)"
                                            class="px-2.5 py-1.5 bg-slate-800 hover:bg-slate-900 text-white rounded-lg text-[11px] font-semibold transition">
                                        <i class="fa-solid fa-lock mr-1"></i> Tutup Buku
                                    </button>
                                @else
                                    <span class="text-[11px] text-gray-400">Hanya lihat</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tutup Buku -->
<div id="closing-modal" data-modal class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm p-4 flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-gray-900"><i class="fa-solid fa-lock text-slate-700 mr-2"></i>Tutup Buku</h2>
                <p class="text-xs text-gray-500 mt-0.5" id="closing-modal-period">Periode</p>
            </div>
            <button type="button" onclick="closeModal('closing-modal')" class="w-8 h-8 rounded-lg hover:bg-gray-100 text-gray-500" aria-label="Tutup">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form method="POST" action="{{ route('admin.reports.closing.store') }}" class="p-6 space-y-4">
            @csrf
            <input type="hidden" name="period_year" value="{{ $year }}">
            <input type="hidden" name="period_month" id="closing-modal-month" value="">

            <div class="rounded-xl bg-gray-50 border border-gray-100 p-4 space-y-2 text-sm">
                <div class="flex items-center justify-between"><span class="text-gray-500">Kas masuk</span><span class="font-semibold text-emerald-700" id="closing-modal-in">Rp 0</span></div>
                <div class="flex items-center justify-between"><span class="text-gray-500">Kas keluar</span><span class="font-semibold text-rose-700" id="closing-modal-out">Rp 0</span></div>
                <div class="flex items-center justify-between border-t border-gray-200 pt-2"><span class="font-semibold text-gray-700">Saldo akhir</span><span class="font-bold text-gray-900" id="closing-modal-balance">Rp 0</span></div>
                <div class="flex items-center justify-between"><span class="text-gray-500">Jumlah transaksi</span><span class="text-gray-700" id="closing-modal-counts">-</span></div>
            </div>

            <div class="bg-rose-50 border border-rose-200 rounded-xl p-3 text-xs text-rose-800 flex items-start space-x-2">
                <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
                <p>Setelah ditutup, pembayaran, koreksi/pembatalan kuitansi, dan kas keluar pada periode ini <strong>tidak dapat diubah</strong>. Pastikan laporan sudah diperiksa.</p>
            </div>

            <label class="flex items-start space-x-2 text-xs text-gray-700">
                <input type="checkbox" name="confirm_close" value="1" required class="mt-0.5 rounded border-gray-300 text-brand-900 focus:ring-brand-800">
                <span>Saya sudah memeriksa laporan periode ini dan yakin menutupnya.</span>
            </label>

            <div class="flex items-center justify-end space-x-2 pt-2 border-t border-gray-100">
                <button type="button" onclick="closeModal('closing-modal')" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-sm font-semibold transition">Batal</button>
                <button type="submit" class="px-5 py-2.5 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-sm font-semibold shadow-sm transition">
                    <i class="fa-solid fa-lock mr-1.5"></i> Tutup Buku Sekarang
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Buka Kembali (Administrator) -->
@if(auth()->user()->role?->isAdmin())
    <div id="reopen-modal" data-modal class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm p-4 flex items-center justify-center">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-gray-900"><i class="fa-solid fa-lock-open text-amber-600 mr-2"></i>Buka Kembali Periode</h2>
                    <p class="text-xs text-gray-500 mt-0.5" id="reopen-modal-period">Periode</p>
                </div>
                <button type="button" onclick="closeModal('reopen-modal')" class="w-8 h-8 rounded-lg hover:bg-gray-100 text-gray-500" aria-label="Tutup">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <form method="POST" id="reopen-form" action="#" class="p-6 space-y-4">
                @csrf
                @method('PATCH')

                <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 text-xs text-amber-900 flex items-start space-x-2">
                    <i class="fa-solid fa-circle-info mt-0.5"></i>
                    <p>Transaksi pada periode ini akan dapat diubah kembali. Snapshot yang sudah tersimpan tetap menjadi riwayat, dan alasan pembukaan tercatat di Log Aktivitas.</p>
                </div>

                <div>
                    <label for="reopen_reason" class="block text-xs font-semibold text-gray-700 mb-1">Alasan Pembukaan Kembali <span class="text-rose-500">*</span></label>
                    <textarea id="reopen_reason" name="reopen_reason" rows="3" minlength="5" maxlength="500" required
                              placeholder="Contoh: kuitansi salah input perlu dikoreksi setelah laporan diserahkan"
                              class="w-full text-sm rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800 focus:border-brand-800"></textarea>
                </div>

                <div class="flex items-center justify-end space-x-2 pt-2 border-t border-gray-100">
                    <button type="button" onclick="closeModal('reopen-modal')" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-sm font-semibold transition">Batal</button>
                    <button type="submit" class="px-5 py-2.5 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-sm font-semibold shadow-sm transition">
                        <i class="fa-solid fa-lock-open mr-1.5"></i> Buka Kembali
                    </button>
                </div>
            </form>
        </div>
    </div>
@endif

<script>
    function openClosingModal(button) {
        document.getElementById('closing-modal-period').textContent = 'Periode ' + button.dataset.period;
        document.getElementById('closing-modal-month').value = button.dataset.closeMonth;
        document.getElementById('closing-modal-in').textContent = 'Rp ' + button.dataset.totalIn;
        document.getElementById('closing-modal-out').textContent = 'Rp ' + button.dataset.totalOut;
        document.getElementById('closing-modal-balance').textContent = 'Rp ' + button.dataset.balance;
        document.getElementById('closing-modal-counts').textContent = button.dataset.counts;
        openModal('closing-modal');
    }

    function openReopenModal(button) {
        document.getElementById('reopen-modal-period').textContent = 'Periode ' + button.dataset.period;
        document.getElementById('reopen-form').action = button.dataset.reopenUrl;
        openModal('reopen-modal');
    }

    function openModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function closeModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;
        modal.classList.add('hidden');
        if (!document.querySelector('[data-modal]:not(.hidden)')) {
            document.body.classList.remove('overflow-hidden');
        }
    }

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        document.querySelectorAll('[data-modal]:not(.hidden)').forEach(function (modal) {
            closeModal(modal.id);
        });
    });

    document.querySelectorAll('[data-modal]').forEach(function (modal) {
        modal.addEventListener('click', function (event) {
            if (event.target === modal) closeModal(modal.id);
        });
    });
</script>
@endsection
