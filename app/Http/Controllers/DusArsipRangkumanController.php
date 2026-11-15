<?php

namespace App\Http\Controllers;

use App\Models\DusArsip;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;

/**
 * Halaman rangkuman berkas per Dus Arsip + generator QR code-nya.
 *
 * QR code (qrcode()) SEKARANG isinya kode PENDEK dus.kode_qrcode (mis.
 * "3-12-20260915"), BUKAN URL -- sengaja diubah dari versi awal supaya
 * begitu discan, HP menampilkan kode itu sebagai TEKS (bisa dibaca/
 * disalin), lalu diketik manual di halaman publik "Lacak Dus Arsip"
 * (/lacak-dus, lihat DusArsipLacakController). Ini penting SELAMA
 * aplikasi masih jalan di localhost/jaringan lokal -- HP yang scan QR
 * biasanya tidak bisa langsung membuka URL localhost server. Ditempel/
 * dicetak di dus fisik lewat halaman rangkuman (show()) di bawah.
 *
 * SENGAJA PUBLIK (tanpa middleware 'auth' maupun otorisasi per-record lewat
 * DusArsipPolicy) -- lihat routes/web.php & App\Http\Controllers\
 * DusArsipLacakController. Tujuan awal QR code ini memang supaya staf yang
 * cuma pegang dus fisik (tanpa akun panel admin) tetap bisa lihat isinya
 * cukup dengan scan, tanpa harus login dulu. Kalau suatu saat perlu
 * dibatasi lagi (mis. wajib login), tinggal bungkus balik route-nya dengan
 * middleware('auth') di routes/web.php dan kembalikan baris
 * abort_unless(auth()->user()?->can('view', $dusArsip), 403) di sini &
 * qrcode() di bawah.
 */
class DusArsipRangkumanController extends Controller
{
    public function show(DusArsip $dusArsip): View
    {
        $dusArsip->load(['rakArsip', 'opd.opdInduk']);

        $arsips = $dusArsip->arsips()
            ->with(['masterKode', 'periode'])
            ->orderBy('nomor')
            ->get();

        return view('dus.rangkuman', [
            'dusArsip' => $dusArsip,
            'arsips' => $arsips,
        ]);
    }

    /**
     * Gambar QR code (PNG) yang isinya kode_qrcode dus ini (teks pendek,
     * BUKAN URL -- lihat catatan di atas). Dipakai sebagai <img src="...">
     * di halaman rangkuman itu sendiri (untuk dicetak/diunduh) dan lewat
     * action "QR Code" di DusArsipResource (lihat
     * App\Filament\Resources\DusArsipResource).
     */
    public function qrcode(DusArsip $dusArsip): Response
    {
        $options = new QROptions([
            'outputType' => QROutputInterface::GDIMAGE_PNG,
            'outputBase64' => false,
            'scale' => 8,
            'imageTransparent' => false,
        ]);

        // Fallback ke URL rangkuman kalau kode_qrcode kosong -- jaga-jaga
        // buat dus lama yang dibuat sebelum kolom ini ada & belum sempat
        // disave ulang lewat form Edit (lihat EditDusArsip::afterSave()).
        $payload = $dusArsip->kode_qrcode ?: route('dus-arsip.rangkuman', $dusArsip);

        $png = (new QRCode($options))->render($payload);

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="qrcode-dus-'.$dusArsip->id.'.png"',
        ]);
    }
}
