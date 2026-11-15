<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Label Dus Arsip</title>
    <style>
        {{--
            Desain kartu label ini MENIRU persis format label boks arsip
            fisik yang sudah dipakai sebelumnya (kotak bergaris ganda berisi
            INSTANSI / NOMOR BERKAS / NOMOR BOKS / Tahun), sengaja
            dipertahankan supaya label baru masih konsisten dengan label
            lama yang sudah tertempel di dus-dus sebelumnya -- ditambah
            gambar QR code di pojok kanan kartu.
        --}}
        * { box-sizing: border-box; }

        body {
            font-family: 'Times New Roman', Times, serif;
            margin: 0;
            padding: 18px;
            background: #f3f4f6;
        }

        .toolbar {
            max-width: 720px;
            margin: 0 auto 16px;
            text-align: right;
        }

        .toolbar button {
            padding: 8px 16px;
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            font-family: Arial, Helvetica, sans-serif;
            cursor: pointer;
        }

        .sheet {
            max-width: 720px;
            margin: 0 auto;
        }

        .label-card {
            border: 3px double #000;
            padding: 14px 18px;
            margin-bottom: 18px;
            background: #fff;
            display: flex;
            justify-content: space-between;
            gap: 16px;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .label-text {
            flex: 1;
            font-size: 15px;
            line-height: 1.55;
        }

        .label-text .baris {
            display: flex;
        }

        .label-text .label-kolom {
            font-weight: 700;
            width: 150px;
            flex-shrink: 0;
        }

        .label-text .label-isi {
            font-weight: 700;
        }

        .label-text .label-isi.kosong {
            display: inline-block;
            min-width: 160px;
            border-bottom: 1px solid #000;
        }

        .label-text .tahun {
            text-align: right;
            font-weight: 700;
            margin-top: 6px;
        }

        .label-qr {
            flex-shrink: 0;
            text-align: center;
            width: 110px;
        }

        .label-qr img {
            width: 100px;
            height: 100px;
        }

        .label-qr .kode-qrcode {
            display: block;
            margin-top: 4px;
            font-family: 'Courier New', monospace;
            font-size: 10px;
            font-weight: 700;
            word-break: break-all;
        }

        @media print {
            body { background: #fff; padding: 0; }
            .toolbar { display: none !important; }
            .sheet { max-width: none; }
            .label-card { margin: 0 0 14px; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Cetak Halaman</button>
    </div>

    <div class="sheet">
        @foreach ($dusArsips as $dusArsip)
            <div class="label-card">
                <div class="label-text">
                    <div class="baris">
                        <span class="label-kolom">INSTANSI</span>
                        <span>:&nbsp;</span>
                        <span class="label-isi">
                            {{ strtoupper($dusArsip->opd?->unit_kerja ?? '-') }}<br>
                            {{ strtoupper($dusArsip->opd?->opdInduk?->instansi ?? '-') }}
                        </span>
                    </div>

                    {{-- SENGAJA dikosongkan (diisi tulis tangan) -- lihat
                         catatan di DusArsipLabelController. --}}
                    <div class="baris" style="margin-top: 8px;">
                        <span class="label-kolom">NOMOR BERKAS</span>
                        <span>:&nbsp;</span>
                        <span class="label-isi kosong">&nbsp;</span>
                    </div>

                    <div class="baris" style="margin-top: 8px;">
                        <span class="label-kolom">NOMOR BOKS</span>
                        <span>:&nbsp;</span>
                        <span class="label-isi">{{ $dusArsip->nomor_dus ?? $dusArsip->id }}</span>
                    </div>

                    <div class="tahun">Tahun&nbsp;&nbsp;{{ $dusArsip->created_at?->format('Y') ?? date('Y') }}</div>
                </div>

                <div class="label-qr">
                    <img src="{{ route('dus-arsip.qrcode', $dusArsip) }}" alt="QR Code Dus {{ $dusArsip->nomor_dus }}">
                    @if ($dusArsip->kode_qrcode)
                        <span class="kode-qrcode">{{ $dusArsip->kode_qrcode }}</span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</body>
</html>
