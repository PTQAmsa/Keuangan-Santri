@extends('layouts.app')

@section('title', 'Dashboard Wali Santri - ' . $student->nama_lengkap)

@section('content')
@php
    // Popup pengingat ganti PIN: disarankan bila PIN belum pernah diganti.
    // PIN yang wajib diganti sudah dialihkan middleware ke halaman ganti PIN.
    $forcePinChange = $student->must_change_pin;
    $suggestPinChange = ! $forcePinChange && $student->pin_changed_at === null;
@endphp
<div class="space-y-6">

    @if($forcePinChange)
        <div id="pinWarningBanner" class="hidden bg-amber-50 border border-amber-200 text-amber-900 text-xs rounded-xl p-3 flex items-center justify-between shadow-sm">
            <span><i class="fa-solid fa-triangle-exclamation mr-1.5"></i> PIN Anda belum diganti. Demi keamanan data keuangan, segera buat PIN baru.</span>
            <a href="{{ route('portal.change-pin') }}" class="font-bold underline whitespace-nowrap ml-3">Ganti PIN &rarr;</a>
        </div>
    @endif

    <!-- 1. Header Profil Santri Card -->
    <div class="bg-gradient-to-r from-brand-900 via-brand-800 to-blue-900 rounded-2xl p-6 text-white shadow-xl">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center space-x-4">
                <div class="w-16 h-16 rounded-2xl bg-white/10 border border-white/20 flex items-center justify-center text-3xl text-gold-400 shadow-inner">
                    <i class="fa-solid fa-user-graduate"></i>
                </div>
                <div>
                    <div class="flex items-center space-x-2">
                        <h1 class="text-xl sm:text-2xl font-bold tracking-tight">{{ $student->nama_lengkap }}</h1>
                        @if($student->diskon_beasiswa > 0)
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-400/30">
                                Penerima Beasiswa {{ $student->diskon_beasiswa }}%
                            </span>
                        @endif
                    </div>
                    <p class="text-xs sm:text-sm text-blue-200 mt-1 flex flex-wrap gap-x-4 gap-y-1">
                        <span><i class="fa-solid fa-id-badge mr-1"></i> NIS: <strong>{{ $student->nis }}</strong></span>
                        <span><i class="fa-solid fa-chalkboard-user mr-1"></i> Kelas: <strong>{{ $student->kelas }}</strong> &bull; Marhalah: <strong>{{ $student->kelompok }}</strong></span>
                        <span><i class="fa-solid fa-bed mr-1"></i> Asrama: <strong>{{ $student->kamar_asrama ?? 'Asrama Gadog' }}</strong></span>
                    </p>
                </div>
            </div>

            <!-- Identitas Wali Santri -->
            <div class="bg-white/10 rounded-xl px-4 py-3 border border-white/10 text-xs space-y-1 self-start md:self-auto">
                <p class="text-blue-200">Wali Santri:</p>
                <p class="font-semibold text-white text-sm">{{ $student->nama_wali }} ({{ $student->hubungan_wali === 'Lainnya' && $student->hubungan_wali_detail ? 'Lainnya: '.$student->hubungan_wali_detail : $student->hubungan_wali }})</p>
                <p class="text-gold-400"><i class="fa-brands fa-whatsapp mr-1"></i> {{ $student->no_whatsapp_wali }}</p>
            </div>
        </div>
    </div>

    <!-- 2. Ringkasan Status Keuangan -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <!-- Total Tunggakan -->
        <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Tagihan Belum Lunas</p>
                <p class="text-xl sm:text-2xl font-bold text-rose-600 mt-1">
                    Rp {{ number_format($totalTunggakan, 0, ',', '.') }}
                </p>
                <p class="text-[11px] text-gray-400 mt-0.5">{{ $activeInvoices->count() }} pos tagihan &bull; s.d. {{ $batasTunggakanLabel }}</p>
                <p class="text-[10px] text-gray-400 mt-1 leading-snug">{{ $batasTunggakanCatatan }}</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-file-invoice-dollar"></i>
            </div>
        </div>

        <!-- Total Pembayaran Selesai -->
        <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Dana Masuk</p>
                <p class="text-xl sm:text-2xl font-bold text-emerald-600 mt-1">
                    Rp {{ number_format($payments->whereNull('voided_at')->sum('amount_paid'), 0, ',', '.') }}
                </p>
                <p class="text-[11px] text-gray-400 mt-0.5">{{ $payments->whereNull('voided_at')->count() }} transaksi terekam</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-receipt"></i>
            </div>
        </div>

        <!-- Rekening Resmi Pesantren -->
        <div class="bg-gradient-to-br from-amber-50 to-orange-50 rounded-xl p-5 border border-amber-200/60 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-amber-800 uppercase tracking-wider">Rekening Resmi Pesantren</p>
                <p class="text-lg font-bold text-brand-900 mt-1">Bank Syariah Indonesia (BSI) 1202020212</p>
                <p class="text-[11px] text-amber-700">a/n Pesantren Tadabbur Al-Qur'an</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-200/50 text-amber-800 flex items-center justify-center text-xl">
                <i class="fa-solid fa-building-columns"></i>
            </div>
        </div>
    </div>

    <!-- 3. Daftar Tagihan Yang Belum Lunas (Active Invoices) -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 bg-gray-50/70 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-base font-bold text-gray-800 flex items-center">
                <i class="fa-solid fa-bell text-gold-500 mr-2"></i> Tagihan Aktif s.d. {{ $batasTunggakanLabel }}
            </h2>
            <span class="text-xs px-2.5 py-1 rounded-full bg-rose-100 text-rose-800 font-semibold">
                {{ $activeInvoices->count() }} Tagihan
            </span>
        </div>

        @if($activeInvoices->isEmpty())
            <div class="p-8 text-center text-gray-500">
                <div class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-500 mx-auto flex items-center justify-center text-xl mb-3">
                    <i class="fa-solid fa-check-double"></i>
                </div>
                <p class="font-semibold text-gray-700">Alhamdulillah! Tidak ada tagihan menunggak.</p>
                <p class="text-xs text-gray-400 mt-1">Seluruh tagihan ananda telah lunas terbayarkan.</p>
            </div>
        @else
            <div class="divide-y divide-gray-100">
                @foreach($activeInvoices as $inv)
                    <div class="p-5 sm:p-6 hover:bg-gray-50/50 transition">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="space-y-1">
                                <div class="flex items-center space-x-2">
                                    <span class="text-xs font-mono text-gray-400">{{ $inv->invoice_number }}</span>
                                    @if($inv->status === 'partial')
                                        <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-100 text-amber-800">
                                            Dicicil Sebagian
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-rose-100 text-rose-800">
                                            Belum Dibayar
                                        </span>
                                    @endif
                                </div>
                                <h3 class="text-base font-bold text-gray-900">{{ $inv->title }}</h3>
                                <p class="text-xs text-gray-500">
                                    @if($inv->isWakaf())
                                        <span class="italic">Pembayaran wakaf berlaku selama ananda menjadi santri</span>
                                    @else
                                        Jatuh Tempo: <strong>{{ $inv->due_date ? $inv->due_date->format('d M Y') : '-' }}</strong>
                                    @endif
                                    @if($inv->notes)
                                        • <span class="italic">{{ $inv->notes }}</span>
                                    @endif
                                </p>
                            </div>

                            <div class="sm:text-right space-y-1">
                                <div class="text-xs text-gray-500">Sisa yang harus dibayar:</div>
                                <div class="text-lg sm:text-xl font-extrabold text-rose-600">
                                    Rp {{ number_format($inv->remaining_amount, 0, ',', '.') }}
                                </div>
                                @if($inv->paid_amount > 0)
                                    <div class="text-[11px] text-gray-400">
                                        (Total tagihan: Rp {{ number_format($inv->amount, 0, ',', '.') }} | Sudah masuk: Rp {{ number_format($inv->paid_amount, 0, ',', '.') }})
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Panduan Pembayaran Singkat -->
                        <div class="mt-4 pt-3 border-t border-gray-100 flex flex-wrap items-center justify-between text-xs text-gray-500 gap-2">
                            <span>Metode: Transfer ke Bank Syariah Indonesia (BSI) 1202020212 a/n Pesantren Tadabbur Al-Qur'an</span>
                            <a href="https://wa.me/6285692253516?text={{ urlencode('Assalamu\'alaikum Admin PTQ Amsa, saya wali dari ' . $student->nama_lengkap . ' (NIS: ' . $student->nis . ') ingin konfirmasi pembayaran untuk tagihan: ' . $inv->title) }}" 
                               target="_blank" class="inline-flex items-center text-emerald-700 font-semibold hover:underline">
                                <i class="fa-brands fa-whatsapp text-sm mr-1"></i> Konfirmasi Bayar ke Admin
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- 4. Riwayat Transaksi & Bukti Kuitansi Digital -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 bg-gray-50/70 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-base font-bold text-gray-800 flex items-center">
                <i class="fa-solid fa-receipt text-brand-800 mr-2"></i> Riwayat Pembayaran & Kuitansi Digital
            </h2>
            <span class="text-xs text-gray-500">Total: {{ $payments->count() }} Kuitansi</span>
        </div>

        @if($payments->isEmpty())
            <div class="p-8 text-center text-gray-400 text-sm">
                Belum ada riwayat transaksi pembayaran.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs sm:text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-gray-600 uppercase text-[11px] font-semibold border-b border-gray-200">
                            <th class="py-3 px-4">No. Kuitansi</th>
                            <th class="py-3 px-4">Uraian Tagihan</th>
                            <th class="py-3 px-4">Nominal Masuk</th>
                            <th class="py-3 px-4">Tanggal Bayar</th>
                            <th class="py-3 px-4">Metode</th>
                            <th class="py-3 px-4 text-center">Aksi Dokumen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($payments as $pay)
                            <tr class="hover:bg-gray-50/50 {{ $pay->isVoided() ? 'opacity-60' : '' }}">
                                <td class="py-3.5 px-4 font-mono font-bold text-brand-900">
                                    {{ $pay->receipt->receipt_number ?? '-' }}
                                    @if($pay->isVoided())
                                        <span class="block mt-0.5 text-[10px] font-bold text-rose-700 bg-rose-100 px-1.5 py-0.5 rounded w-max">DIBATALKAN</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 font-medium text-gray-800">
                                    {{ $pay->invoice?->receipt_title ?? '-' }}
                                </td>
                                <td class="py-3.5 px-4 font-bold {{ $pay->isVoided() ? 'text-gray-400 line-through' : 'text-emerald-600' }}">
                                    Rp {{ number_format($pay->amount_paid, 0, ',', '.') }}
                                </td>
                                <td class="py-3.5 px-4 text-gray-600">
                                    {{ $pay->payment_date ? $pay->payment_date->format('d/m/Y') : '-' }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="px-2 py-0.5 rounded text-[11px] uppercase font-semibold bg-gray-100 text-gray-700">
                                        {{ $pay->payment_method }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    @if($pay->receipt)
                                        <a href="{{ route('receipt.download', $pay->receipt->id) }}" target="_blank" rel="noopener"
                                           class="inline-flex items-center px-2.5 py-1.5 bg-brand-900 hover:bg-brand-800 text-white rounded-lg text-xs font-semibold shadow-sm transition">
                                            <i class="fa-solid fa-file-pdf mr-1 text-gold-400"></i> Unduh PDF
                                        </a>
                                    @else
                                        <span class="text-gray-400 text-xs">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>

@if($forcePinChange || $suggestPinChange)
    <!-- Popup saran/wajib ganti PIN setelah login -->
    <div id="pinReminder" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full overflow-hidden border border-gray-100">
            <div class="bg-gradient-to-r from-brand-900 to-brand-800 px-6 py-5 text-white flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-gold-400 text-brand-900 flex items-center justify-center text-lg shadow">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <div>
                    <h3 class="font-bold text-base">{{ $forcePinChange ? 'Wajib Ganti PIN' : 'Amankan Akun Anda' }}</h3>
                    <p class="text-[11px] text-blue-200">
                        {{ $forcePinChange ? 'Anda login memakai nomor WhatsApp' : 'PIN Anda belum pernah diganti' }}
                    </p>
                </div>
            </div>

            <div class="p-6 space-y-4 text-xs text-gray-600">
                @if($forcePinChange)
                    <p>Demi keamanan data keuangan santri, Anda <strong>wajib membuat PIN baru</strong> sebelum memakai portal.</p>
                @else
                    <p>PIN Anda belum pernah diganti sejak diberikan petugas. Kami menyarankan Anda menggantinya dengan PIN 6 angka yang hanya Anda ketahui.</p>
                @endif

                <div class="p-3 bg-blue-50 border border-blue-100 rounded-xl text-[11px] text-blue-900">
                    <p class="font-semibold mb-1"><i class="fa-solid fa-circle-info mr-1"></i> Lupa PIN di kemudian hari?</p>
                    <p>Anda tetap bisa masuk dengan <strong>NIS + nomor WhatsApp wali</strong>, lalu membuat PIN baru. Bila nomor WA juga berganti, ajukan reset PIN ke Bendahara Pesantren.</p>
                </div>

                <div class="flex items-center justify-end space-x-2 pt-1">
                    <button type="button" onclick="dismissPinReminder()"
                            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-semibold transition">
                        Nanti Saja
                    </button>
                    <a href="{{ route('portal.change-pin') }}"
                       class="px-5 py-2 bg-brand-900 hover:bg-brand-800 text-white rounded-xl font-bold shadow-md transition inline-flex items-center">
                        <i class="fa-solid fa-lock mr-1.5"></i> {{ $forcePinChange ? 'Buat PIN Baru' : 'Ganti PIN Sekarang' }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // "Nanti Saja" menutup popup. Bila PIN wajib diganti, banner peringatan
        // tetap tampil di dashboard sampai wali benar-benar mengganti PIN.
        function dismissPinReminder() {
            document.getElementById('pinReminder').classList.add('hidden');
            const banner = document.getElementById('pinWarningBanner');
            if (banner) {
                banner.classList.remove('hidden');
            }
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    </script>
@endif
@endsection
