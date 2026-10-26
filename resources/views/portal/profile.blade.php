@extends('layouts.app')

@section('title', 'Profil Santri - ' . $student->nama_lengkap)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Header Profil -->
    <div class="bg-gradient-to-r from-brand-900 via-brand-800 to-blue-900 rounded-2xl p-6 text-white shadow-xl">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center space-x-4">
                <div class="w-16 h-16 rounded-2xl bg-white/10 border border-white/20 flex items-center justify-center text-3xl text-gold-400 shadow-inner">
                    <i class="fa-solid fa-user-graduate"></i>
                </div>
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold tracking-tight">{{ $student->nama_lengkap }}</h1>
                    <p class="text-xs sm:text-sm text-blue-200 mt-1">
                        NIS <strong>{{ $student->nis }}</strong> &bull; Kelas {{ $student->kelas }} &bull; Marhalah {{ $student->kelompok }}
                    </p>
                    <span class="inline-flex items-center mt-2 px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $student->status === 'aktif' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-400/30' : 'bg-gray-500/20 text-gray-200 border border-gray-400/30' }}">
                        <i class="fa-solid fa-circle text-[6px] mr-1.5"></i> Santri {{ ucfirst($student->status) }}
                    </span>
                </div>
            </div>
            <a href="{{ route('portal.dashboard') }}" class="self-start sm:self-auto text-xs px-3 py-2 rounded-lg bg-white/10 hover:bg-white/20 border border-white/20 text-white font-semibold transition">
                <i class="fa-solid fa-arrow-left mr-1"></i> Kembali ke Dasbor
            </a>
        </div>
    </div>

    <!-- Identitas Santri -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-gray-50/70 border-b border-gray-100">
            <h2 class="text-base font-bold text-gray-800 flex items-center">
                <i class="fa-solid fa-id-card text-brand-800 mr-2"></i> Identitas Santri
            </h2>
        </div>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 p-6 text-sm">
            <div>
                <dt class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Nama Lengkap</dt>
                <dd class="font-semibold text-gray-900 mt-0.5">{{ $student->nama_lengkap }}</dd>
            </div>
            <div>
                <dt class="text-[11px] font-bold uppercase tracking-wider text-gray-400">NIS</dt>
                <dd class="font-semibold text-gray-900 mt-0.5">{{ $student->nis }}</dd>
            </div>
            <div>
                <dt class="text-[11px] font-bold uppercase tracking-wider text-gray-400">NISN</dt>
                <dd class="font-semibold text-gray-900 mt-0.5">{{ $student->nisn ?: '-' }}</dd>
            </div>
            <div>
                <dt class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Jenis Kelamin</dt>
                <dd class="font-semibold text-gray-900 mt-0.5">{{ $student->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</dd>
            </div>
            <div>
                <dt class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Marhalah</dt>
                <dd class="font-semibold text-gray-900 mt-0.5">{{ $student->kelompok }}</dd>
            </div>
            <div>
                <dt class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Kelas</dt>
                <dd class="font-semibold text-gray-900 mt-0.5">{{ $student->kelas }}</dd>
            </div>
            <div>
                <dt class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Kamar Asrama</dt>
                <dd class="font-semibold text-gray-900 mt-0.5">{{ $student->kamar_asrama ?: '-' }}</dd>
            </div>
            <div>
                <dt class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Tahun Masuk</dt>
                <dd class="font-semibold text-gray-900 mt-0.5">{{ $student->tahun_masuk }}</dd>
            </div>
            <div>
                <dt class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Kota Asal</dt>
                <dd class="font-semibold text-gray-900 mt-0.5">{{ $student->kota_asal ?: '-' }}</dd>
            </div>
            <div>
                <dt class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Alamat Lengkap</dt>
                <dd class="font-semibold text-gray-900 mt-0.5">{{ $student->alamat_lengkap ?: '-' }}</dd>
            </div>
            <div>
                <dt class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Beasiswa / Keringanan</dt>
                <dd class="font-semibold text-gray-900 mt-0.5">
                    @if($student->diskon_beasiswa > 0)
                        Beasiswa {{ $student->diskon_beasiswa }}%{{ $student->bebas_wakaf ? ' + Bebas Wakaf' : '' }}
                    @else
                        Reguler (Biaya Penuh)
                    @endif
                </dd>
            </div>
        </dl>
    </div>

    <!-- Data Wali -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-gray-50/70 border-b border-gray-100">
            <h2 class="text-base font-bold text-gray-800 flex items-center">
                <i class="fa-solid fa-users text-brand-800 mr-2"></i> Data Wali Santri
            </h2>
        </div>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 p-6 text-sm">
            <div>
                <dt class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Nama Wali</dt>
                <dd class="font-semibold text-gray-900 mt-0.5">{{ $student->nama_wali }}</dd>
            </div>
            <div>
                <dt class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Hubungan</dt>
                <dd class="font-semibold text-gray-900 mt-0.5">
                    {{ $student->hubungan_wali === 'Lainnya' && $student->hubungan_wali_detail ? 'Lainnya: '.$student->hubungan_wali_detail : $student->hubungan_wali }}
                </dd>
            </div>
            <div>
                <dt class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Nomor WhatsApp</dt>
                <dd class="font-semibold text-gray-900 mt-0.5">
                    <i class="fa-brands fa-whatsapp text-emerald-600 mr-1"></i>{{ $student->no_whatsapp_wali }}
                </dd>
            </div>
        </dl>
    </div>

    <!-- Keamanan Akun -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-base font-bold text-gray-800 flex items-center">
                <i class="fa-solid fa-shield-halved text-brand-800 mr-2"></i> Keamanan Akun
            </h2>
            <p class="text-xs text-gray-500 mt-1">
                PIN dipakai untuk masuk ke Portal Wali Santri.
                @if($student->pin_changed_at)
                    Terakhir diganti {{ $student->pin_changed_at->translatedFormat('d F Y') }}.
                @else
                    PIN belum pernah diganti sejak diberikan petugas.
                @endif
            </p>
        </div>
        <a href="{{ route('portal.change-pin') }}" class="inline-flex items-center justify-center px-4 py-2.5 bg-brand-900 hover:bg-brand-800 text-white rounded-xl text-xs font-bold shadow-sm transition whitespace-nowrap">
            <i class="fa-solid fa-lock mr-1.5"></i> Ganti PIN
        </a>
    </div>

</div>
@endsection
