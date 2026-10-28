@extends('layouts.app')

@section('title', 'Data Guru dan Staf - SIKA PTQ')

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-gray-200">
        <div>
            <div class="flex items-center space-x-2 text-xs text-gray-500 mb-1">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-brand-900">Admin</a>
                <span>/</span>
                <span class="text-gray-800 font-semibold">Data Guru dan Staf</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center">
                <i class="fa-solid fa-user-tie text-brand-800 mr-2.5"></i> Data Guru dan Staf
            </h1>
            <p class="text-xs sm:text-sm text-gray-500 mt-0.5">
                Data induk majelis kyai/nyai, guru, dan staf. Cukup diisi sekali, lalu dipakai setiap bulan untuk bisyarah.
            </p>
        </div>
        <div class="flex flex-col sm:flex-row sm:items-center gap-2">
            @include('admin.teachers._tabs')
            <button type="button" onclick="openTeacherModal('teacher-modal-add')"
                    class="inline-flex items-center justify-center px-4 py-2.5 bg-brand-900 hover:bg-brand-800 text-white rounded-xl text-xs font-bold shadow-sm transition whitespace-nowrap">
                <i class="fa-solid fa-plus mr-1.5"></i> Tambah Data Guru dan Staf
            </button>
        </div>
    </div>

    <!-- Ringkasan -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-brand-800 to-brand-900 rounded-2xl p-5 text-white shadow-md">
            <p class="text-xs font-medium text-blue-200 uppercase tracking-wider">Guru Aktif</p>
            <p class="text-3xl font-black mt-1">{{ $active }} <span class="text-sm font-medium text-blue-200">orang</span></p>
            <p class="text-xs text-blue-200 mt-2"><i class="fa-solid fa-chalkboard-user mr-1.5"></i>Siap dipilih setiap bulan</p>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-sm">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Guru Nonaktif</p>
            <p class="text-2xl font-extrabold text-gray-900 mt-1">{{ $inactive }} <span class="text-xs font-medium text-gray-400">orang</span></p>
            <p class="text-xs text-gray-400 mt-2">Riwayat bisyarah tetap tersimpan</p>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-sm">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">WhatsApp Aktif</p>
            <p class="text-2xl font-extrabold text-gray-900 mt-1">{{ $active - $missingWhatsApp }} <span class="text-xs font-medium text-gray-400">/ {{ $active }}</span></p>
            <p class="text-xs mt-2 {{ $missingWhatsApp > 0 ? 'text-amber-600 font-semibold' : 'text-gray-400' }}">
                @if($missingWhatsApp > 0)
                    {{ $missingWhatsApp }} guru belum punya nomor
                @else
                    Seluruh guru aktif sudah punya nomor
                @endif
            </p>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-sm">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Bisyarah {{ $currentPeriodLabel }}</p>
            <p class="text-2xl font-extrabold text-gray-900 mt-1">Rp {{ number_format($monthTotal, 0, ',', '.') }}</p>
            <p class="text-xs text-gray-400 mt-2">
                {{ $monthRecorded }} catatan tersimpan ·
                <a href="{{ route('admin.bisyarah') }}" class="text-brand-700 font-semibold hover:underline">Buka bisyarah</a>
            </p>
        </div>
    </div>

    @if($missingWhatsApp > 0)
        <div class="bg-amber-50 border-l-4 border-amber-400 p-4 rounded-r-lg shadow-sm flex items-start space-x-3">
            <i class="fa-solid fa-circle-info text-amber-600 text-lg mt-0.5"></i>
            <div class="text-xs sm:text-sm text-amber-900">
                <p class="font-semibold">{{ $missingWhatsApp }} guru aktif belum punya nomor WhatsApp.</p>
                <p class="mt-0.5">Lengkapi lewat tombol Ubah agar komunikasi bisyarah dan data guru tetap lengkap.</p>
            </div>
        </div>
    @endif

    <!-- Daftar Guru -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 bg-gray-50/70 border-b border-gray-100 flex flex-col lg:flex-row lg:items-center justify-between gap-3">
            <div>
                <h2 class="text-base font-bold text-gray-800 flex items-center">
                    <i class="fa-solid fa-list-ul text-brand-800 mr-2"></i> Daftar Guru
                </h2>
                <p class="text-xs text-gray-500 mt-0.5">{{ $teachers->count() }} baris ditampilkan. Data nonaktif tetap tersimpan.</p>
            </div>
            <form method="GET" action="{{ route('admin.teachers') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-gray-400 text-xs"></i>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama, asal, WhatsApp..."
                           class="w-full sm:w-60 text-xs rounded-xl border-gray-300 py-2 pl-8 pr-3 focus:ring-brand-800 focus:border-brand-800 bg-white">
                </div>
                <select name="status" class="text-xs rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800 focus:border-brand-800 bg-white">
                    <option value="">-- Semua Status --</option>
                    @foreach($statuses as $statusValue => $statusLabel)
                        <option value="{{ $statusValue }}" {{ $status === $statusValue ? 'selected' : '' }}>{{ $statusLabel }}</option>
                    @endforeach
                </select>
                <button type="submit" class="px-4 py-2 bg-brand-900 hover:bg-brand-800 text-white rounded-xl text-xs font-semibold transition">
                    Cari
                </button>
                @if($search !== '' || $status !== '')
                    <a href="{{ route('admin.teachers') }}" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl text-xs font-semibold text-center transition">
                        Reset
                    </a>
                @endif
            </form>
        </div>

        @if($teachers->isEmpty())
            <div class="p-12 text-center text-gray-400 text-sm">
                <i class="fa-solid fa-user-tie text-4xl text-gray-300 block mb-3"></i>
                <p class="font-semibold text-gray-500">Belum ada data guru yang cocok.</p>
                <p class="text-xs mt-1">Tambahkan data lewat tombol "Tambah Data Guru dan Staf" di atas.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs sm:text-sm">
                    <thead class="bg-gray-50 text-[11px] uppercase tracking-wider text-gray-500">
                        <tr>
                            <x-sort-th column="teacher" label="Guru" :sort-by="$sortBy" :sort-order="$sortOrder" class="px-5 py-3 font-semibold" />
                            <x-sort-th column="whatsapp" label="WhatsApp" :sort-by="$sortBy" :sort-order="$sortOrder" class="px-4 py-3 font-semibold" />
                            <x-sort-th column="status" label="Status" :sort-by="$sortBy" :sort-order="$sortOrder" class="px-4 py-3 font-semibold" />
                            <x-sort-th column="notes" label="Catatan" :sort-by="$sortBy" :sort-order="$sortOrder" class="px-4 py-3 font-semibold" />
                            <th class="px-5 py-3 font-semibold text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($teachers as $teacher)
                            @php
                                $statusBadge = match ($teacher->status) {
                                    'pengasuh' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                    'guru_tetap' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'guru_tidak_tetap' => 'bg-amber-50 text-amber-700 border-amber-200',
                                    default => 'bg-cyan-50 text-cyan-700 border-cyan-200',
                                };
                                $wa = \App\Models\Teacher::normalizeWhatsApp($teacher->no_whatsapp);
                            @endphp
                            <tr class="hover:bg-gray-50/60 transition {{ $teacher->is_active ? '' : 'opacity-60' }}">
                                <td class="px-5 py-3.5">
                                    <p class="font-semibold text-gray-900">{{ $teacher->nama_lengkap }}</p>
                                    <p class="text-[11px] text-gray-500 mt-0.5">
                                        <i class="fa-solid fa-location-dot mr-1 text-gray-400"></i>{{ $teacher->kota_asal }}
                                        @unless($teacher->is_active)
                                            <span class="ml-2 inline-flex px-1.5 py-0.5 rounded bg-gray-100 text-gray-600 border border-gray-200 text-[10px] font-semibold">Nonaktif</span>
                                        @endunless
                                    </p>
                                    @if(! $teacher->is_active && $teacher->inactive_reason)
                                        <p class="text-[11px] text-rose-600 mt-1">
                                            <i class="fa-solid fa-user-slash mr-1"></i>{{ $teacher->inactive_reason }}
                                            @if($teacher->inactivated_at)
                                                <span class="text-gray-400">· {{ $teacher->inactivated_at->translatedFormat('d M Y') }}</span>
                                            @endif
                                        </p>
                                    @endif
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
                                    <span class="inline-flex px-2 py-0.5 rounded-full border text-[11px] font-semibold {{ $statusBadge }}">
                                        {{ $teacher->statusLabel() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 max-w-[18rem]">
                                    <p class="text-xs text-gray-600 line-clamp-2">{{ $teacher->notes ?: '—' }}</p>
                                </td>
                                <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center space-x-1.5">
                                        <button type="button" onclick="openTeacherModal('teacher-modal-edit-{{ $teacher->id }}')"
                                                class="px-2.5 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 rounded-lg text-[11px] font-semibold transition">
                                            <i class="fa-solid fa-pen-to-square mr-1"></i> Ubah
                                        </button>
                                        <form method="POST" action="{{ route('admin.teachers.toggle', $teacher) }}"
                                              @if($teacher->is_active)
                                                  onsubmit="const reason = prompt('Alasan menonaktifkan {{ addslashes($teacher->nama_lengkap) }}:\n(misal: pindah tugas, berhenti mengajar, cuti panjang)'); if (!reason || !reason.trim()) { return false; } this.inactive_reason.value = reason.trim(); return true;"
                                              @else
                                                  onsubmit="return confirm('Aktifkan kembali data guru {{ addslashes($teacher->nama_lengkap) }}?')"
                                              @endif>
                                            @csrf
                                            @method('PATCH')
                                            @if($teacher->is_active)
                                                <input type="hidden" name="inactive_reason" value="">
                                            @endif
                                            <button type="submit" class="px-2.5 py-1.5 rounded-lg text-[11px] font-semibold border transition {{ $teacher->is_active ? 'bg-gray-50 hover:bg-gray-100 text-gray-600 border-gray-200' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border-emerald-200' }}">
                                                <i class="fa-solid {{ $teacher->is_active ? 'fa-user-slash' : 'fa-user-check' }} mr-1"></i>
                                                {{ $teacher->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>
                                    </div>
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
        <span>Nominal dan metode bisyarah dicatat terpisah setiap bulan di tab <a href="{{ route('admin.bisyarah') }}" class="text-brand-800 font-semibold hover:underline">Bisyarah Bulanan</a>, sehingga data induk tidak perlu diubah lagi.</span>
    </p>
</div>

<!-- Modal Tambah Guru -->
<div id="teacher-modal-add" data-modal class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm p-4 flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white z-10">
            <div>
                <h2 class="text-base font-bold text-gray-900"><i class="fa-solid fa-user-plus text-brand-800 mr-2"></i>Tambah Data Guru dan Staf</h2>
                <p class="text-xs text-gray-500 mt-0.5">Isi identitas dan kontak. Nominal bisyarah diisi terpisah setiap bulan.</p>
            </div>
            <button type="button" onclick="closeTeacherModal('teacher-modal-add')" class="w-8 h-8 rounded-lg hover:bg-gray-100 text-gray-500" aria-label="Tutup">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form method="POST" action="{{ route('admin.teachers.store') }}" class="p-6 space-y-5">
            @csrf
            @include('admin.teachers._form', ['teacher' => null, 'context' => 'add', 'statuses' => $statuses])
            <div class="flex items-center justify-end space-x-2 pt-2 border-t border-gray-100">
                <button type="button" onclick="closeTeacherModal('teacher-modal-add')" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-sm font-semibold transition">Batal</button>
                <button type="submit" class="px-5 py-2.5 bg-brand-900 hover:bg-brand-800 text-white rounded-xl text-sm font-semibold shadow-sm transition">
                    <i class="fa-solid fa-save mr-1.5"></i> Simpan Data Guru dan Staf
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Ubah per Guru -->
@foreach($teachers as $teacher)
    <div id="teacher-modal-edit-{{ $teacher->id }}" data-modal class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm p-4 flex items-center justify-center">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white z-10">
                <div>
                    <h2 class="text-base font-bold text-gray-900"><i class="fa-solid fa-pen-to-square text-amber-600 mr-2"></i>Ubah Data Guru dan Staf</h2>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $teacher->nama_lengkap }}</p>
                </div>
                <button type="button" onclick="closeTeacherModal('teacher-modal-edit-{{ $teacher->id }}')" class="w-8 h-8 rounded-lg hover:bg-gray-100 text-gray-500" aria-label="Tutup">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('admin.teachers.update', $teacher) }}" class="p-6 space-y-5">
                @csrf
                @method('PATCH')
                @include('admin.teachers._form', ['teacher' => $teacher, 'context' => 'edit-'.$teacher->id, 'statuses' => $statuses])
                <div class="flex items-center justify-end space-x-2 pt-2 border-t border-gray-100">
                    <button type="button" onclick="closeTeacherModal('teacher-modal-edit-{{ $teacher->id }}')" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-sm font-semibold transition">Batal</button>
                    <button type="submit" class="px-5 py-2.5 bg-brand-900 hover:bg-brand-800 text-white rounded-xl text-sm font-semibold shadow-sm transition">
                        <i class="fa-solid fa-save mr-1.5"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
@endforeach

<script>
    function openTeacherModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function closeTeacherModal(id) {
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
            closeTeacherModal(modal.id);
        });
    });

    document.querySelectorAll('[data-modal]').forEach(function (modal) {
        modal.addEventListener('click', function (event) {
            if (event.target === modal) closeTeacherModal(modal.id);
        });
    });

    // Buka kembali modal yang gagal validasi agar isian old() terlihat,
    // atau buka modal tambah bila datang dari menu "Tambah Guru" (?tambah=1).
    document.addEventListener('DOMContentLoaded', function () {
        const context = @json(old('_form_context') ?: (request()->boolean('tambah') ? 'add' : null));
        if (context) openTeacherModal('teacher-modal-' + context);
    });
</script>
@endsection
