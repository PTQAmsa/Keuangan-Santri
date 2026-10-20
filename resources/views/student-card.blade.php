@extends('layouts.app')

@section('title', 'Kartu Keuangan - ' . $student->nama_lengkap)

@section('content')
@php
    $isPortal = request()->routeIs('portal.card');
    $backUrl = $isPortal ? route('portal.dashboard') : route('admin.students');
    $pdfUrl = $isPortal ? route('portal.card.pdf') : route('admin.students.card.pdf', $student);
    $statusLabel = match ($student->status) {
        'aktif' => 'Aktif',
        'lulus' => 'Lulus',
        'pindah' => 'Pindah',
        'nonaktif' => 'Nonaktif',
        default => ucfirst($student->status),
    };
    $hubunganWali = $student->hubungan_wali === 'Lainnya' && $student->hubungan_wali_detail
        ? 'Lainnya: ' . $student->hubungan_wali_detail
        : $student->hubungan_wali;
@endphp

<div class="space-y-6">
    <div class="no-print flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <a href="{{ $backUrl }}" class="inline-flex items-center text-sm font-semibold text-brand-900 hover:underline">
            <i class="fa-solid fa-arrow-left mr-2"></i> Kembali
        </a>
        <div class="flex items-center gap-2">
            <a href="{{ $pdfUrl }}" target="_blank"
               class="inline-flex items-center px-4 py-2 bg-brand-900 hover:bg-brand-800 text-white rounded-xl text-sm font-semibold shadow-sm transition">
                <i class="fa-solid fa-file-pdf mr-2 text-gold-400"></i> Unduh PDF
            </a>
            <button type="button" onclick="window.print()"
                    class="inline-flex items-center px-4 py-2 bg-white hover:bg-gray-50 text-brand-900 border border-gray-200 rounded-xl text-sm font-semibold shadow-sm transition">
                <i class="fa-solid fa-print mr-2"></i> Cetak
            </button>
        </div>
    </div>

    <div class="print-card bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="bg-gradient-to-r from-brand-900 via-brand-800 to-blue-900 px-6 py-5 text-white border-b-4 border-gold-400">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-white p-1.5 shadow">
                        <img src="{{ asset('images/logo-pesantren.png') }}" alt="Logo PTQ Amsa001" class="w-full h-full object-contain">
                    </div>
                    <div>
                        <p class="text-[11px] uppercase tracking-widest text-gold-300 font-bold">Pesantren Tadabbur Al-Qur'an Amsa001</p>
                        <h1 class="text-xl sm:text-2xl font-bold tracking-tight">Kartu Keuangan Santri</h1>
                        <p class="text-xs text-blue-200 mt-0.5">Satuan Pendidikan Muadalah Mu'allimien | Putra-Putri</p>
                    </div>
                </div>
                <div class="text-xs text-blue-200 sm:text-right">
                    <p>Dicetak: <strong class="text-white">{{ now()->format('d/m/Y H:i') }}</strong></p>
                    <p class="mt-1">NIS: <strong class="text-gold-300">{{ $student->nis }}</strong></p>
                </div>
            </div>
        </div>

        <div class="p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm border-b border-gray-100">
            <div>
                <p class="text-[11px] uppercase tracking-wider text-gray-400 font-semibold">Nama Santri</p>
                <p class="font-bold text-gray-900 mt-0.5">{{ $student->nama_lengkap }}</p>
            </div>
            <div>
                <p class="text-[11px] uppercase tracking-wider text-gray-400 font-semibold">Kelas / Marhalah</p>
                <p class="font-semibold text-gray-800 mt-0.5">{{ $student->kelas }} / {{ $student->kelompok }}</p>
            </div>
            <div>
                <p class="text-[11px] uppercase tracking-wider text-gray-400 font-semibold">Wali Santri</p>
                <p class="font-semibold text-gray-800 mt-0.5">{{ $student->nama_wali }}</p>
                <p class="text-[11px] text-gray-500">{{ $hubunganWali }} &bull; {{ $student->no_whatsapp_wali }}</p>
            </div>
            <div>
                <p class="text-[11px] uppercase tracking-wider text-gray-400 font-semibold">Status Santri</p>
                <p class="font-semibold text-gray-800 mt-0.5">{{ $statusLabel }}</p>
                <p class="text-[11px] text-gray-500">{{ $student->kamar_asrama ?: 'Asrama belum diisi' }}</p>
            </div>
        </div>

        <div class="p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="rounded-xl border border-blue-100 bg-blue-50/60 p-4">
                <p class="text-[11px] uppercase tracking-wider text-blue-700 font-semibold">Total Tagihan Aktif</p>
                <p class="text-xl font-bold text-blue-900 mt-1">Rp {{ number_format($totalTagihan, 0, ',', '.') }}</p>
                <p class="text-[11px] text-blue-700 mt-0.5">{{ $invoices->where('status', '!=', 'cancelled')->count() }} tagihan</p>
            </div>
            <div class="rounded-xl border border-emerald-100 bg-emerald-50/60 p-4">
                <p class="text-[11px] uppercase tracking-wider text-emerald-700 font-semibold">Total Dibayar</p>
                <p class="text-xl font-bold text-emerald-700 mt-1">Rp {{ number_format($totalDibayar, 0, ',', '.') }}</p>
                <p class="text-[11px] text-emerald-700 mt-0.5">{{ $jumlahKuitansi }} kuitansi sah</p>
            </div>
            <div class="rounded-xl border border-rose-100 bg-rose-50/60 p-4">
                <p class="text-[11px] uppercase tracking-wider text-rose-700 font-semibold">Sisa Tunggakan</p>
                <p class="text-xl font-bold text-rose-700 mt-1">Rp {{ number_format($totalTunggakan, 0, ',', '.') }}</p>
                <p class="text-[11px] text-rose-700 mt-0.5">Saldo akhir kartu</p>
            </div>
            <div class="rounded-xl border border-amber-100 bg-amber-50/60 p-4">
                <p class="text-[11px] uppercase tracking-wider text-amber-700 font-semibold">Dokumen</p>
                <p class="text-xl font-bold text-amber-800 mt-1">{{ $invoices->count() + $payments->count() }}</p>
                <p class="text-[11px] text-amber-700 mt-0.5">{{ $invoices->count() }} tagihan &bull; {{ $payments->count() }} pembayaran</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 bg-gray-50/70 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-base font-bold text-gray-800 flex items-center">
                <i class="fa-solid fa-book-open text-brand-800 mr-2"></i> Buku Kas Santri
            </h2>
            <span class="text-xs text-gray-500">Tagihan = debit, pembayaran = kredit</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs sm:text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 uppercase text-[11px] font-semibold border-b border-gray-200">
                        <th class="py-3 px-3">Tanggal</th>
                        <th class="py-3 px-3">Uraian</th>
                        <th class="py-3 px-3">No. Dokumen</th>
                        <th class="py-3 px-3 text-right">Debit</th>
                        <th class="py-3 px-3 text-right">Kredit</th>
                        <th class="py-3 px-3 text-right">Saldo</th>
                        <th class="py-3 px-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($ledger as $entry)
                        <tr class="hover:bg-gray-50/50 {{ ($entry['payment']?->isVoided() || $entry['invoice']?->isCancelled()) ? 'opacity-60' : '' }}">
                            <td class="py-3 px-3 text-gray-600 whitespace-nowrap">{{ $entry['date']->format('d/m/Y') }}</td>
                            <td class="py-3 px-3">
                                <p class="font-semibold text-gray-800">{{ $entry['description'] }}</p>
                                @if($entry['note'])
                                    <p class="text-[10px] text-rose-600 mt-0.5">{{ $entry['note'] }}</p>
                                @endif
                            </td>
                            <td class="py-3 px-3 font-mono text-[11px] text-gray-600">{{ $entry['document'] }}</td>
                            <td class="py-3 px-3 text-right font-semibold text-blue-800">
                                {{ $entry['debit'] > 0 ? 'Rp ' . number_format($entry['debit'], 0, ',', '.') : '-' }}
                            </td>
                            <td class="py-3 px-3 text-right font-semibold text-emerald-700">
                                {{ $entry['credit'] > 0 ? 'Rp ' . number_format($entry['credit'], 0, ',', '.') : '-' }}
                            </td>
                            <td class="py-3 px-3 text-right font-bold text-gray-900">Rp {{ number_format($entry['balance'], 0, ',', '.') }}</td>
                            <td class="py-3 px-3 text-center">
                                <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold
                                    {{ $entry['status'] === 'Sah' || $entry['status'] === 'Lunas' ? 'bg-emerald-100 text-emerald-800' : '' }}
                                    {{ $entry['status'] === 'Belum Bayar' ? 'bg-rose-100 text-rose-800' : '' }}
                                    {{ $entry['status'] === 'Dicicil (Sebagian)' ? 'bg-amber-100 text-amber-800' : '' }}
                                    {{ $entry['status'] === 'Dibatalkan' ? 'bg-gray-200 text-gray-600' : '' }}">
                                    {{ $entry['status'] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-10 text-center text-gray-400">Belum ada transaksi keuangan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 bg-gray-50/70 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-base font-bold text-gray-800 flex items-center">
                <i class="fa-solid fa-file-invoice-dollar text-brand-800 mr-2"></i> Daftar Tagihan
            </h2>
            <span class="text-xs text-gray-500">{{ $invoices->count() }} dokumen</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs sm:text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 uppercase text-[11px] font-semibold border-b border-gray-200">
                        <th class="py-3 px-3">Tanggal</th>
                        <th class="py-3 px-3">No. Tagihan</th>
                        <th class="py-3 px-3">Uraian</th>
                        <th class="py-3 px-3">Jatuh Tempo</th>
                        <th class="py-3 px-3 text-right">Nominal</th>
                        <th class="py-3 px-3 text-right">Dibayar</th>
                        <th class="py-3 px-3 text-right">Sisa</th>
                        <th class="py-3 px-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($invoices as $invoice)
                        <tr class="hover:bg-gray-50/50 {{ $invoice->isCancelled() ? 'opacity-60' : '' }}">
                            <td class="py-3 px-3 text-gray-600 whitespace-nowrap">{{ $invoice->created_at?->format('d/m/Y') ?? '-' }}</td>
                            <td class="py-3 px-3 font-mono text-[11px] text-gray-600">{{ $invoice->invoice_number }}</td>
                            <td class="py-3 px-3">
                                <p class="font-semibold text-gray-800">{{ $invoice->title }}</p>
                                <p class="text-[10px] text-gray-400">{{ $invoice->category?->name ?? '-' }}</p>
                            </td>
                            <td class="py-3 px-3 text-gray-600 whitespace-nowrap">{{ $invoice->due_date?->format('d/m/Y') ?? '-' }}</td>
                            <td class="py-3 px-3 text-right font-semibold text-gray-800">Rp {{ number_format((float) $invoice->amount, 0, ',', '.') }}</td>
                            <td class="py-3 px-3 text-right text-emerald-700">Rp {{ number_format((float) $invoice->paid_amount, 0, ',', '.') }}</td>
                            <td class="py-3 px-3 text-right font-bold {{ $invoice->remaining_amount > 0 ? 'text-rose-600' : 'text-gray-400' }}">
                                Rp {{ number_format($invoice->remaining_amount, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-3 text-center">
                                <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold
                                    {{ $invoice->status === 'paid' ? 'bg-emerald-100 text-emerald-800' : '' }}
                                    {{ $invoice->status === 'unpaid' ? 'bg-rose-100 text-rose-800' : '' }}
                                    {{ $invoice->status === 'partial' ? 'bg-amber-100 text-amber-800' : '' }}
                                    {{ $invoice->status === 'cancelled' ? 'bg-gray-200 text-gray-600' : '' }}">
                                    {{ $invoice->status_label }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-10 text-center text-gray-400">Belum ada tagihan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 bg-gray-50/70 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-base font-bold text-gray-800 flex items-center">
                <i class="fa-solid fa-receipt text-brand-800 mr-2"></i> Riwayat Pembayaran & Kuitansi
            </h2>
            <span class="text-xs text-gray-500">{{ $payments->count() }} pembayaran</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs sm:text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 uppercase text-[11px] font-semibold border-b border-gray-200">
                        <th class="py-3 px-3">Tanggal</th>
                        <th class="py-3 px-3">No. Kuitansi</th>
                        <th class="py-3 px-3">Tagihan</th>
                        <th class="py-3 px-3 text-right">Nominal</th>
                        <th class="py-3 px-3">Metode</th>
                        <th class="py-3 px-3">Diterima Dari</th>
                        <th class="py-3 px-3">Petugas</th>
                        <th class="py-3 px-3 text-center">Status</th>
                        <th class="py-3 px-3 text-center">Dokumen</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($payments as $payment)
                        <tr class="hover:bg-gray-50/50 {{ $payment->isVoided() ? 'opacity-60' : '' }}">
                            <td class="py-3 px-3 text-gray-600 whitespace-nowrap">{{ $payment->payment_date?->format('d/m/Y') ?? '-' }}</td>
                            <td class="py-3 px-3 font-mono text-[11px] text-gray-600">{{ $payment->receipt?->receipt_number ?? $payment->payment_code }}</td>
                            <td class="py-3 px-3 font-semibold text-gray-800">{{ $payment->invoice?->title ?? '-' }}</td>
                            <td class="py-3 px-3 text-right font-bold {{ $payment->isVoided() ? 'text-gray-400 line-through' : 'text-emerald-700' }}">
                                Rp {{ number_format((float) $payment->amount_paid, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-3 uppercase text-[11px] text-gray-600">{{ $payment->payment_method }}</td>
                            <td class="py-3 px-3 text-gray-700">{{ $payment->received_from ?: $student->nama_wali }}</td>
                            <td class="py-3 px-3 text-gray-600">{{ $payment->recordedBy?->name ?? '-' }}</td>
                            <td class="py-3 px-3 text-center">
                                @if($payment->isVoided())
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-gray-200 text-gray-600">Dibatalkan</span>
                                @else
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">Sah</span>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-center">
                                @if($payment->receipt)
                                    <a href="{{ route('receipt.download', $payment->receipt->id) }}" target="_blank"
                                       class="inline-flex items-center px-2.5 py-1.5 bg-brand-900 hover:bg-brand-800 text-white rounded-lg text-[11px] font-semibold transition">
                                        <i class="fa-solid fa-file-pdf mr-1 text-gold-400"></i> PDF
                                    </a>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-10 text-center text-gray-400">Belum ada pembayaran.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-blue-50/60 border border-blue-100 rounded-xl p-4 text-[11px] text-blue-800">
        <p><strong>Catatan:</strong> Kartu ini merangkum seluruh tagihan dan pembayaran yang tercatat di sistem. Kuitansi berstatus <strong>Sah</strong> dapat diunduh dan diverifikasi melalui QR Code. Transaksi berstatus <strong>Dibatalkan</strong> tidak mengubah saldo.</p>
        <p class="mt-1">Dicetak dari Sistem Keuangan PTQ Amsa001 pada {{ now()->format('d/m/Y H:i') }}.</p>
    </div>
</div>

<style>
    @media print {
        header, footer, nav { display: none !important; }
        body { background: #fff !important; }
        .no-print { display: none !important; }
        .print-card { box-shadow: none !important; }
        main { padding: 0 !important; }
        a { color: inherit !important; text-decoration: none !important; }
        .print-card, .bg-white { break-inside: avoid; }
    }
</style>
@endsection
