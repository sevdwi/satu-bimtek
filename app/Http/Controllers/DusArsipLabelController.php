<?php

namespace App\Http\Controllers;

use App\Models\DusArsip;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Halaman cetak "Label Dus Arsip" -- satu atau banyak kartu label sekaligus
 * dalam satu halaman (siap digunting/ditempel di dus fisik), dipakai lewat
 * tombol "Cetak Label" (per baris & bulk action) di
 * App\Filament\Resources\DusArsipResource.
 *
 * Formatnya mengikuti label boks arsip fisik yang sudah dipakai
 * sebelumnya (kotak bergaris ganda: INSTANSI / NOMOR BERKAS / NOMOR BOKS /
 * Tahun), ditambah gambar QR code dus (lihat
 * DusArsipRangkumanController::qrcode()) di pojok kartu. Kolom "NOMOR
 * BERKAS" SENGAJA dikosongkan -- diisi tulis tangan setelah berkas di
 * dalam dus ditentukan nomornya, karena bisa berubah-ubah setelah dus
 * dicetak labelnya.
 *
 * DILINDUNGI middleware 'auth' (lihat routes/web.php) -- beda dari
 * 'dus-arsip.rangkuman'/'dus-arsip.qrcode' yang sengaja publik, cetak label
 * ini murni alat bantu admin/pengolah di dalam panel, bukan buat diakses
 * pihak luar.
 */
class DusArsipLabelController extends Controller
{
    public function cetak(Request $request): View
    {
        $ids = collect(explode(',', (string) $request->query('ids', '')))
            ->map(fn ($id) => trim($id))
            ->filter(fn ($id) => ctype_digit($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        abort_if($ids->isEmpty(), 404, 'Tidak ada Dus Arsip yang dipilih untuk dicetak labelnya.');

        $dusArsips = DusArsip::query()
            ->with(['opd.opdInduk'])
            ->whereIn('id', $ids)
            ->get()
            // Urutan dikembalikan sesuai urutan pemilihan/ids di URL, bukan
            // urutan default query, biar sesuai ekspektasi pas cetak massal
            // dari tabel yang sudah diurutkan/difilter.
            ->sortBy(fn (DusArsip $dus): int => $ids->search($dus->id))
            ->values();

        return view('dus.label', ['dusArsips' => $dusArsips]);
    }
}
