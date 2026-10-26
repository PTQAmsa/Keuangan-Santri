<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Penerimaan Kas - PTQ Amsa001</title>
    <style>
        @page {
            size: a4 landscape;
            margin: 12mm 15mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1f2937;
            font-size: 10px;
            line-height: 1.35;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-bottom: 2.5px solid #1e3a8a;
            padding-bottom: 6px;
            margin-bottom: 12px;
        }
        .logo-title {
            font-size: 16px;
            font-weight: bold;
            color: #1e3a8a;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .logo-sub {
            font-size: 10px;
            color: #4b5563;
            margin: 2px 0;
        }
        .logo-address {
            font-size: 8.5px;
            color: #6b7280;
            margin: 0;
        }
        .report-header {
            text-align: center;
            margin-bottom: 14px;
        }
        .report-title {
            font-size: 14px;
            font-weight: bold;
            color: #1e3a8a;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin: 0;
        }
        .report-period {
            font-size: 10px;
            color: #4b5563;
            margin-top: 3px;
        }
        .summary-table {
            width: 100%;
            margin-bottom: 14px;
            border-collapse: collapse;
        }
        .summary-box {
            padding: 8px 10px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
        }
        .summary-label {
            font-size: 8.5px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: bold;
        }
        .summary-value {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
            margin-top: 2px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .data-table th {
            background-color: #1e3a8a;
            color: #ffffff;
            font-weight: bold;
            text-align: left;
            padding: 6px 8px;
            font-size: 9px;
            text-transform: uppercase;
            border: 1px solid #1e3a8a;
        }
        .data-table td {
            padding: 5px 8px;
            border: 1px solid #e2e8f0;
            font-size: 9.5px;
        }
        .data-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .data-table .total-row td {
            background-color: #f1f5f9;
            font-weight: bold;
            font-size: 10px;
            border-top: 2px solid #1e3a8a;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-mono { font-family: 'Courier New', Courier, monospace; }
        
        .footer-table {
            width: 100%;
            margin-top: 20px;
            page-break-inside: avoid;
        }
        .signature-box {
            text-align: center;
            width: 30%;
        }
        .signature-space {
            height: 55px;
        }
        .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }
        .signature-title {
            font-size: 9px;
            color: #6b7280;
        }
    </style>
</head>
<body>

    <!-- Kop Pesantren PTQ Amsa001 -->
    <table class="header-table">
        <tr>
            <td style="width: 12%; vertical-align: middle;">
                <img src="{{ public_path('images/logo-pesantren.png') }}" style="height: 58px; width: auto;" alt="Logo PTQ Amsa001">
            </td>
            <td style="width: 58%; vertical-align: middle;">
                <h1 class="logo-title">Pesantren Tadabbur Al-Qur'an Amsa001</h1>
                <p class="logo-sub">Satuan Pendidikan Muadalah Mu'allimien | Putra-Putri</p>
                <p class="logo-address">Jl. Cikopo Selatan, Gg. Yulias, Sukabirus, Desa Gadog, Kec. Megamendung, Bogor 16770 | Telp: +62 856-9225-3516 | Web: www.ptqamsa.id</p>
            </td>
            <td style="width: 30%; text-align: right; vertical-align: middle;">
                <div style="font-size: 9px; color: #64748b;">
                    Dicetak: {{ now()->translatedFormat('d F Y') }}<br>
                    Dokumen: Laporan Resmi Internal
                </div>
            </td>
        </tr>
    </table>

    <!-- Judul Laporan -->
    <div class="report-header">
        <h2 class="report-title">Laporan Penerimaan Kas Keuangan</h2>
        <p class="report-period">
            Periode: <strong>{{ \Carbon\Carbon::parse($startDate)->translatedFormat('d F Y') }}</strong> s/d <strong>{{ \Carbon\Carbon::parse($endDate)->translatedFormat('d F Y') }}</strong>
            @if(in_array($kelompok ?? null, ['putra', 'putri'], true)) | Marhalah: <strong>{{ ucfirst($kelompok) }}</strong> @endif
        </p>
    </div>

    <!-- Ringkasan Statistik -->
    <table class="summary-table">
        <tr>
            <td style="width: 25%; padding-right: 6px;">
                <div class="summary-box">
                    <div class="summary-label">Total Kas Masuk</div>
                    <div class="summary-value" style="color: #047857;">Rp {{ number_format($totalKasMasuk ?? $totalPenerimaan, 0, ',', '.') }}</div>
                    @if(($totalLainnya ?? 0) > 0)
                        <div style="font-size: 8px; color: #6b7280; margin-top: 2px;">Santri Rp {{ number_format($totalPenerimaan, 0, ',', '.') }} &bull; Lainnya Rp {{ number_format($totalLainnya, 0, ',', '.') }}</div>
                    @endif
                </div>
            </td>
            <td style="width: 25%; padding-right: 6px;">
                <div class="summary-box">
                    <div class="summary-label">Jumlah Transaksi</div>
                    <div class="summary-value">{{ $totalTransaksi }} Kuitansi</div>
                </div>
            </td>
            <td style="width: 25%; padding-right: 6px;">
                <div class="summary-box">
                    <div class="summary-label">Penerimaan Tunai (Kasir)</div>
                    <div class="summary-value">Rp {{ number_format($totalCash, 0, ',', '.') }}</div>
                </div>
            </td>
            <td style="width: 25%;">
                <div class="summary-box">
                    <div class="summary-label">Penerimaan Transfer Bank</div>
                    <div class="summary-value">Rp {{ number_format($totalTransfer, 0, ',', '.') }}</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Tabel Rincian Pembayaran -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;" class="text-center">No</th>
                <th style="width: 13%;">No Kuitansi</th>
                <th style="width: 9%;">Tanggal</th>
                <th style="width: 15%;">Nama Santri & Kelas</th>
                <th style="width: 7%;" class="text-center">Marhalah</th>
                <th style="width: 21%;">Komponen Tagihan</th>
                <th style="width: 8%;" class="text-center">Metode</th>
                <th style="width: 14%;" class="text-right">Nominal (Rp)</th>
                <th style="width: 9%;">Catatan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payments as $index => $pay)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="font-mono">{{ $pay->receipt->receipt_number ?? '-' }}</td>
                    <td>{{ $pay->payment_date ? $pay->payment_date->format('d/m/Y') : '-' }}</td>
                    <td>
                        <strong>{{ $pay->student->nama_lengkap ?? '-' }}</strong><br>
                        <span style="font-size: 8.5px; color: #64748b;">NIS: {{ $pay->student->nis ?? '-' }} (Kelas {{ $pay->student->kelas ?? '-' }})</span>
                    </td>
                    <td class="text-center">{{ $pay->student->kelompok ?? '-' }}</td>
                    <td>
                        <strong>{{ $pay->invoice->category->name ?? '-' }}</strong><br>
                        <span style="font-size: 8.5px; color: #64748b;">{{ $pay->invoice?->receipt_title ?? '-' }}</span>
                    </td>
                    <td class="text-center">
                        {{ strtoupper($pay->payment_method) }}
                    </td>
                    <td class="text-right" style="font-weight: bold;">
                        Rp {{ number_format($pay->amount_paid, 0, ',', '.') }}
                    </td>
                    <td style="font-size: 8.5px; color: #64748b;">
                        {{ $pay->notes ?? '-' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center" style="padding: 20px; color: #94a3b8;">
                        Tidak ada transaksi pembayaran pada rentang periode ini.
                    </td>
                </tr>
            @endforelse
            <tr class="total-row">
                <td colspan="7" class="text-right" style="padding: 6px 8px; text-transform: uppercase;">
                    Total Pembayaran Santri:
                </td>
                <td class="text-right" style="color: #047857; font-size: 11px;">
                    Rp {{ number_format($totalPenerimaan, 0, ',', '.') }}
                </td>
                <td></td>
            </tr>
        </tbody>
    </table>

    @if(isset($otherIncomes) && $otherIncomes->isNotEmpty())
        <div class="report-header" style="margin-top: 14px;">
            <h2 class="report-title" style="font-size: 11px;">Pemasukan Lainnya (Donasi, Infaq, Hibah, Unit Usaha, Sewa)</h2>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 4%;" class="text-center">No</th>
                    <th style="width: 13%;">Kode</th>
                    <th style="width: 9%;">Tanggal</th>
                    <th style="width: 14%;">Jenis</th>
                    <th style="width: 24%;">Uraian</th>
                    <th style="width: 14%;">Sumber / Pemberi</th>
                    <th style="width: 8%;" class="text-center">Metode</th>
                    <th style="width: 14%;" class="text-right">Nominal (Rp)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($otherIncomes as $index => $income)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $income->income_code }}</td>
                        <td>{{ $income->income_date?->format('d/m/Y') }}</td>
                        <td>{{ $income->category->label() }}</td>
                        <td>{{ $income->description }}</td>
                        <td>{{ $income->source ?: '-' }}</td>
                        <td class="text-center">{{ $income->paymentMethodLabel() }}</td>
                        <td class="text-right">{{ number_format((float) $income->amount, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="7" class="text-right" style="padding: 6px 8px; text-transform: uppercase;">Total Pemasukan Lainnya:</td>
                    <td class="text-right" style="color: #047857; font-size: 11px;">Rp {{ number_format($totalLainnya, 0, ',', '.') }}</td>
                </tr>
                <tr class="total-row">
                    <td colspan="7" class="text-right" style="padding: 6px 8px; text-transform: uppercase;">Total Seluruh Kas Masuk:</td>
                    <td class="text-right" style="color: #047857; font-size: 11px;">Rp {{ number_format($totalKasMasuk, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    <!-- Tanda Tangan Pengesahan -->
    <table class="footer-table">
        <tr>
            <td style="width: 60%; vertical-align: top;">
                <div style="font-size: 8.5px; color: #64748b;">
                    * Laporan ini dicetak secara otomatis melalui Sistem Keuangan PTQ Amsa001.<br>
                    * Setiap data pembayaran terikat langsung dengan nomor kuitansi digital.
                </div>
            </td>
            <td class="signature-box" style="vertical-align: top; width: 40%;">
                <p style="margin: 0 0 2px 0;">Bogor, {{ now()->translatedFormat('d F Y') }}</p>
                <p class="signature-title">Bendahara Keuangan,</p>
                <div class="signature-space"></div>
                <p class="signature-name">Usth. Yuni Permatasari</p>
                <p class="signature-title">Bendahara Keuangan PTQ Amsa001</p>
            </td>
        </tr>
    </table>

</body>
</html>
