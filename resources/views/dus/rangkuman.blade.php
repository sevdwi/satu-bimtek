<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rangkuman Berkas — Dus {{ $dusArsip->nomor_dus ?? $dusArsip->id }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #1f2937;
            margin: 0;
            padding: 24px 16px 48px;
            background: #f3f4f6;
        }

        .wrapper {
            max-width: 900px;
            margin: 0 auto;
        }

        .card {
            background: #fff;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            padding: 20px 24px;
            margin-bottom: 20px;
        }

        .header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .header h1 {
            font-size: 18px;
            font-weight: 700;
            margin: 0 0 4px;
        }

        .header p {
            margin: 0;
            font-size: 13px;
            color: #6b7280;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(160px, 1fr));
            gap: 8px 24px;
            margin-top: 14px;
            font-size: 13px;
        }

        .info-grid dt {
            font-weight: 600;
            color: #374151;
        }

        .info-grid dd {
            margin: 0 0 8px;
            color: #1f2937;
        }

        .qr-box {
            text-align: center;
            flex-shrink: 0;
        }

        .qr-box img {
            width: 130px;
            height: 130px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 6px;
            background: #fff;
        }

        .qr-box a {
            display: block;
            margin-top: 6px;
            font-size: 11px;
            color: #2563eb;
            text-decoration: none;
        }

        .qr-box .kode-qrcode {
            display: block;
            margin-top: 8px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.5px;
            color: #1f2937;
        }

        table.rangkuman-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12.5px;
        }

        table.rangkuman-table th,
        table.rangkuman-table td {
            border: 1px solid #e5e7eb;
            padding: 8px 10px;
            text-align: left;
            vertical-align: top;
        }

        table.rangkuman-table th {
            background: #f9fafb;
            font-weight: 700;
        }

        table.rangkuman-table tr:nth-child(even) td {
            background: #fafafa;
        }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
            background: #e5e7eb;
            color: #374151;
        }

        .empty-state {
            padding: 24px;
            text-align: center;
            color: #6b7280;
            font-size: 13px;
        }

        .no-print .btn-cetak {
            padding: 8px 16px;
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }

        @media print {
            body { background: #fff; padding: 0; }
            .no-print { display: none !important; }
            .card { border: none; border-radius: 0; }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card header">
            <div>
                <h1>Rangkuman Berkas — Dus {{ $dusArsip->nomor_dus ?? '-' }}</h1>
                <p>Daftar seluruh arsip yang tersimpan di dalam dus ini.</p>

                <dl class="info-grid">
                    <dt>Nomor Dus</dt>
                    <dd>{{ $dusArsip->nomor_dus ?? '-' }}</dd>

                    <dt>Nomor Rak</dt>
                    <dd>{{ $dusArsip->rakArsip?->nomor_rak ?? '-' }}</dd>

                    <dt>Dinas</dt>
                    <dd>{{ $dusArsip->opd?->opdInduk?->instansi ?? '-' }}</dd>

                    <dt>Bidang</dt>
                    <dd>{{ $dusArsip->opd?->unit_kerja ?? '-' }}</dd>

                    <dt>Jumlah Arsip</dt>
                    <dd>{{ $arsips->count() }} berkas</dd>

                    @if ($dusArsip->kode_qrcode)
                        <dt>Kode QR</dt>
                        <dd style="font-family: 'Courier New', monospace; font-weight: 700;">{{ $dusArsip->kode_qrcode }}</dd>
                    @endif
                </dl>
            </div>

            <div class="qr-box">
                <img src="{{ route('dus-arsip.qrcode', $dusArsip) }}" alt="QR Code Dus {{ $dusArsip->nomor_dus }}">

                {{-- Kode ini PERSIS yang muncul kalau QR di atas discan
                     (lihat DusArsipRangkumanController::qrcode()) -- dicetak
                     juga sebagai teks di sini supaya tetap bisa dibaca &
                     diketik manual di halaman "Lacak Dus Arsip" walau
                     gambar QR-nya rusak/tidak terbaca kamera. --}}
                @if ($dusArsip->kode_qrcode)
                    <span class="kode-qrcode">{{ $dusArsip->kode_qrcode }}</span>
                @endif

                <div class="no-print">
                    <a href="{{ route('dus-arsip.qrcode', $dusArsip) }}" target="_blank" rel="noopener">Unduh QR</a>
                    <a href="#" onclick="window.print(); return false;">Cetak Halaman</a>
                </div>
            </div>
        </div>

        <div class="card" style="padding: 0; overflow: hidden;">
            @if ($arsips->isEmpty())
                <div class="empty-state">Belum ada arsip yang tercatat di dus ini.</div>
            @else
                <table class="rangkuman-table">
                    <thead>
                        <tr>
                            <th style="width: 32px;">No</th>
                            <th>Nomor</th>
                            <th>Kode Klasifikasi</th>
                            <th>Judul / Perihal</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th>Nasib Akhir</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($arsips as $i => $arsip)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $arsip->nomor ?? '-' }}</td>
                                <td>{{ $arsip->masterKode ? "{$arsip->masterKode->kode} — {$arsip->masterKode->nama}" : '-' }}</td>
                                <td>{{ $arsip->judul }}</td>
                                <td>{{ $arsip->tanggal?->format('d M Y') ?? '-' }}</td>
                                <td><span class="badge">{{ $arsip->status?->getLabel() ?? $arsip->status?->value ?? '-' }}</span></td>
                                <td>{{ $arsip->nasib_akhir?->getLabel() ?? $arsip->nasib_akhir?->value ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</body>
</html>
