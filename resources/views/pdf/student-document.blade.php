<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $documentTitle }} - {{ $student->nis }}</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 10mm 10mm 12mm 10mm;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1f2937;
            font-size: 8px;
            line-height: 1.35;
            margin: 0;
            padding: 0;
        }
        .header-table { width: 100%; border-bottom: 2px solid #1e3a8a; padding-bottom: 6px; margin-bottom: 8px; }
        .logo-text { font-size: 13px; font-weight: bold; color: #1e3a8a; margin: 0; }
        .sub-text { font-size: 8px; color: #4b5563; margin: 1px 0; }
        .address-text { font-size: 7px; color: #6b7280; margin: 0; }
        .doc-title { text-align: right; }
        .doc-title h1 { font-size: 11px; color: #1e3a8a; margin: 0; text-transform: uppercase; letter-spacing: 0.3px; line-height: 1.2; }
        .doc-title p { font-size: 7.5px; color: #6b7280; margin: 2px 0 0; }
        .identity-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .identity-table td { padding: 3px 5px; vertical-align: top; border: 1px solid #e5e7eb; }
        .identity-label { width: 14%; background: #f8fafc; color: #6b7280; font-size: 7px; text-transform: uppercase; font-weight: bold; }
        .identity-value { width: 36%; font-weight: bold; color: #111827; }
        .period-box { border: 1px solid #dbeafe; background: #eff6ff; padding: 5px 7px; margin-bottom: 8px; font-size: 7.5px; color: #1e3a8a; }
        .period-box strong { font-size: 8px; }
        .summary-table { width: 100%; border-collapse: separate; border-spacing: 4px; margin-bottom: 8px; }
        .summary-table td { width: 33.333%; border: 1px solid #e5e7eb; padding: 5px 6px; vertical-align: top; }
        .summary-label { font-size: 7px; color: #6b7280; text-transform: uppercase; font-weight: bold; }
        .summary-value { font-size: 11px; font-weight: bold; color: #1e3a8a; margin-top: 2px; }
        .summary-note { font-size: 7px; color: #6b7280; margin-top: 1px; }
        .section-title { font-size: 9px; font-weight: bold; color: #1e3a8a; text-transform: uppercase; letter-spacing: 0.4px; margin: 10px 0 4px; border-left: 3px solid #f59e0b; padding-left: 5px; }
        .document-role { border: 1px solid #fde68a; background: #fffbeb; color: #92400e; padding: 4px 6px; margin: 0 0 6px; font-size: 7.5px; font-weight: bold; }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        table.data th { background: #1e3a8a; color: #ffffff; font-size: 7px; text-transform: uppercase; padding: 4px 3px; border: 1px solid #1e3a8a; text-align: left; }
        table.data td { font-size: 7.5px; padding: 3px; border: 1px solid #e5e7eb; vertical-align: top; }
        table.data tbody tr:nth-child(even) { background: #f8fafc; }
        table.data thead { display: table-header-group; }
        table.data tr { page-break-inside: avoid; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .muted { color: #6b7280; }
        .void { color: #9f1239; text-decoration: line-through; }
        .badge { display: inline-block; padding: 1px 4px; border-radius: 3px; font-size: 6.5px; font-weight: bold; }
        .badge-paid { background: #d1fae5; color: #065f46; }
        .badge-unpaid { background: #ffe4e6; color: #9f1239; }
        .badge-partial { background: #fef3c7; color: #92400e; }
        .badge-void { background: #e5e7eb; color: #4b5563; }
        .footer-note { margin-top: 8px; border-top: 1px solid #e5e7eb; padding-top: 5px; font-size: 7px; color: #6b7280; }
        .document-code { font-family: 'Courier New', monospace; font-size: 7px; }
    </style>
</head>
<body>
    @php
        $hubunganWali = $student->hubungan_wali === 'Lainnya' && $student->hubungan_wali_detail
            ? 'Lainnya: ' . $student->hubungan_wali_detail
            : $student->hubungan_wali;
        $statusSantri = match ($student->status) {
            'aktif' => 'Aktif',
            'lulus' => 'Lulus',
            'pindah' => 'Pindah',
            'nonaktif' => 'Nonaktif',
            default => ucfirst($student->status),
        };
    @endphp

    <table class="header-table">
        <tr>
            <td style="width: 12%; vertical-align: middle;">
                <img src="{{ public_path('images/logo-pesantren.png') }}" style="height: 46px; width: auto;" alt="Logo PTQ Amsa001">
            </td>
            <td style="width: 54%; vertical-align: middle;">
                <div class="logo-text">PESANTREN TADABBUR AL-QUR'AN AMSA001</div>
                <div class="sub-text">Satuan Pendidikan Muadalah Mu'allimien | Putra-Putri</div>
                <div class="address-text">Jl. Cikopo Selatan, Gg. Yulias, Sukabirus, Desa Gadog, Kec. Megamendung, Kab. Bogor 16770</div>
                <div class="address-text">WhatsApp: +62 856-9225-3516 | Email: info@ptqamsa.id | Web: www.ptqamsa.id</div>
            </td>
            <td style="width: 34%; vertical-align: top;" class="doc-title">
                <h1>{{ $documentTitle }}</h1>
                <p>Tertampil: {{ now()->format('d/m/Y') }}</p>
                <p>Kode: {{ $documentCode }}</p>
            </td>
        </tr>
    </table>

    <table class="identity-table">
        <tr>
            <td class="identity-label">Nama Santri</td>
            <td class="identity-value">{{ $student->nama_lengkap }} ({{ $student->nis }})</td>
            <td class="identity-label">Kelas / Marhalah</td>
            <td class="identity-value">{{ $student->kelas }} / {{ $student->kelompok }}</td>
        </tr>
        <tr>
            <td class="identity-label">Wali Santri</td>
            <td class="identity-value">{{ $student->nama_wali }} ({{ $hubunganWali }})</td>
            <td class="identity-label">WhatsApp Wali</td>
            <td class="identity-value">{{ $student->no_whatsapp_wali }}</td>
        </tr>
        <tr>
            <td class="identity-label">Status</td>
            <td class="identity-value">{{ $statusSantri }}</td>
            <td class="identity-label">Asrama</td>
            <td class="identity-value">{{ $student->kamar_asrama ?: '-' }}</td>
        </tr>
    </table>

    <div class="period-box">
        <strong>Periode dokumen:</strong> {{ $semester['label'] }}
        ({{ $semester['start']->format('d/m/Y') }} - {{ $periodEnd->format('d/m/Y') }})
        &nbsp;|&nbsp;
        <strong>Batas tunggakan:</strong> sampai {{ $batasTunggakanLabel }}
    </div>

    @if($mode === 'invoices')
        <table class="summary-table">
            <tr>
                <td>
                    <div class="summary-label">Total Tagihan</div>
                    <div class="summary-value">Rp {{ number_format($totalTagihan, 0, ',', '.') }}</div>
                    <div class="summary-note">{{ $invoices->count() }} dokumen dalam periode</div>
                </td>
                <td>
                    <div class="summary-label">Total Dibayar</div>
                    <div class="summary-value" style="color: #047857;">Rp {{ number_format($totalDibayar, 0, ',', '.') }}</div>
                    <div class="summary-note">Akumulasi pembayaran sah</div>
                </td>
                <td>
                    <div class="summary-label">Sisa Tunggakan</div>
                    <div class="summary-value" style="color: #be123c;">Rp {{ number_format($totalTunggakan, 0, ',', '.') }}</div>
                    <div class="summary-note">Sampai {{ $batasTunggakanLabel }}</div>
                </td>
            </tr>
        </table>

        <div class="document-role">Dokumen ini adalah daftar tagihan, bukan bukti pembayaran.</div>
        <div class="section-title">Daftar Tagihan &amp; Sisa Kewajiban</div>
        <table class="data">
            <thead>
                <tr>
                    <th style="width: 9%;">Tanggal</th>
                    <th style="width: 15%;">No. Tagihan</th>
                    <th style="width: 27%;">Uraian</th>
                    <th style="width: 9%;">Jatuh Tempo</th>
                    <th style="width: 11%;" class="text-right">Nominal</th>
                    <th style="width: 11%;" class="text-right">Dibayar</th>
                    <th style="width: 10%;" class="text-right">Sisa</th>
                    <th style="width: 8%;" class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $invoice)
                    <tr>
                        <td>{{ $invoice->created_at?->format('d/m/Y') ?? '-' }}</td>
                        <td class="document-code">
                            {{ $invoice->invoice_number }}
                            @php
                                $invoiceReceipts = $invoice->payments
                                    ->whereNull('voided_at')
                                    ->map(fn ($payment) => $payment->receipt?->receipt_number)
                                    ->filter()
                                    ->unique();
                            @endphp
                            @if($invoiceReceipts->isNotEmpty())
                                <div class="muted" style="font-size: 6px; margin-top: 1px;">Kuitansi: {{ $invoiceReceipts->join(', ') }}</div>
                            @endif
                        </td>
                        <td>
                            {{ $invoice->title }}
                            <div class="muted" style="font-size: 6.5px;">{{ $invoice->category?->name ?? '-' }}</div>
                        </td>
                        <td>{{ $invoice->due_date?->format('d/m/Y') ?? '-' }}</td>
                        <td class="text-right">{{ number_format((float) $invoice->amount, 0, ',', '.') }}</td>
                        <td class="text-right" style="color: #047857;">{{ number_format((float) $invoice->paid_amount, 0, ',', '.') }}</td>
                        <td class="text-right font-bold">{{ number_format($invoice->remaining_amount, 0, ',', '.') }}</td>
                        <td class="text-center">
                            <span class="badge {{ $invoice->status === 'paid' ? 'badge-paid' : '' }} {{ $invoice->status === 'unpaid' ? 'badge-unpaid' : '' }} {{ $invoice->status === 'partial' ? 'badge-partial' : '' }} {{ $invoice->status === 'cancelled' ? 'badge-void' : '' }}">{{ $invoice->status_label }}</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center muted">Belum ada tagihan pada periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    @else
        <table class="summary-table">
            <tr>
                <td>
                    <div class="summary-label">Total Pembayaran</div>
                    <div class="summary-value" style="color: #047857;">Rp {{ number_format($totalDibayar, 0, ',', '.') }}</div>
                    <div class="summary-note">Pembayaran sah pada periode</div>
                </td>
                <td>
                    <div class="summary-label">Jumlah Kuitansi</div>
                    <div class="summary-value">{{ $jumlahKuitansi }}</div>
                    <div class="summary-note">{{ $payments->count() }} baris transaksi</div>
                </td>
                <td>
                    <div class="summary-label">Periode</div>
                    <div class="summary-value" style="font-size: 9px;">{{ $semester['label'] }}</div>
                    <div class="summary-note">s.d. {{ $periodEnd->format('d/m/Y') }}</div>
                </td>
            </tr>
        </table>

        <div class="document-role">Dokumen ini adalah bukti pembayaran.</div>
        <div class="section-title">Riwayat Pembayaran &amp; Kuitansi</div>
        <table class="data">
            <thead>
                <tr>
                    <th style="width: 9%;">Tanggal</th>
                    <th style="width: 16%;">No. Kuitansi</th>
                    <th style="width: 23%;">Tagihan</th>
                    <th style="width: 11%;" class="text-right">Nominal</th>
                    <th style="width: 9%;">Metode</th>
                    <th style="width: 14%;">Diterima Dari</th>
                    <th style="width: 10%;">Petugas</th>
                    <th style="width: 8%;" class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                    <tr>
                        <td>{{ $payment->payment_date?->format('d/m/Y') ?? '-' }}</td>
                        <td class="document-code">{{ $payment->receipt?->receipt_number ?? $payment->payment_code }}</td>
                        <td>
                            {{ $payment->invoice?->receipt_title ?? '-' }}
                            @if($payment->invoice?->invoice_number)
                                <div class="muted document-code" style="font-size: 6px; margin-top: 1px;">{{ $payment->invoice->invoice_number }}</div>
                            @endif
                        </td>
                        <td class="text-right font-bold {{ $payment->isVoided() ? 'void' : '' }}">{{ number_format((float) $payment->amount_paid, 0, ',', '.') }}</td>
                        <td>{{ strtoupper($payment->payment_method) }}</td>
                        <td>{{ $payment->received_from ?: $student->nama_wali }}</td>
                        <td>{{ $payment->recordedBy?->name ?? '-' }}</td>
                        <td class="text-center">
                            <span class="badge {{ $payment->isVoided() ? 'badge-void' : 'badge-paid' }}">{{ $payment->isVoided() ? 'Dibatalkan' : 'Sah' }}</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center muted">Belum ada pembayaran pada periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endif

    <div class="footer-note">
        <strong>Catatan:</strong> {{ $batasTunggakanCatatan }}
        Dokumen ini dibatasi maksimal satu semester ({{ $semester['label'] }}); sisa wakaf yang masih berjalan tetap disertakan. Kuitansi berstatus
        <strong>Sah</strong> dapat diunduh; transaksi berstatus
        <strong>Dibatalkan</strong> tidak mengubah saldo.
        <br>Tertampil dari SIKA PTQ (Sistem Informasi Keuangan Amanah PTQ Amsa001) pada {{ now()->format('d/m/Y') }}.
    </div>
</body>
</html>
