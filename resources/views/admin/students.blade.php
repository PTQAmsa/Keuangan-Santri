@extends('layouts.app')

@section('title', 'Data Santri & Status Keuangan - PTQ Amsa001')

@section('content')
<div class="space-y-6">

    <!-- Header & Tombol Tambah Santri -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-gray-200">
        <div>
            <div class="flex items-center space-x-2 text-xs text-gray-500 mb-1">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-brand-900">Admin</a>
                <span>/</span>
                <a href="{{ route('admin.invoices') }}" class="hover:text-brand-900">Tagihan</a>
                <span>/</span>
                <span class="text-gray-800 font-semibold">Data Santri</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center">
                <i class="fa-solid fa-user-graduate text-brand-800 mr-2.5"></i> Data Santri &amp; Status Keuangan
            </h1>
            <p class="text-xs sm:text-sm text-gray-500 mt-0.5">
                Tambahkan santri baru yang masuk, atau nonaktifkan santri yang sudah keluar pondok
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button type="button" onclick="openStudentModal()" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-sm transition flex items-center">
                <i class="fa-solid fa-user-plus mr-1.5 text-sm"></i> Tambah Santri Baru
            </button>
        </div>
    </div>

    <!-- Ringkasan Statistik -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-sm">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Santri Aktif</p>
            <p class="text-2xl font-extrabold text-emerald-700 mt-1">{{ $totalAktif }}</p>
            <p class="text-xs text-gray-400 mt-1">Mendapat tagihan rutin bulanan</p>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-sm">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Santri Putra</p>
            <p class="text-2xl font-extrabold text-blue-700 mt-1">{{ $totalPutra }}</p>
            <p class="text-xs text-gray-400 mt-1">Seluruh santri marhalah putra</p>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-sm">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Santri Putri</p>
            <p class="text-2xl font-extrabold text-fuchsia-700 mt-1">{{ $totalPutri }}</p>
            <p class="text-xs text-gray-400 mt-1">Seluruh santri marhalah putri</p>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-sm">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Tidak Aktif / Keluar</p>
            <p class="text-2xl font-extrabold text-gray-700 mt-1">{{ $totalNonaktif }}</p>
            <p class="text-xs text-gray-400 mt-1">Tidak lagi dibuatkan tagihan baru</p>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-sm">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Penerima Beasiswa</p>
            <p class="text-2xl font-extrabold text-brand-900 mt-1">{{ $totalBeasiswa }}</p>
            <p class="text-xs text-gray-400 mt-1">Santri dengan potongan SPP</p>
        </div>
    </div>

    <!-- Filter & Pencarian -->
    <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
        <form action="{{ route('admin.students') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end text-xs">
            <div>
                <label class="block text-[11px] font-semibold text-gray-500 uppercase mb-1">Cari Santri / NIS / Wali</label>
                <div class="relative">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Nama, NIS, kelas, atau wali..."
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
                <label class="block text-[11px] font-semibold text-gray-500 uppercase mb-1">Status Santri</label>
                <select name="status" class="w-full rounded-xl border-gray-300 py-1.5 px-3 bg-gray-50 text-xs">
                    <option value="">-- Semua Status --</option>
                    <option value="aktif" {{ $status === 'aktif' ? 'selected' : '' }}>Aktif</option>
                    <option value="nonaktif" {{ $status === 'nonaktif' ? 'selected' : '' }}>Nonaktif (Keluar)</option>
                    <option value="lulus" {{ $status === 'lulus' ? 'selected' : '' }}>Lulus</option>
                    <option value="pindah" {{ $status === 'pindah' ? 'selected' : '' }}>Pindah</option>
                </select>
            </div>

            <div class="flex items-center space-x-2">
                <button type="submit" class="py-2 px-4 bg-brand-900 hover:bg-brand-800 text-white rounded-xl text-xs font-semibold shadow-sm transition">
                    <i class="fa-solid fa-filter mr-1"></i> Terapkan
                </button>
                <a href="{{ route('admin.students') }}" class="py-2 px-3 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl text-xs font-semibold transition" title="Reset Filter">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Tabel Data Santri -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200/80 overflow-hidden">
        <div class="p-4 sm:p-5 bg-gray-50/70 border-b border-gray-200 flex items-center justify-between">
            <div>
                <h2 class="text-sm font-bold text-gray-900">Daftar Santri</h2>
                <p class="text-xs text-gray-500">Menampilkan {{ $students->count() }} santri</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-gray-600">
                <thead class="bg-gray-100 text-gray-700 font-bold uppercase text-[11px] tracking-wider border-b border-gray-200">
                    <tr>
                        <th class="py-3 px-4 text-center">No</th>
                        <x-sort-th column="student" label="Santri" :sort-by="$sortBy" :sort-order="$sortOrder" class="py-3 px-4" />
                        <x-sort-th column="kelas" label="Marhalah, Kelas & Kamar" :sort-by="$sortBy" :sort-order="$sortOrder" class="py-3 px-4" />
                        <x-sort-th column="wali" label="Wali Santri & Kontak" :sort-by="$sortBy" :sort-order="$sortOrder" class="py-3 px-4" />
                        <x-sort-th column="beasiswa" label="Beasiswa" :sort-by="$sortBy" :sort-order="$sortOrder" align="center" class="py-3 px-4" />
                        <x-sort-th column="sisa_tagihan" label="Sisa Tagihan" :sort-by="$sortBy" :sort-order="$sortOrder" align="right" class="py-3 px-4" />
                        <x-sort-th column="status" label="Status" :sort-by="$sortBy" :sort-order="$sortOrder" align="center" class="py-3 px-4" />
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($students as $s)
                        @php
                            $tunggakan = $s->invoices->sum(fn ($inv) => $inv->remaining_amount);
                            $statusLabel = match ($s->status) {
                                'aktif' => 'Aktif',
                                'nonaktif' => 'Nonaktif (Keluar)',
                                'lulus' => 'Lulus',
                                'pindah' => 'Pindah',
                                default => ucfirst($s->status),
                            };
                        @endphp
                        <tr class="hover:bg-gray-50/60 transition">
                            <td class="py-3 px-4 text-center text-gray-400 font-mono">{{ $loop->iteration }}</td>
                            <td class="py-3 px-4">
                                <p class="font-bold text-gray-900">{{ $s->nama_lengkap }}</p>
                                <p class="text-[11px] text-gray-400 font-mono">NIS: {{ $s->nis }}</p>
                            </td>
                            <td class="py-3 px-4">
                                <p class="font-semibold text-gray-700 flex items-center gap-1.5">
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold {{ $s->kelompok_badge }}">{{ $s->kelompok }}</span>
                                    <span>{{ $s->kelas }}</span>
                                </p>
                                <p class="text-[11px] text-gray-400">{{ $s->kamar_asrama ?: 'Belum ada kamar' }}</p>
                            </td>
                            <td class="py-3 px-4">
                                <p class="font-semibold text-gray-800">{{ $s->nama_wali }}</p>
                                <p class="text-[11px] text-gray-500 font-mono">{{ $s->no_whatsapp_wali }} ({{ $s->hubungan_wali === 'Lainnya' && $s->hubungan_wali_detail ? 'Lainnya: '.$s->hubungan_wali_detail : $s->hubungan_wali }})</p>
                                @if($s->pin_reset_requested_at)
                                    <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                        <i class="fa-solid fa-key mr-1"></i> Minta reset PIN
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if((float) $s->diskon_beasiswa > 0)
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-semibold bg-blue-50 text-blue-700">
                                        {{ number_format((float) $s->diskon_beasiswa, 0) }}%
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                                @if($s->bebas_wakaf)
                                    <span class="inline-block mt-1 px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700">
                                        Bebas Wakaf
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right font-bold {{ $tunggakan > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                Rp {{ number_format($tunggakan, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if($s->status === 'aktif')
                                    <span class="inline-block px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                        {{ $statusLabel }}
                                    </span>
                                @else
                                    <span class="inline-block px-2.5 py-1 rounded-full text-[10px] font-bold bg-gray-200 text-gray-700">
                                        {{ $statusLabel }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                <div class="inline-flex items-center space-x-1.5">
                                    <a href="{{ route('admin.students.card', $s) }}"
                                       class="inline-flex items-center px-2.5 py-1.5 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-800 text-[11px] font-semibold transition border border-blue-200"
                                       title="Lihat kartu keuangan santri">
                                        <i class="fa-solid fa-address-card mr-1"></i> Kartu
                                    </a>

                                    <form action="{{ route('admin.students.reset-pin', $s) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Reset PIN portal {{ $s->nama_lengkap }} ke 123456? Wali akan diminta mengganti PIN saat login berikutnya.');">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center px-2.5 py-1.5 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-800 text-[11px] font-semibold transition border border-amber-200" title="Reset PIN portal wali">
                                            <i class="fa-solid fa-key mr-1"></i> Reset PIN
                                        </button>
                                    </form>

                                    <form action="{{ route('admin.students.toggle', $s) }}" method="POST" class="inline"
                                          onsubmit="return confirm('{{ $s->status === 'aktif'
                                                ? 'Nonaktifkan santri '.$s->nama_lengkap.'? Santri ini tidak akan dibuatkan tagihan baru lagi (mis. SPP bulanan), namun seluruh tagihan & kuitansi lama tetap tercatat.'
                                                : 'Aktifkan kembali santri '.$s->nama_lengkap.'?' }}');">
                                        @csrf
                                        @method('PATCH')
                                        @if($s->status === 'aktif')
                                            <button type="submit" class="inline-flex items-center px-3 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 text-[11px] font-semibold transition border border-rose-200">
                                                <i class="fa-solid fa-user-slash mr-1"></i> Nonaktifkan
                                            </button>
                                        @else
                                            <button type="submit" class="inline-flex items-center px-3 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-[11px] font-semibold transition border border-emerald-200">
                                                <i class="fa-solid fa-user-check mr-1"></i> Aktifkan
                                            </button>
                                        @endif
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-gray-400">
                                <i class="fa-solid fa-user-slash text-4xl mb-2 text-gray-300 block"></i>
                                Tidak ada data santri yang cocok dengan filter ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal Form Tambah Santri Baru (Santri Masuk) -->
<div id="studentModal" class="fixed inset-0 z-50 hidden bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl max-w-3xl w-full max-h-[92vh] flex flex-col overflow-hidden border border-gray-100">
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-brand-900 to-brand-800 px-6 py-5 text-white flex items-center justify-between">
            <div class="flex items-center space-x-2.5">
                <div class="w-9 h-9 rounded-xl bg-gold-400 text-brand-900 font-bold flex items-center justify-center text-sm shadow">
                    <i class="fa-solid fa-user-plus"></i>
                </div>
                <div>
                    <h3 class="font-bold text-base">Tambah Santri Baru</h3>
                    <p class="text-xs text-blue-200">Data santri yang masuk pondok beserta ketentuan keuangannya</p>
                </div>
            </div>
            <button type="button" onclick="closeStudentModal()" class="text-gray-300 hover:text-white text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Modal Form -->
        <form action="{{ route('admin.students.store') }}" method="POST" class="p-6 space-y-5 text-xs overflow-y-auto">
            @csrf

            <!-- Data Diri Santri -->
            <div>
                <p class="font-bold text-gray-700 uppercase mb-2 text-[11px] tracking-wider">1. Data Diri Santri</p>
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <div>
                        <label class="block font-semibold text-gray-600 mb-1">NIS <span class="text-rose-500">*</span></label>
                        <input type="text" name="nis" value="{{ old('nis') }}" required placeholder="202601009"
                               class="w-full text-xs rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-600 mb-1">NISN (Opsional)</label>
                        <input type="text" name="nisn" value="{{ old('nisn') }}" placeholder="0098765439"
                               class="w-full text-xs rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-600 mb-1">Jenis Kelamin <span class="text-rose-500">*</span></label>
                        <select name="jenis_kelamin" required onchange="syncSantriGroup(this.value)"
                                class="w-full text-xs rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800">
                            <option value="L" {{ old('jenis_kelamin') === 'L' ? 'selected' : '' }}>Laki-laki</option>
                            <option value="P" {{ old('jenis_kelamin') === 'P' ? 'selected' : '' }}>Perempuan</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-600 mb-1">Marhalah Santri <span class="text-rose-500">*</span></label>
                        <select name="santri_group" id="santriGroup" required class="w-full text-xs rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800">
                            <option value="putra" {{ old('santri_group', 'putra') === 'putra' ? 'selected' : '' }}>Putra</option>
                            <option value="putri" {{ old('santri_group') === 'putri' ? 'selected' : '' }}>Putri</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-3">
                    <div class="sm:col-span-2">
                        <label class="block font-semibold text-gray-600 mb-1">Nama Lengkap Santri <span class="text-rose-500">*</span></label>
                        <input type="text" name="nama_lengkap" value="{{ old('nama_lengkap') }}" required placeholder="Contoh: Ahmad Fauzan"
                               class="w-full text-xs rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-600 mb-1">Kota Asal</label>
                        <input type="text" name="kota_asal" value="{{ old('kota_asal') }}" placeholder="Bogor"
                               class="w-full text-xs rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800">
                    </div>
                </div>
            </div>

            <!-- Data Akademik -->
            <div>
                <p class="font-bold text-gray-700 uppercase mb-2 text-[11px] tracking-wider">2. Kelas &amp; Status</p>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block font-semibold text-gray-600 mb-1">Kelas <span class="text-rose-500">*</span></label>
                        <input type="text" name="kelas" value="{{ old('kelas') }}" required placeholder="7A / 7 Putri"
                               class="w-full text-xs rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-600 mb-1">Tahun Masuk <span class="text-rose-500">*</span></label>
                        <input type="number" name="tahun_masuk" value="{{ old('tahun_masuk', now()->year) }}" required min="2000" max="2100"
                               class="w-full text-xs rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-600 mb-1">Status Keuangan <span class="text-rose-500">*</span></label>
                        <select name="status" required class="w-full text-xs rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800">
                            <option value="aktif" {{ old('status', 'aktif') === 'aktif' ? 'selected' : '' }}>Aktif</option>
                            <option value="nonaktif" {{ old('status') === 'nonaktif' ? 'selected' : '' }}>Nonaktif (Keluar)</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3">
                    <div>
                        <label class="block font-semibold text-gray-600 mb-1">Kamar Asrama</label>
                        <input type="text" name="kamar_asrama" value="{{ old('kamar_asrama') }}" placeholder="Gedung Amsa - Kamar 03"
                               class="w-full text-xs rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-600 mb-1">PIN Portal Wali (angka) <span class="text-rose-500">*</span></label>
                        <input type="text" name="pin_portal" value="{{ old('pin_portal', '123456') }}" required maxlength="10"
                               class="w-full text-xs rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800">
                    </div>
                </div>
            </div>

            <!-- Data Wali -->
            <div>
                <p class="font-bold text-gray-700 uppercase mb-2 text-[11px] tracking-wider">3. Data Wali Santri</p>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block font-semibold text-gray-600 mb-1">Nama Wali <span class="text-rose-500">*</span></label>
                        <input type="text" name="nama_wali" value="{{ old('nama_wali') }}" required placeholder="Bpk. Ahmad Subandi"
                               class="w-full text-xs rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-600 mb-1">No. WhatsApp Wali <span class="text-rose-500">*</span></label>
                        <input type="text" name="no_whatsapp_wali" value="{{ old('no_whatsapp_wali') }}" required placeholder="081234567890"
                               class="w-full text-xs rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-600 mb-1">Hubungan <span class="text-rose-500">*</span></label>
                        <select name="hubungan_wali" id="hubunganWali" required onchange="syncHubunganDetail()" class="w-full text-xs rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800">
                            @foreach(['Ayah Kandung', 'Ibu Kandung', 'Ayah Angkat', 'Ibu Angkat', 'Wali', 'Lainnya'] as $hubungan)
                                <option value="{{ $hubungan }}" {{ old('hubungan_wali', 'Ayah Kandung') === $hubungan ? 'selected' : '' }}>{{ $hubungan }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div id="hubunganWaliDetailWrap" class="{{ old('hubungan_wali') === 'Lainnya' ? '' : 'hidden' }} sm:col-span-3">
                        <label class="block font-semibold text-gray-600 mb-1">Keterangan Hubungan <span class="text-rose-500">*</span></label>
                        <input type="text" name="hubungan_wali_detail" id="hubunganWaliDetail" value="{{ old('hubungan_wali_detail') }}"
                               placeholder="Contoh: Kakek dari pihak ayah, Paman, Kakak kandung, Yayasan..."
                               class="w-full text-xs rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800">
                        <p class="text-[10px] text-gray-400 mt-1">Wajib diisi bila Anda memilih hubungan <strong>Lainnya</strong>, agar keterangan hubungan wali lebih jelas.</p>
                    </div>
                </div>
            </div>

            <!-- Ketentuan Keuangan -->
            <div>
                <p class="font-bold text-gray-700 uppercase mb-2 text-[11px] tracking-wider">4. Ketentuan Keuangan</p>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                    <div>
                        <label class="block font-semibold text-gray-600 mb-1">Potongan Beasiswa (%)</label>
                        <input type="number" name="diskon_beasiswa" value="{{ old('diskon_beasiswa', 0) }}" min="0" max="100" step="0.01"
                               class="w-full text-xs rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800">
                    </div>
                    <label class="flex items-center p-2.5 rounded-xl border border-gray-200 cursor-pointer hover:bg-gray-50">
                        <input type="checkbox" name="bebas_wakaf" value="1" {{ old('bebas_wakaf') ? 'checked' : '' }}
                               class="rounded text-brand-900 focus:ring-brand-800">
                        <span class="ml-2 font-semibold text-gray-800">Bebas kewajiban wakaf</span>
                    </label>
                </div>
            </div>

            <div class="p-3 bg-blue-50 rounded-xl border border-blue-100 flex items-start space-x-2 text-[11px] text-blue-800">
                <i class="fa-solid fa-circle-info text-blue-600 mt-0.5"></i>
                <p>Santri berstatus <strong>Aktif</strong> akan menerima tagihan rutin (SPP bulanan) dan bisa masuk ke Portal Wali Santri memakai NIS + PIN di atas.</p>
            </div>

            <!-- Modal Action Buttons -->
            <div class="flex items-center justify-end space-x-2 pt-2 border-t border-gray-100">
                <button type="button" onclick="closeStudentModal()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-semibold transition">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 bg-brand-900 hover:bg-brand-800 text-white rounded-xl font-bold shadow-md transition flex items-center">
                    <i class="fa-solid fa-floppy-disk mr-1.5 text-xs"></i> Simpan Data Santri
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openStudentModal() {
        document.getElementById('studentModal').classList.remove('hidden');
    }

    function closeStudentModal() {
        document.getElementById('studentModal').classList.add('hidden');
    }

    // Marhalah santri mengikuti jenis kelamin, tetapi petugas tetap bisa mengubahnya.
    function syncSantriGroup(jenisKelamin) {
        document.getElementById('santriGroup').value = jenisKelamin === 'P' ? 'putri' : 'putra';
    }

    // Tampilkan kolom keterangan hubungan hanya bila wali memilih "Lainnya".
    function syncHubunganDetail() {
        const nilai = document.getElementById('hubunganWali').value;
        const wrap = document.getElementById('hubunganWaliDetailWrap');
        const input = document.getElementById('hubunganWaliDetail');

        if (nilai === 'Lainnya') {
            wrap.classList.remove('hidden');
            input.setAttribute('required', 'required');
        } else {
            wrap.classList.add('hidden');
            input.removeAttribute('required');
            input.value = '';
        }
    }

    document.addEventListener('DOMContentLoaded', syncHubunganDetail);

    // Bila validasi gagal atau dibuka dari menu "Tambah Santri Baru" (?tambah=1),
    // modal langsung dibuka agar petugas bisa langsung mengisi.
    @if($errors->any() || request()->boolean('tambah'))
        document.addEventListener('DOMContentLoaded', openStudentModal);
    @endif
</script>
@endsection
