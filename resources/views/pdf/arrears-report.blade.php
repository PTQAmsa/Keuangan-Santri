<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Rekapitulasi Tunggakan Santri - PTQ Amsa001</title>
    <style>
        @page {
            size: a4 portrait;
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
            font-size: 15px;
            font-weight: bold;
            color: #1e3a8a;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .logo-sub {
            font-size: 9.5px;
            color: #4b5563;
            margin: 2px 0;
        }
        .logo-address {
            font-size: 8px;
            color: #6b7280;
            margin: 0;
        }
        .report-header {
            text-align: center;
            margin-bottom: 14px;
        }
        .report-title {
            font-size: 13px;
            font-weight: bold;
            color: #b91c1c;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin: 0;
        }
        .report-period {
            font-size: 9.5px;
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
            background-color: #fff1f2;
            border: 1px solid #fecdd3;
            border-radius: 4px;
        }
        .summary-label {
            font-size: 8px;
            color: #9f1239;
            text-transform: uppercase;
            font-weight: bold;
        }
        .summary-value {
            font-size: 13px;
            font-weight: bold;
            color: #be123c;
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
            font-size: 9px;
            vertical-align: top;
        }
        .data-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .data-table .total-row td {
            background-color: #f1f5f9;
            font-weight: bold;
            font-size: 9.5px;
            border-top: 2px solid #b91c1c;
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
            width: 45%;
        }
        .signature-space {
            height: 50px;
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
            <td style="width: 14%; vertical-align: middle;">
                <img src="{{ public_path('images/logo-pesantren.png') }}" style="height: 52px; width: auto;" alt="Logo PTQ Amsa001">
            </td>
            <td style="width: 56%; vertical-align: middle;">
                <h1 class="logo-title">Pesantren Tadabbur Al-Qur'an Amsa001</h1>
                <p class="logo-sub">Pendidikan Muadalah Mu'allimien (Program 6 Tahun)</p>
                <p class="logo-address">Jl. Cikopo Selatan, Gg. Yulias, Sukabirus, Desa Gadog, Kec. Megamendung, Bogor 16770 | Telp: +62 856-9225-3516</p>
            </td>
            <td style="width: 30%; text-align: right; vertical-align: middle;">
                <div style="font-size: 8.5px; color: #64748b;">
                    Dicetak: {{ now()->translatedFormat('d F Y H:i') }} WIB<br>
                    Dokumen: Rekapitulasi Tunggakan
                </div>
            </td>
        </tr>
    </table>

    <!-- Judul Laporan -->
    <div class="report-header">
        <h2 class="report-title">Laporan Rekapitulasi Tunggakan Santri</h2>
        <p class="report-period">
            Per Tanggal: <strong>{{ now()->translatedFormat('d F Y') }}</strong>
            @if($selectedClass) | Kelas: <strong>{{ $selectedClass }}</strong> @endif
            @if(in_array($kelompok ?? null, ['putra', 'putri'], true)) | Kelompok: <strong>{{ ucfirst($kelompok) }}</strong> @endif
        </p>
    </div>

    <!-- Ringkasan Statistik -->
    <table class="summary-table">
        <tr>
            <td style="width: 50%; padding-right: 6px;">
                <div class="summary-box">
                    <div class="summary-label">Total Nilai Tunggakan</div>
                    <div class="summary-value">Rp {{ number_format($totalNilaiTunggakan, 0, ',', '.') }}</div>
                </div>
            </td>
            <td style="width: 50%;">
                <div class="summary-box" style="background-color: #f8fafc; border-color: #cbd5e1;">
                    <div class="summary-label" style="color: #475569;">Santri Belum Lunas</div>
                    <div class="summary-value" style="color: #0f172a;">{{ $totalSantriMenunggak }} Santri</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Tabel Daftar Tunggakan -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">No</th>
                <th style="width: 25%;">Santri & Kelas</th>
                <th style="width: 20%;">Wali & Kontak</th>
                <th style="width: 33%;">Rincian Tagihan Tertunggak</th>
                <th style="width: 17%;" class="text-right">Total Tunggakan</th>
            </tr>
        </thead>
        <tbody>
            @php $no = 1; @endphp
            @forelse($studentArrears as $item)
                <tr>
                    <td class="text-center">{{ $no++ }}</td>
                    <td>
                        <strong>{{ $item['student']->nama_lengkap ?? '-' }}</strong><br>
                        <span style="font-size: 8.5px; color: #64748b;">NIS: {{ $item['student']->nis ?? '-' }} • Kelas {{ $item['student']->kelas ?? '-' }} • {{ $item['student']->kelompok ?? '-' }}</span>
                    </td>
                    <td>
                        {{ $item['student']->nama_wali ?? '-' }}<br>
                        <span style="font-size: 8.5px; color: #64748b; font-family: 'Courier New';">{{ $item['student']->no_whatsapp_wali ?? '-' }}</span>
                    </td>
                    <td>
                        @foreach($item['invoices'] as $inv)
                            <div style="margin-bottom: 2px;">
                                • {{ $inv->title }}:
                                <strong style="color: #b91c1c;">Rp {{ number_format($inv->remaining_amount, 0, ',', '.') }}</strong>
                                @if($inv->status == 'partial') <span style="color: #d97706; font-size: 8px;">(Cicilan)</span> @endif
                            </div>
                        @endforeach
                    </td>
                    <td class="text-right" style="font-weight: bold; color: #b91c1c;">
                        Rp {{ number_format($item['total_tunggakan'], 0, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center" style="padding: 20px; color: #94a3b8;">
                        Tidak ada catatan santri yang memiliki tunggakan.
                    </td>
                </tr>
            @endforelse
            <tr class="total-row">
                <td colspan="4" class="text-right" style="padding: 6px 8px; text-transform: uppercase;">
                    Total Nilai Seluruh Tunggakan:
                </td>
                <td class="text-right" style="color: #b91c1c; font-size: 11px;">
                    Rp {{ number_format($totalNilaiTunggakan, 0, ',', '.') }}
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Tanda Tangan Pengesahan -->
    <table class="footer-table">
        <tr>
            <td style="width: 50%; vertical-align: top;">
                <div style="font-size: 8.5px; color: #64748b;">
                    * Data ini digunakan sebagai rujukan pembinaan & penagihan berkala oleh Tim Keuangan Pesantren.<br>
                    * Harap konfirmasikan pembayaran jika ada wali santri yang baru menyelesaikan setoran.
                </div>
            </td>
            <td class="signature-box" style="vertical-align: top;">
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
