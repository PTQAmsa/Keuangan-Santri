@extends('layouts.app')

@section('title', 'Bisyarah Bulanan - SIKA PTQ')

@section('content')
@php
    $addContext = old('_form_context') === 'add';
    $addValue = fn (string $field, $default = null) => $addContext ? old($field, $default) : $default;
@endphp

<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-gray-200">
        <div>
            <div class="flex items-center space-x-2 text-xs text-gray-500 mb-1">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-brand-900">Admin</a>
                <span>/</span>
                <a href="{{ route('admin.teachers') }}" class="hover:text-brand-900">Data Guru dan Staf</a>
                <span>/</span>
                <span class="text-gray-800 font-semibold">Bisyarah Bulanan</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center">
                <i class="fa-solid fa-money-bill-wave text-emerald-600 mr-2.5"></i> Bisyarah Bulanan
            </h1>
            <p class="text-xs sm:text-sm text-gray-500 mt-0.5">
                Pilih guru yang sudah terdaftar, lalu isi nominal dan metodenya. Data guru tidak perlu diisi ulang.
            </p>
        </div>
        <div class="flex flex-col sm:flex-row sm:items-center gap-2">
            @include('admin.teachers._tabs')
            @unless($isLocked)
                <button type="button" onclick="openBisyarahAdd()"
                        class="inline-flex items-center justify-center px-4 py-2.5 bg-brand-900 hover:bg-brand-800 text-white rounded-xl text-xs font-bold shadow-sm transition whitespace-nowrap">
                    <i class="fa-solid fa-plus mr-1.5"></i> Catat Bisyarah
                </button>
            @endunless
        </div>
    </div>

    <!-- Pilih Periode -->
    <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-sm">
        <form method="GET" action="{{ route('admin.bisyarah') }}" class="flex flex-col sm:flex-row sm:items-end gap-3">
            <div>
                <label for="period" class="block text-xs font-semibold text-gray-700 mb-1">Periode Bisyarah</label>
                <input type="month" id="period" name="period" value="{{ $period }}"
                       class="text-sm rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800 focus:border-brand-800 bg-gray-50/50">
            </div>
            <button type="submit" class="px-4 py-2 bg-brand-900 hover:bg-brand-800 text-white rounded-xl text-xs font-semibold shadow-sm transition">
                <i class="fa-solid fa-calendar-check mr-1"></i> Tampilkan
            </button>
            @if($period !== now()->format('Y-m'))
                <a href="{{ route('admin.bisyarah') }}" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl text-xs font-semibold text-center transition">
                    Bulan ini
                </a>
            @endif
            <p class="text-xs text-gray-500 sm:ml-auto">
                <i class="fa-solid fa-circle-info mr-1 text-gray-400"></i>
                Periode yang sudah ditutup buku tidak dapat dicatat atau diubah.
            </p>
        </form>
    </div>

    @if($isLocked && $closing)
        <div class="bg-slate-100 border-l-4 border-slate-500 p-4 rounded-r-lg shadow-sm flex items-start space-x-3">
            <i class="fa-solid fa-lock text-slate-600 text-lg mt-0.5"></i>
            <div class="text-xs sm:text-sm text-slate-800">
                <p class="font-semibold">Bisyarah {{ $periodLabel }} terkunci karena buku bulan ini sudah ditutup.</p>
                <p class="mt-0.5">
                    Ditutup pada {{ $closing->closed_at?->translatedFormat('d F Y H:i') ?? '-' }}.
                    Buka kembali lewat
                    <a href="{{ route('admin.reports.closing', ['year' => substr($period, 0, 4)]) }}" class="text-brand-800 font-semibold hover:underline">Tutup Buku</a>
                    bila ada koreksi. Hanya Administrator yang dapat membuka kembali.
                </p>
            </div>
        </div>
    @endif

    <!-- Ringkasan -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-emerald-700 to-emerald-900 rounded-2xl p-5 text-white shadow-md">
            <p class="text-xs font-medium text-emerald-100 uppercase tracking-wider">Total Bisyarah</p>
            <p class="text-2xl font-black mt-1">Rp {{ number_format($totalAmount, 0, ',', '.') }}</p>
            <p class="text-xs text-emerald-100 mt-2"><i class="fa-regular fa-calendar mr-1.5"></i>{{ $periodLabel }}</p>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-sm">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Transfer</p>
            <p class="text-xl font-extrabold text-brand-900 mt-1">Rp {{ number_format($totalTransfer, 0, ',', '.') }}</p>
            <p class="text-xs text-gray-400 mt-2">Dikirim ke rekening guru</p>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-sm">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Tunai</p>
            <p class="text-xl font-extrabold text-gray-900 mt-1">Rp {{ number_format($totalCash, 0, ',', '.') }}</p>
            <p class="text-xs text-gray-400 mt-2">Dibayar langsung</p>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-sm">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Guru Aktif Tercatat</p>
            <p class="text-2xl font-extrabold text-gray-900 mt-1">{{ $activeRecordedCount }} <span class="text-xs font-medium text-gray-400">/ {{ $activeTeacherCount }}</span></p>
            <p class="text-xs text-gray-400 mt-2">
                {{ max($activeTeacherCount - $activeRecordedCount, 0) }} guru aktif belum dicatat
                @if($recordedCount > $activeRecordedCount)
                    · {{ $recordedCount - $activeRecordedCount }} catatan nonaktif
                @endif
            </p>
        </div>
    </div>

    <!-- Daftar Bisyarah -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 bg-gray-50/70 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h2 class="text-base font-bold text-gray-800 flex items-center">
                    <i class="fa-solid fa-list-ul text-brand-800 mr-2"></i> Daftar Bisyarah {{ $periodLabel }}
                </h2>
                <p class="text-xs text-gray-500 mt-0.5">{{ $teachers->count() }} guru ditampilkan. Satu guru satu catatan per bulan.</p>
            </div>
            @if($isLocked)
                <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-slate-100 text-slate-600 border border-slate-200 text-[11px] font-semibold">
                    <i class="fa-solid fa-lock mr-1.5"></i> Periode terkunci
                </span>
            @endif
        </div>

        @if($teachers->isEmpty())
            <div class="p-12 text-center text-gray-400 text-sm">
                <i class="fa-solid fa-user-tie text-4xl text-gray-300 block mb-3"></i>
                <p class="font-semibold text-gray-500">Belum ada guru aktif.</p>
                <p class="text-xs mt-1">
                    Tambahkan data guru terlebih dahulu di
                    <a href="{{ route('admin.teachers') }}" class="text-brand-800 font-semibold hover:underline">Data Guru dan Staf</a>.
                </p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs sm:text-sm">
                    <thead class="bg-gray-50 text-[11px] uppercase tracking-wider text-gray-500">
                        <tr>
                            <x-sort-th column="teacher" label="Guru" :sort-by="$sortBy" :sort-order="$sortOrder" class="px-5 py-3 font-semibold" />
                            <x-sort-th column="whatsapp" label="WhatsApp" :sort-by="$sortBy" :sort-order="$sortOrder" class="px-4 py-3 font-semibold" />
                            <x-sort-th column="recorded" label="Status" :sort-by="$sortBy" :sort-order="$sortOrder" class="px-4 py-3 font-semibold" />
                            <x-sort-th column="amount" label="Nominal" :sort-by="$sortBy" :sort-order="$sortOrder" align="right" class="px-4 py-3 font-semibold" />
                            <x-sort-th column="method" label="Metode" :sort-by="$sortBy" :sort-order="$sortOrder" class="px-4 py-3 font-semibold" />
                            <x-sort-th column="account" label="Rekening / Dompet" :sort-by="$sortBy" :sort-order="$sortOrder" class="px-4 py-3 font-semibold" />
                            <th class="px-5 py-3 font-semibold text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($teachers as $teacher)
                            @php
                                $payment = $payments->get($teacher->id);
                                $account = $lastTransferAccounts->get($teacher->id);
                                $wa = \App\Models\Teacher::normalizeWhatsApp($teacher->no_whatsapp);
                            @endphp
                            <tr id="guru-{{ $teacher->id }}" class="hover:bg-gray-50/60 transition {{ $teacher->is_active ? '' : 'opacity-60' }}">
                                <td class="px-5 py-3.5">
                                    <p class="font-semibold text-gray-900">{{ $teacher->nama_lengkap }}</p>
                                    <p class="text-[11px] text-gray-500 mt-0.5">
                                        <i class="fa-solid fa-location-dot mr-1 text-gray-400"></i>{{ $teacher->kota_asal }}
                                        @unless($teacher->is_active)
                                            <span class="ml-2 inline-flex px-1.5 py-0.5 rounded bg-gray-100 text-gray-600 border border-gray-200 text-[10px] font-semibold">Nonaktif</span>
                                        @endunless
                                    </p>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    @if($wa !== '')
                                        <a href="https://wa.me/{{ $wa }}" target="_blank" class="text-emerald-700 hover:text-emerald-800 font-medium">
                                            <i class="fa-brands fa-whatsapp mr-1"></i>+{{ $wa }}
                                        </a>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5">
                                    @if($payment)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full border bg-emerald-50 text-emerald-700 border-emerald-200 text-[11px] font-semibold">
                                            <i class="fa-solid fa-circle-check mr-1"></i> Sudah dicatat
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full border bg-gray-50 text-gray-500 border-gray-200 text-[11px] font-semibold">
                                            <i class="fa-regular fa-circle mr-1"></i> Belum dicatat
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                    @if($payment)
                                        <p class="font-bold text-gray-900">Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}</p>
                                        @if($payment->expense)
                                            <a href="{{ route('admin.expenses', ['start_date' => $payment->expense->expense_date->toDateString(), 'end_date' => $payment->expense->expense_date->toDateString()]) }}"
                                               class="font-mono text-[10px] text-rose-600 hover:text-rose-800" title="Lihat di Kas Keluar">
                                                <i class="fa-solid fa-arrow-trend-down mr-0.5"></i>{{ $payment->expense->expense_code }}
                                            </a>
                                        @endif
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5">
                                    @if($payment)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $payment->payment_method === 'cash' ? 'bg-emerald-50 text-emerald-700' : 'bg-blue-50 text-blue-700' }}">
                                            <i class="fa-solid {{ $payment->payment_method === 'cash' ? 'fa-money-bill-wave' : 'fa-building-columns' }} mr-1"></i>
                                            {{ $payment->paymentChannelLabel() }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5">
                                    @if($payment && $payment->hasBankAccount())
                                        <p class="font-medium text-gray-800">{{ $payment->bank_name ?: 'Bank' }} &bull; {{ $payment->bank_account_number }}</p>
                                        <p class="text-[11px] text-gray-500">{{ $payment->bank_account_name ?: 'Nama pemilik belum diisi' }}</p>
                                    @elseif($payment)
                                        <span class="text-gray-400">Tunai</span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                    @if($isLocked)
                                        <span class="inline-flex items-center px-2 py-1 rounded-lg bg-slate-100 text-slate-500 border border-slate-200 text-[11px] font-semibold" title="Buka kembali periode di Tutup Buku untuk mengubah">
                                            <i class="fa-solid fa-lock mr-1"></i> Terkunci
                                        </span>
                                    @elseif($payment)
                                        <button type="button" onclick="openBisyarahModal('bisyarah-modal-edit-{{ $payment->id }}')"
                                                class="px-2.5 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 rounded-lg text-[11px] font-semibold transition">
                                            <i class="fa-solid fa-pen-to-square mr-1"></i> Ubah
                                        </button>
                                        <form action="{{ route('admin.bisyarah.destroy', $payment) }}" method="POST" class="inline"
                                              onsubmit="const reason = prompt('Alasan pembatalan bisyarah {{ $teacher->nama_lengkap }} {{ $periodLabel }}:'); if (!reason || !reason.trim()) { return false; } this.reason.value = reason.trim(); return true;">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="reason" value="">
                                            <button type="submit" title="Batalkan bisyarah beserta kas keluarnya"
                                                    class="px-2.5 py-1.5 bg-gray-50 hover:bg-rose-50 text-gray-600 hover:text-rose-700 border border-gray-200 rounded-lg text-[11px] font-semibold transition">
                                                <i class="fa-solid fa-ban mr-1"></i> Batalkan
                                            </button>
                                        </form>
                                    @else
                                        <button type="button" onclick="openBisyarahAdd({{ $teacher->id }})"
                                                class="px-2.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-lg text-[11px] font-semibold transition">
                                            <i class="fa-solid fa-plus mr-1"></i> Catat
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <p class="text-[11px] text-gray-500 flex items-start">
        <i class="fa-solid fa-circle-info mr-1.5 mt-0.5 text-gray-400"></i>
        <span>Setiap catatan bisyarah otomatis menjadi satu baris <strong>Kas Keluar</strong> kategori Bisyarah, sehingga langsung terhitung di laporan arus kas dan tutup buku tanpa dicatat ulang. Nominal dan metode dapat berbeda setiap bulan. Bila keliru, gunakan tombol <strong>Ubah</strong> (kas keluar ikut disesuaikan) atau <strong>Batalkan</strong> (kas keluar ikut dibatalkan); riwayat tercatat di Log Aktivitas.</span>
    </p>
</div>

@unless($isLocked)
    <!-- Modal Catat Bisyarah -->
    <div id="bisyarah-modal-add" data-modal class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm p-4 flex items-center justify-center">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white z-10">
                <div>
                    <h2 class="text-base font-bold text-gray-900"><i class="fa-solid fa-money-bill-wave text-emerald-600 mr-2"></i>Catat Bisyarah</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Periode {{ $periodLabel }}. Pilih guru yang belum dicatat bulan ini.</p>
                </div>
                <button type="button" onclick="closeBisyarahModal('bisyarah-modal-add')" class="w-8 h-8 rounded-lg hover:bg-gray-100 text-gray-500" aria-label="Tutup">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <form id="bisyarah-add-form" method="POST" action="{{ route('admin.bisyarah.store') }}" class="p-6 space-y-5" data-bisyarah-form>
                @csrf
                <input type="hidden" name="_form_context" value="add">
                <input type="hidden" name="period" value="{{ $period }}">

                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Nama Guru <span class="text-rose-500">*</span></label>
                    <select name="teacher_id" required data-teacher-select
                            class="w-full text-sm rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800 focus:border-brand-800">
                        <option value="">-- Pilih Guru --</option>
                        @foreach($teachers->where('is_active', true) as $teacher)
                            @php
                                $alreadyRecorded = $payments->has($teacher->id);
                                $account = $lastTransferAccounts->get($teacher->id);
                            @endphp
                            <option value="{{ $teacher->id }}"
                                    data-bank-name="{{ $account->bank_name ?? '' }}"
                                    data-bank-number="{{ $account->bank_account_number ?? '' }}"
                                    data-bank-owner="{{ $account->bank_account_name ?? '' }}"
                                    {{ $alreadyRecorded ? 'disabled' : '' }}
                                    {{ (string) $addValue('teacher_id') === (string) $teacher->id ? 'selected' : '' }}>
                                {{ $teacher->nama_lengkap }} — {{ $teacher->kota_asal }}{{ $alreadyRecorded ? ' (sudah dicatat)' : '' }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-gray-400 mt-1">Guru yang sudah dicatat bulan ini tidak muncul sebagai pilihan baru; gunakan tombol Ubah pada tabel.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Nominal Bisyarah (Rp) <span class="text-rose-500">*</span></label>
                        <input type="number" name="amount" value="{{ $addValue('amount') }}" min="1" step="1" required
                               placeholder="Contoh: 1500000"
                               class="w-full text-sm rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800 focus:border-brand-800">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Metode Pembayaran <span class="text-rose-500">*</span></label>
                        <select name="payment_method" required data-payment-method
                                class="w-full text-sm rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800 focus:border-brand-800">
                            <option value="" {{ $addValue('payment_method') ? '' : 'selected' }}>-- Pilih Metode --</option>
                            <option value="transfer" {{ $addValue('payment_method') === 'transfer' ? 'selected' : '' }}>Transfer (Bank / Dompet Digital)</option>
                            <option value="cash" {{ $addValue('payment_method') === 'cash' ? 'selected' : '' }}>Tunai</option>
                        </select>
                    </div>
                </div>

                <div data-bank-fields class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-blue-50/60 border border-blue-100 rounded-xl p-4">
                    <p class="sm:col-span-2 text-[11px] text-blue-800">
                        <i class="fa-solid fa-building-columns mr-1"></i>
                        Catat lewat mana transfernya: rekening bank atau dompet digital (DANA, GoPay, OVO, dan sejenisnya). Pilih Lainnya bila tidak ada di daftar.
                    </p>
                    <x-transfer-channel-fields :channel="$addValue('transfer_channel')" :other="$addValue('transfer_channel_other', '')" />
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Nomor Rekening / Nomor Dompet Digital <span class="text-rose-500">*</span></label>
                        <input type="text" name="bank_account_number" value="{{ $addValue('bank_account_number') }}" maxlength="40" data-bank-required="1"
                               placeholder="Contoh: 7212345678 atau 081234567890"
                               class="w-full text-sm rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800 focus:border-brand-800">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Nama Pemilik Rekening <span class="text-gray-400 font-normal">(opsional)</span></label>
                        <input type="text" name="bank_account_name" value="{{ $addValue('bank_account_name') }}" maxlength="100"
                               placeholder="Sesuai buku tabungan"
                               class="w-full text-sm rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800 focus:border-brand-800">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Catatan <span class="text-gray-400 font-normal">(opsional)</span></label>
                    <textarea name="notes" rows="2" maxlength="500" placeholder="Contoh: bisyarah bulan Oktober"
                              class="w-full text-sm rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800 focus:border-brand-800">{{ $addValue('notes') }}</textarea>
                </div>

                <div class="flex items-center justify-end space-x-2 pt-2 border-t border-gray-100">
                    <button type="button" onclick="closeBisyarahModal('bisyarah-modal-add')" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-sm font-semibold transition">Batal</button>
                    <button type="submit" class="px-5 py-2.5 bg-brand-900 hover:bg-brand-800 text-white rounded-xl text-sm font-semibold shadow-sm transition">
                        <i class="fa-solid fa-save mr-1.5"></i> Simpan Bisyarah
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Ubah per Catatan -->
    @foreach($payments as $payment)
        @php
            $editContext = 'edit-'.$payment->id;
            $isEditContext = old('_form_context') === $editContext;
            $editValue = fn (string $field, $default = null) => $isEditContext ? old($field, $default) : $default;
        @endphp
        <div id="bisyarah-modal-edit-{{ $payment->id }}" data-modal class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm p-4 flex items-center justify-center">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white z-10">
                    <div>
                        <h2 class="text-base font-bold text-gray-900"><i class="fa-solid fa-pen-to-square text-amber-600 mr-2"></i>Ubah Bisyarah</h2>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $payment->teacher->nama_lengkap }} · {{ $payment->periodLabel() }}</p>
                    </div>
                    <button type="button" onclick="closeBisyarahModal('bisyarah-modal-edit-{{ $payment->id }}')" class="w-8 h-8 rounded-lg hover:bg-gray-100 text-gray-500" aria-label="Tutup">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <form method="POST" action="{{ route('admin.bisyarah.update', $payment) }}" class="p-6 space-y-5" data-bisyarah-form>
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="_form_context" value="{{ $editContext }}">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Nominal Bisyarah (Rp) <span class="text-rose-500">*</span></label>
                            <input type="number" name="amount" value="{{ $editValue('amount', (float) $payment->amount) }}" min="1" step="1" required
                                   class="w-full text-sm rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800 focus:border-brand-800">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Metode Pembayaran <span class="text-rose-500">*</span></label>
                            <select name="payment_method" required data-payment-method
                                    class="w-full text-sm rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800 focus:border-brand-800">
                                <option value="transfer" {{ $editValue('payment_method', $payment->payment_method) === 'transfer' ? 'selected' : '' }}>Transfer (Bank / Dompet Digital)</option>
                                <option value="cash" {{ $editValue('payment_method', $payment->payment_method) === 'cash' ? 'selected' : '' }}>Tunai</option>
                            </select>
                        </div>
                    </div>

                    <div data-bank-fields class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-blue-50/60 border border-blue-100 rounded-xl p-4">
                        <p class="sm:col-span-2 text-[11px] text-blue-800">
                            <i class="fa-solid fa-building-columns mr-1"></i>
                            Rekening atau dompet digital hanya diperlukan untuk pembayaran transfer.
                        </p>
                        <x-transfer-channel-fields
                            :channel="$editValue('transfer_channel', \App\Enums\TransferChannel::optionFor($payment->bank_name))"
                            :other="$editValue('transfer_channel_other', \App\Enums\TransferChannel::otherFor($payment->bank_name))" />
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Nomor Rekening / Nomor Dompet Digital <span class="text-rose-500">*</span></label>
                            <input type="text" name="bank_account_number" value="{{ $editValue('bank_account_number', $payment->bank_account_number) }}" maxlength="40" data-bank-required="1"
                                   placeholder="Contoh: 7212345678 atau 081234567890"
                                   class="w-full text-sm rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800 focus:border-brand-800">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Nama Pemilik Rekening <span class="text-gray-400 font-normal">(opsional)</span></label>
                            <input type="text" name="bank_account_name" value="{{ $editValue('bank_account_name', $payment->bank_account_name) }}" maxlength="100"
                                   placeholder="Sesuai buku tabungan"
                                   class="w-full text-sm rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800 focus:border-brand-800">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Catatan <span class="text-gray-400 font-normal">(opsional)</span></label>
                        <textarea name="notes" rows="2" maxlength="500"
                                  class="w-full text-sm rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800 focus:border-brand-800">{{ $editValue('notes', $payment->notes) }}</textarea>
                    </div>

                    <p class="text-[11px] text-gray-400">
                        Dicatat oleh {{ $payment->recordedBy->name ?? '-' }} pada {{ $payment->created_at?->translatedFormat('d F Y H:i') ?? '-' }}.
                    </p>

                    <div class="flex items-center justify-end space-x-2 pt-2 border-t border-gray-100">
                        <button type="button" onclick="closeBisyarahModal('bisyarah-modal-edit-{{ $payment->id }}')" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-sm font-semibold transition">Batal</button>
                        <button type="submit" class="px-5 py-2.5 bg-brand-900 hover:bg-brand-800 text-white rounded-xl text-sm font-semibold shadow-sm transition">
                            <i class="fa-solid fa-save mr-1.5"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@endunless

<script>
    function openBisyarahModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function closeBisyarahModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;
        modal.classList.add('hidden');
        if (!document.querySelector('[data-modal]:not(.hidden)')) {
            document.body.classList.remove('overflow-hidden');
        }
    }

    function toggleBankFields(form) {
        const method = form.querySelector('[data-payment-method]');
        const box = form.querySelector('[data-bank-fields]');
        if (!method || !box) return;

        const isTransfer = method.value === 'transfer';
        box.classList.toggle('hidden', !isTransfer);
        box.querySelectorAll('[data-bank-required]').forEach(function (input) {
            input.required = isTransfer;
        });

        const channel = box.querySelector('[data-transfer-channel]');
        if (channel) toggleTransferChannelOther(channel);
    }

    function fillTeacherAccount(form) {
        const select = form.querySelector('[data-teacher-select]');
        if (!select) return;
        const option = select.selectedOptions[0];
        if (!option || !option.value) return;

        setTransferChannel(form.querySelector('[data-transfer-channel-box]'), option.dataset.bankName || '');
        form.querySelector('[name="bank_account_number"]').value = option.dataset.bankNumber || '';
        form.querySelector('[name="bank_account_name"]').value = option.dataset.bankOwner || '';
    }

    function openBisyarahAdd(teacherId) {
        const form = document.getElementById('bisyarah-add-form');
        if (!form) return;

        form.reset();
        const select = form.querySelector('[data-teacher-select]');
        if (teacherId && select) {
            select.value = String(teacherId);
            fillTeacherAccount(form);
        }

        toggleBankFields(form);
        openBisyarahModal('bisyarah-modal-add');
        const amount = form.querySelector('[name="amount"]');
        if (amount) amount.focus();
    }

    document.querySelectorAll('[data-bisyarah-form]').forEach(function (form) {
        const method = form.querySelector('[data-payment-method]');
        if (method) {
            method.addEventListener('change', function () {
                toggleBankFields(form);
            });
        }

        const teacher = form.querySelector('[data-teacher-select]');
        if (teacher) {
            teacher.addEventListener('change', function () {
                fillTeacherAccount(form);
                toggleBankFields(form);
            });
        }

        toggleBankFields(form);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        document.querySelectorAll('[data-modal]:not(.hidden)').forEach(function (modal) {
            closeBisyarahModal(modal.id);
        });
    });

    document.querySelectorAll('[data-modal]').forEach(function (modal) {
        modal.addEventListener('click', function (event) {
            if (event.target === modal) closeBisyarahModal(modal.id);
        });
    });

    // Buka kembali modal yang gagal validasi agar isian old() terlihat.
    document.addEventListener('DOMContentLoaded', function () {
        const context = @json(old('_form_context'));
        if (!context) return;
        openBisyarahModal('bisyarah-modal-' + context);
    });
</script>
@endsection
