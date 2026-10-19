@extends('layouts.app')

@section('title', 'Verifikasi Keaslian Kuitansi - PTQ Amsa001')

@section('content')
<div class="max-w-2xl mx-auto my-6">

    @if($receipt)
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-emerald-100">
            <!-- Header Status Terverifikasi -->
            <div class="bg-emerald-600 text-white p-6 sm:p-8 text-center relative overflow-hidden">
                <div class="w-16 h-16 rounded-full bg-white/20 border-2 border-white mx-auto flex items-center justify-center text-3xl mb-3 shadow">
                    <i class="fa-solid fa-certificate"></i>
                </div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight">DOKUMEN RESMI TERVERIFIKASI</h1>
                <p class="text-xs sm:text-sm text-emerald-100 mt-1">
                    Kuitansi elektronik ini sah dan terdaftar dalam pangkalan data resmi PTQ Amsa001.
                </p>
            </div>

            <!-- Detail Dokumen -->
            <div class="p-6 sm:p-8 space-y-6">
                <!-- Info Nomor & Tanggal -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-gray-100 gap-2">
                    <div>
                        <span class="text-xs text-gray-400 uppercase tracking-wider font-semibold">Nomor Kuitansi</span>
                        <p class="text-lg font-mono font-bold text-brand-900">{{ $receipt->receipt_number }}</p>
                    </div>
                    <div class="sm:text-right">
                        <span class="text-xs text-gray-400 uppercase tracking-wider font-semibold">Waktu Penerbitan</span>
                        <p class="text-sm font-medium text-gray-700">{{ $receipt->issued_at->format('d F Y, H:i') }} WIB</p>
                    </div>
                </div>

                <!-- Rincian Transaksi -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs sm:text-sm">
                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 space-y-1">
                        <p class="text-gray-400 font-semibold uppercase text-[11px]">Identitas Santri</p>
                        <p class="font-bold text-gray-900 text-base">{{ $receipt->payment->student->nama_lengkap }}</p>
                        <p class="text-gray-600">NIS: <strong>{{ $receipt->payment->student->nis }}</strong></p>
                        <p class="text-gray-600">Kelas: <strong>{{ $receipt->payment->student->kelas }}</strong> ({{ $receipt->payment->student->jenjang }})</p>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 space-y-1">
                        <p class="text-gray-400 font-semibold uppercase text-[11px]">Telah Diterima Dari</p>
                        <p class="font-bold text-gray-900 text-base">{{ $receipt->payment->received_from ?: $receipt->payment->student->nama_wali }}</p>
                        <p class="text-gray-600">Metode: <span class="uppercase font-semibold">{{ $receipt->payment->payment_method }}</span></p>
                    </div>
                </div>

                <!-- Pos & Nominal Tagihan -->
                <div class="bg-blue-50/70 border border-blue-100 rounded-xl p-5 space-y-3">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="text-[11px] font-semibold text-brand-800 uppercase tracking-wider">Uraian Pembayaran</span>
                            <h3 class="text-base font-bold text-brand-900 mt-0.5">{{ $receipt->payment->invoice->receipt_title }}</h3>
                        </div>
                        <span class="px-2.5 py-1 rounded text-xs font-bold bg-emerald-100 text-emerald-800">
                            {{ $receipt->payment->invoice->status === 'paid' ? 'LUNAS' : 'DICICIL' }}
                        </span>
                    </div>

                    <div class="pt-3 border-t border-blue-200/60 flex items-center justify-between">
                        <span class="text-xs text-gray-600">Nominal yang Diterima:</span>
                        <span class="text-2xl font-extrabold text-brand-900">
                            Rp {{ number_format($receipt->payment->amount_paid, 0, ',', '.') }},-
                        </span>
                    </div>
                </div>

                <!-- Informasi Penerbit -->
                <div class="text-center text-xs text-gray-400 pt-2 space-y-1">
                    <p>Diterbitkan resmi oleh <strong>Bagian Keuangan Pesantren Tadabbur Al-Qur'an Amsa001</strong></p>
                    <p>Gadog, Megamendung, Kabupaten Bogor, Jawa Barat</p>
                    <p class="font-mono text-[10px]">Token Verifikasi: {{ $token }}</p>
                </div>

                <!-- Tombol Unduh PDF -->
                <div class="pt-2 text-center">
                    <a href="{{ route('receipt.download', $receipt->id) }}" target="_blank"
                       class="inline-flex items-center px-6 py-3 bg-brand-900 hover:bg-brand-800 text-white font-semibold rounded-xl shadow-md transition space-x-2 text-sm">
                        <i class="fa-solid fa-file-pdf text-gold-400"></i>
                        <span>Unduh Lembar Kuitansi PDF</span>
                    </a>
                </div>
            </div>
        </div>
    @else
        <!-- Token Tidak Valid -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-rose-100 p-8 text-center space-y-4">
            <div class="w-16 h-16 rounded-full bg-rose-50 text-rose-600 mx-auto flex items-center justify-center text-3xl">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <h1 class="text-xl font-bold text-gray-900">Kuitansi Tidak Ditemukan / Tidak Valid</h1>
            <p class="text-sm text-gray-600 max-w-md mx-auto">
                Nomor verifikasi kuitansi tidak terdaftar dalam basis data resmi PTQ Amsa001. Harap pastikan kembali keaslian dokumen atau hubungi admin keuangan.
            </p>
            <div class="pt-4">
                <a href="{{ url('/') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-xs font-semibold">
                    Kembali ke Beranda
                </a>
            </div>
        </div>
    @endif

</div>
@endsection
