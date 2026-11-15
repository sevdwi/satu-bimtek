<?php

namespace App\Http\Controllers;

use App\Models\DusArsip;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Halaman publik "Lacak Dus Arsip" -- TANPA LOGIN. Menerima salah satu dari:
 * (1) kode_qrcode pendek yang MUNCUL kalau QR code fisik di-scan (format
 * "{opd_induk_id}-{rak_arsip_id}-{Ymd}", mis. "3-12-20260915" -- lihat
 * App\Models\DusArsip::generateKodeQrcode() &
 * DusArsipRangkumanController::qrcode()), (2) ID Dus polos, atau (3) URL
 * lengkap hasil scan QR versi lama (isinya link ke route
 * 'dus-arsip.rangkuman') -- lalu redirect ke halaman rangkuman berkas dus
 * tersebut.
 *
 * Route ini SENGAJA publik, sepasang dengan 'dus-arsip.rangkuman' &
 * 'dus-arsip.qrcode' yang juga sudah dibuka publik di routes/web.php --
 * tujuannya staf yang cuma pegang dus fisik (tanpa akun panel admin) tetap
 * bisa lihat isinya tanpa harus login dulu.
 */
class DusArsipLacakController extends Controller
{
    public function index(): View
    {
        return view('dus.lacak');
    }

    public function cari(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kode' => ['required', 'string', 'max:2048'],
        ]);

        $dusArsipId = $this->resolveDusArsipId($data['kode']);

        if (! $dusArsipId) {
            return back()->withInput()->with(
                'lacak_error',
                'Kode/ID/URL tidak ditemukan. Pastikan Anda mengetik persis kode yang muncul saat QR discan, memasukkan ID Dus yang benar, atau menempel URL lengkap dari QR.',
            );
        }

        return redirect()->route('dus-arsip.rangkuman', $dusArsipId);
    }

    /**
     * Cari ID Dus Arsip dari input bebas -- dicoba berurutan dari yang
     * paling spesifik:
     * 1. URL lengkap hasil scan QR versi lama (domain APA PUN -- dicocokkan
     *    lewat pola path "/dus/{id}/rangkuman" saja, bukan host-nya, supaya
     *    tetap jalan walau QR lama dicetak pakai domain/port yang beda dari
     *    yang sekarang dipakai user).
     * 2. kode_qrcode pendek (exact match ke kolom kode_qrcode) -- kode yang
     *    MUNCUL kalau QR code fisik di-scan sekarang.
     * 3. ID Dus angka polos.
     */
    protected function resolveDusArsipId(string $kode): ?int
    {
        $kode = trim($kode);

        if (preg_match('#/dus/(\d+)/rangkuman#', $kode, $matches)) {
            return (int) $matches[1];
        }

        $dus = DusArsip::where('kode_qrcode', $kode)->first();

        if ($dus) {
            return $dus->id;
        }

        if (ctype_digit($kode) && DusArsip::whereKey($kode)->exists()) {
            return (int) $kode;
        }

        return null;
    }
}
