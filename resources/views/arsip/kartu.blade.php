<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kartu Deskripsi Arsip — {{ $arsip->judul }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #1f2937;
            margin: 0;
            padding: 0;
            background: #f3f4f6;
        }

        .toolbar {
            padding: 24px 16px 16px;
            text-align: center;
            border-bottom: 1px solid #e5e7eb;
            background: #fff;
        }

        .toolbar h1 {
            font-size: 18px;
            font-weight: 600;
            margin: 0 0 16px;
        }

        .toolbar-controls {
            display: flex;
            align-items: flex-end;
            justify-content: center;
            gap: 24px;
            flex-wrap: wrap;
        }

        .toolbar-field {
            display: flex;
            flex-direction: column;
            gap: 4px;
            text-align: left;
        }

        .toolbar-field label {
            font-size: 12px;
            font-weight: 600;
            color: #374151;
        }

        .toolbar-field select {
            padding: 6px 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 13px;
            min-width: 110px;
        }

        .btn-cetak {
            padding: 8px 16px;
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-cetak:hover {
            background: #1d4ed8;
        }

        .toolbar-note {
            margin: 10px 0 0;
            font-size: 11px;
            color: #6b7280;
        }

        /*
         * Halaman cetak tetap A4 (sesuai judul "Pengaturan Posisi Cetak
         * A4") -- yang berukuran PAS mengikuti dokumen Word klien
         * (Kartu_Inaktif.docx: 157.02mm x 105.02mm, landscape) adalah KOTAK
         * KARTU-nya sendiri (.kartu-wrapper), yang diposisikan di dalam
         * lembar A4 itu lewat Posisi Horizontal/Vertikal. Ini biar 1 lembar
         * A4 bisa dipakai buat beberapa kali cetak kartu di posisi
         * berbeda-beda (hemat kertas kartu fisik).
         */
        #page-area {
            display: flex;
            min-height: calc(100vh - 105px);
            padding: 10mm;
        }

        .kartu-wrapper {
            /* Ukuran PERSIS dari Kartu_Inaktif.docx (pgSz 8902 x 5954 twips). */
            width: 157.02mm;
            height: 105.02mm;
            /* Margin dalam kartu = pgMar dokumen Word (top/right/bottom/left),
               kiri sengaja lebih lebar (1.5cm) -- ruang lubang binder. */
            padding: 6mm 10mm 5.01mm 15.01mm;
            background: #fff;
            border: 1px dashed #cbd5e1; /* cuma panduan preview di layar */
            display: flex;
            flex-direction: column;
        }

        .kartu-wrapper h2 {
            font-size: 10pt;
            font-weight: 700;
            margin: 0 0 2mm;
        }

        .kartu-box {
            border: 0.5pt solid #000;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        table.kartu-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        table.kartu-table th,
        table.kartu-table td {
            border: 0.5pt solid #000;
            border-top: none;
            padding: 1mm 1.5mm;
            text-align: center;
            font-size: 8.5pt;
            overflow: hidden;
        }

        table.kartu-table tr:first-child th { border-top: none; }
        table.kartu-table tr th:first-child,
        table.kartu-table tr td:first-child { border-left: none; }
        table.kartu-table tr th:last-child,
        table.kartu-table tr td:last-child { border-right: none; }

        table.kartu-table th {
            font-weight: 700;
            background: #fff;
        }

        /* Kolom CODE | KLASIFIKASI | SEMENTARA | DIFINITIF, proporsi persis
           dari grid kolom docx (1020:795+2722:1984:1560 twips). */
        table.kartu-table col.col-code { width: 12.6%; }
        table.kartu-table col.col-klasifikasi { width: 43.5%; }
        table.kartu-table col.col-sementara { width: 24.6%; }
        table.kartu-table col.col-difinitif { width: 19.3%; }

        .nilai-difinitif {
            color: #dc2626;
            font-weight: 600;
        }

        .kartu-isi {
            border-top: 0.5pt solid #000;
            padding: 1.5mm 2mm;
            font-size: 8pt;
            line-height: 1.35;
            flex: 1;
            overflow: hidden;
            /* Kolom flex: paragraf "Isi Berkas" di atas, daftar data lain
               (judul, tanggal, jumlah isi, dst) didorong menempel ke bawah
               lewat margin-top:auto di <ul> -- sesuai contoh kartu fisik. */
            display: flex;
            flex-direction: column;
        }

        .kartu-isi .isi-berkas {
            text-align: justify;
        }

        .kartu-isi .label {
            font-weight: 700;
        }

        .kartu-isi ul {
            margin: auto 0 0;
            padding-top: 1mm;
            padding-left: 3mm;
            list-style: none;
        }

        .kartu-isi ul li {
            position: relative;
            padding-left: 2.5mm;
        }

        .kartu-isi ul li::before {
            content: "-";
            position: absolute;
            left: 0;
        }

        .kartu-isi ul li.lokasi {
            font-style: italic;
        }

        /* Baris footer (Keterangan | Retensi | Korektor) -- pakai flexbox,
           bukan <table>, karena di dokumen aslinya garis vertikal ANTAR 3
           sel ini sengaja disembunyikan (tcBorders putih), jadi lebih
           gampang replikasinya tanpa border sama sekali antar kolom. */
        .kartu-legend {
            border-top: 0.5pt solid #000;
            padding: 1.5mm 2mm;
            font-size: 7.5pt;
            display: flex;
            gap: 2mm;
        }

        .kartu-legend > div {
            min-width: 0;
        }

        /* Proporsi persis dari grid docx: Keterangan 1815, Retensi 2722,
           Korektor 3544 (dari total 8081 twips). */
        .legend-keterangan { flex: 0 0 22.4%; }
        .legend-retensi { flex: 0 0 33.6%; }
        .legend-korektor { flex: 0 0 43.8%; text-align: center; }

        .legend-title {
            font-weight: 700;
            display: block;
            margin-bottom: 0.5mm;
        }

        .kartu-legend ol {
            margin: 0;
            padding-left: 3mm;
        }

        .kartu-legend ol li {
            margin-bottom: 0.3mm;
        }

        /* Opsi Keterangan arsip yang cocok sama arsips.keterangan dikasih
           lingkaran -- persis konvensi form fisik "lingkari yang sesuai". */
        .opsi-terpilih {
            display: inline-block;
            border: 1pt solid #000;
            border-radius: 999px;
            padding: 0 1.5mm;
        }

        .retensi-lines {
            font-size: 8.5pt;
        }

        .korektor-space {
            height: 5mm;
        }

        .korektor-line {
            text-align: center;
            font-size: 8pt;
        }

        @media print {
            body { background: #fff; }
            .no-print { display: none !important; }
            #page-area { min-height: auto; padding: 0; }
            .kartu-wrapper { border-color: transparent; }
            @page { size: A4; margin: 0; }
        }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <h1>Pengaturan Posisi Cetak A4</h1>
        <div class="toolbar-controls">
            <div class="toolbar-field">
                <label for="posisiHorizontal">Posisi Horizontal</label>
                <select id="posisiHorizontal">
                    <option value="kiri" selected>Kiri</option>
                    <option value="tengah">Tengah</option>
                    <option value="kanan">Kanan</option>
                </select>
            </div>
            <div class="toolbar-field">
                <label for="posisiVertikal">Posisi Vertikal</label>
                <select id="posisiVertikal">
                    <option value="atas" selected>Atas</option>
                    <option value="tengah">Tengah</option>
                    <option value="bawah">Bawah</option>
                </select>
            </div>
            <button type="button" class="btn-cetak" onclick="window.print()">🖶 Cetak Kartu</button>
        </div>
        <p class="toolbar-note">Ukuran kartu 15,7 cm x 10,5 cm, persis mengikuti template Word "Kartu Inaktif".</p>
    </div>

    <div id="page-area">
        <div class="kartu-wrapper">
            <h2>Kartu Deskripsi Arsip</h2>

            <div class="kartu-box">
                <table class="kartu-table">
                    <colgroup>
                        <col class="col-code">
                        <col class="col-klasifikasi">
                        <col class="col-sementara">
                        <col class="col-difinitif">
                    </colgroup>
                    <tr>
                        <th>CODE</th>
                        <th>KLASIFIKASI</th>
                        <th>SEMENTARA</th>
                        <th>DIFINITIF</th>
                    </tr>
                    <tr>
                        <td>{{ $kodeRoot?->kode ?? '-' }}</td>
                        <td>{{ $arsip->masterKode?->kode ?? '-' }}</td>
                        {{-- Nomor sementara otomatis "{alias_user}-{urut per periode}" (arsips.nomor_sementara) --}}
                        <td>{{ $arsip->nomor_sementara ?? '-' }}</td>
                        <td class="nilai-difinitif"></td>
                    </tr>
                </table>

                <div class="kartu-isi">
                    {{-- Isi Berkas = deskripsi arsip (arsips.deskripsi) --}}
                    <div class="isi-berkas">
                        <span class="label">Isi Berkas :</span> {{ $arsip->deskripsi ?: '-' }}
                    </div>

                    @php
                        // Tanggal arsip ditulis lengkap "tanggal bulan tahun" dalam
                        // bahasa Indonesia (mis. "12 Maret 2025"). Kalau tanggalnya
                        // kosong, minimal tampilkan tahunnya saja.
                        $tanggalArsip = $arsip->tanggal
                            ? $arsip->tanggal->copy()->locale('id')->translatedFormat('d F Y')
                            : ($arsip->tahun ?? '-');
                    @endphp
                    <ul>
                        <li>{{ $arsip->judul }}</li>
                        <li>Tanggal : {{ $tanggalArsip }}</li>
                        <li>Jumlah Berkas : {{ $arsip->jumlah_isi_berkas ?? '-' }}</li>
                        <li>Nasib Akhir : {{ $arsip->nasib_akhir?->getLabel() ?? '-' }}</li>
                        <!-- <li>Rak / Dus : {{ $arsip->rakArsip?->nomor_rak ?? '-' }} / {{ $arsip->dusArsip?->nomor_dus ?? '-' }}</li> -->
                        <li class="lokasi">{{ $arsip->opd?->unit_kerja }} - {{ $arsip->opd?->opdInduk?->instansi }}</li>
                    </ul>
                </div>

                <div class="kartu-legend">
                    @php
                        $opsiKeterangan = \App\Enums\ArsipKeterangan::cases();
                    @endphp
                    <div class="legend-keterangan">
                        <span class="legend-title">Keterangan</span>
                        <ol>
                            @foreach ($opsiKeterangan as $i => $opsi)
                                <li>
                                    @if ($arsip->keterangan === $opsi)
                                        <span class="opsi-terpilih">{{ $opsi->getLabel() }}</span>
                                    @else
                                        {{ $opsi->getLabel() }}
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    </div>
                    <div class="legend-retensi">
                        <span class="legend-title">Retensi</span>
                        <ol class="retensi-lines">
                            <li>Aktif&nbsp;&nbsp;&nbsp;: {{ $arsip->aktif ?? '-' }}Th</li>
                            <li>Inaktif : {{ $arsip->inaktif ?? '-' }}Th</li>
                            <li>Ket&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: di Th {{ $arsip->tanggal_musnah?->year ?? '-' }}</li>
                        </ol>
                    </div>
                    <div class="legend-korektor">
                        <span class="legend-title">Korektor</span>
                        <div class="korektor-space"></div>
                        <div class="korektor-line">({{ $arsip->korektor ?: '.......................' }})</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const hMap = { kiri: 'flex-start', tengah: 'center', kanan: 'flex-end' };
        const vMap = { atas: 'flex-start', tengah: 'center', bawah: 'flex-end' };

        function updatePosisiCetak() {
            const h = document.getElementById('posisiHorizontal').value;
            const v = document.getElementById('posisiVertikal').value;
            const area = document.getElementById('page-area');

            area.style.justifyContent = hMap[h] ?? 'flex-start';
            area.style.alignItems = vMap[v] ?? 'flex-start';
        }

        document.getElementById('posisiHorizontal').addEventListener('change', updatePosisiCetak);
        document.getElementById('posisiVertikal').addEventListener('change', updatePosisiCetak);
        updatePosisiCetak();
    </script>
</body>
</html>
