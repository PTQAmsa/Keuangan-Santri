<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kuitansi - {{ $receipt->receipt_number }}</title>
    <style>
        @page {
            size: a5 landscape;
            margin: 15mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1f2937;
            font-size: 11px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #1e3a8a;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .logo-text {
            font-size: 16px;
            font-weight: bold;
            color: #1e3a8a;
            margin: 0;
            letter-spacing: 0.5px;
        }
        .sub-text {
            font-size: 10px;
            color: #4b5563;
            margin: 2px 0;
        }
        .address-text {
            font-size: 8.5px;
            color: #6b7280;
            margin: 0;
        }
        .receipt-title-box {
            text-align: center;
            margin: 8px 0 14px 0;
        }
        .receipt-title {
            display: inline-block;
            font-size: 13px;
            font-weight: bold;
            color: #1e3a8a;
            border-bottom: 1.5px solid #f59e0b;
            padding-bottom: 2px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .receipt-number {
            font-size: 10px;
            color: #4b5563;
            margin-top: 3px;
        }
        .content-table {
            width: 100%;
            margin-bottom: 12px;
            border-collapse: collapse;
        }
        .content-table td {
            padding: 4px 6px;
            vertical-align: top;
        }
        .label-col {
            width: 25%;
            font-weight: bold;
            color: #374151;
        }
        .separator-col {
            width: 3%;
            text-align: center;
        }
        .value-col {
            width: 72%;
            color: #111827;
        }
        .terbilang-box {
            background-color: #f3f4f6;
            border-left: 3px solid #1e3a8a;
            padding: 6px 10px;
            margin: 10px 0;
            font-style: italic;
            font-weight: 500;
            color: #1e3a8a;
        }
        .amount-box {
            display: inline-block;
            background-color: #eff6ff;
            border: 1px dashed #2563eb;
            color: #1e3a8a;
            font-size: 14px;
            font-weight: bold;
            padding: 4px 12px;
            border-radius: 4px;
        }
        .footer-table {
            width: 100%;
            margin-top: 15px;
            border-collapse: collapse;
        }
        .qr-box {
            border: 1px solid #e5e7eb;
            padding: 6px;
            background-color: #fafafa;
            border-radius: 4px;
            font-size: 8px;
            color: #6b7280;
            line-height: 1.2;
        }
        .sig-box {
            text-align: center;
            font-size: 10px;
        }
        .sig-space {
            height: 40px;
        }
        .badge-paid {
            color: #065f46;
            background-color: #d1fae5;
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 9px;
            display: inline-block;
        }
        .badge-partial {
            color: #92400e;
            background-color: #fef3c7;
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 9px;
            display: inline-block;
        }
        .watermark {
            position: fixed;
            top: 35%;
            left: 8%;
            width: 84%;
            text-align: center;
            font-size: 54px;
            font-weight: bold;
            color: rgba(190, 18, 60, 0.20);
            border: 4px solid rgba(190, 18, 60, 0.20);
            padding: 8px 0;
            transform: rotate(-18deg);
            letter-spacing: 6px;
            z-index: 999;
        }
        .badge-void {
            color: #9f1239;
            background-color: #ffe4e6;
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 9px;
            display: inline-block;
        }
    </style>
</head>
<body>
    @if($payment->isVoided())
        <div class="watermark">DIBATALKAN</div>
    @endif

    <!-- Header Lembaga -->
    <table class="header-table">
        <tr>
            <td style="width: 12%; vertical-align: middle;">
                <img src="{{ public_path('images/logo-pesantren.png') }}" style="height: 52px; width: auto;" alt="Logo PTQ Amsa001">
            </td>
            <td style="width: 63%; vertical-align: middle;">
                <div class="logo-text">PESANTREN TADABBUR AL-QUR'AN AMSA001</div>
                <div class="sub-text">Satuan Pendidikan Muadalah Mu'allimien (Program 6 Tahun) Putra &amp; Putri</div>
                <div class="address-text">Jl. Cikopo Selatan, Gg. Yulias, Sukabirus, Desa Gadog, Kec. Megamendung, Kab. Bogor 16770</div>
                <div class="address-text">WhatsApp: +62 856-9225-3516 | Email: info@ptqamsa.id | Web: www.ptqamsa.id</div>
            </td>
            <td style="width: 25%; text-align: right; vertical-align: top;">
                <div style="font-size: 9px; color: #6b7280;">Tanggal Terbit:</div>
                <div style="font-weight: bold; color: #1e3a8a;">{{ $receipt->issued_at->format('d F Y') }}</div>
            </td>
        </tr>
    </table>

    <!-- Judul Kuitansi -->
    <div class="receipt-title-box">
        <div class="receipt-title">Tanda Terima Pembayaran Digital</div>
        <div class="receipt-number">Nomor: <strong>{{ $receipt->receipt_number }}</strong></div>
    </div>

    <!-- Rincian Pembayaran -->
    <table class="content-table">
        <tr>
            <td class="label-col">Telah Diterima Dari</td>
            <td class="separator-col">:</td>
            <td class="value-col"><strong>{{ $payment->received_from ?: $student->nama_wali }}</strong></td>
        </tr>
        <tr>
            <td class="label-col">Nama Santri</td>
            <td class="separator-col">:</td>
            <td class="value-col">{{ $student->nama_lengkap }} (NIS: {{ $student->nis }} / Kelas: {{ $student->kelas }} / {{ $student->kelompok }})</td>
        </tr>
        <tr>
            <td class="label-col">Untuk Pembayaran</td>
            <td class="separator-col">:</td>
            <td class="value-col">
                <strong>{{ $invoice->receipt_title }}</strong>
            </td>
        </tr>
        <tr>
            <td class="label-col">Status Kuitansi</td>
            <td class="separator-col">:</td>
            <td class="value-col">
                @if($payment->isVoided())
                    <span class="badge-void">DIBATALKAN {{ $payment->voided_at->format('d/m/Y H:i') }} — {{ $payment->void_reason }}</span>
                @else
                    <span class="badge-paid">SAH</span>
                @endif
            </td>
        </tr>
        <tr>
            <td class="label-col">Status Tagihan</td>
            <td class="separator-col">:</td>
            <td class="value-col">
                @if($invoice->status === 'paid')
                    <span class="badge-paid">LUNAS</span>
                @else
                    <span class="badge-partial">DICICIL (Sisa Tagihan: Rp {{ number_format($invoice->remaining_amount, 0, ',', '.') }})</span>
                @endif
            </td>
        </tr>
        <tr>
            <td class="label-col">Metode Pembayaran</td>
            <td class="separator-col">:</td>
            <td class="value-col">{{ strtoupper($payment->payment_method) }} ({{ $payment->notes ?? 'Transaksi Sukses' }})</td>
        </tr>
    </table>

    <!-- Kotak Terbilang -->
    <div class="terbilang-box">
        Terbilang: # {{ $terbilang }} #
    </div>

    <!-- Footer & Tanda Tangan -->
    <table class="footer-table">
        <tr>
            <td style="width: 45%; vertical-align: top;">
                <div class="amount-box">
                    Rp {{ number_format($payment->amount_paid, 0, ',', '.') }},-
                </div>
                <div class="qr-box" style="margin-top: 8px;">
                    <strong>Verifikasi Dokumen Resmi:</strong><br>
                    Dokumen ini diterbitkan secara elektronik oleh Sistem Keuangan PTQ Amsa001.<br>
                    Validasi keaslian dokumen dapat diakses melalui:<br>
                    <a href="{{ $verification_url }}" style="color: #2563eb; text-decoration: none;">{{ $verification_url }}</a>
                </div>
            </td>
            <td style="width: 20%;"></td>
            <td style="width: 35%; vertical-align: top;" class="sig-box">
                <div>Gadog, {{ $payment->payment_date->translatedFormat('d F Y') }}</div>
                <div>Bendahara / Kasir Keuangan</div>
                <div class="sig-space"></div>
                <div style="font-weight: bold; text-decoration: underline;">{{ $payment->recordedBy->name ?? 'Usth. Yuni Permatasari' }}</div>
                <div style="font-size: 8.5px; color: #6b7280;">Sistem Keuangan PTQ Amsa001</div>
            </td>
        </tr>
    </table>
</body>
</html>
