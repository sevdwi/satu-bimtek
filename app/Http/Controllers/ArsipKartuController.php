<?php

namespace App\Http\Controllers;

use App\Models\Arsip;
use App\Models\MasterKode;
use Illuminate\Contracts\View\View;

/**
 * Halaman cetak "Kartu Deskripsi Arsip" per arsip -- replika tampilan lama
 * (lihat screenshot yang dikasih user: tabel Code/Klasifikasi/Sementara/
 * Difinitif + Isi Berkas + legenda Keterangan/Retensi), plus kontrol posisi
 * cetak A4 (Horizontal/Vertikal) buat nyocokin posisi cetak di kertas kartu
 * fisik. Halaman ini SENGAJA di luar shell panel Filament (tanpa
 * sidebar/topbar) -- cuma dilindungi middleware 'auth' biasa (lihat
 * routes/web.php), bukan resource/page Filament, karena cuma buat dicetak.
 */
class ArsipKartuController extends Controller
{
    public function show(Arsip $arsip): View
    {
        abort_unless(auth()->user()?->can('view', $arsip), 403);

        $arsip->load(['masterKode', 'opd.opdInduk', 'rakArsip', 'dusArsip', 'periode']);

        return view('arsip.kartu', [
            'arsip' => $arsip,
            'kodeRoot' => $this->resolveKodeRoot($arsip->masterKode),
        ]);
    }

    /**
     * Kolom "CODE" di kartu itu kode klasifikasi tingkat teratas (root),
     * bukan kode lengkapnya sendiri -- mis. arsip berkode "500.12.6.3"
     * kolom CODE-nya nampilin "500". Jalan ke atas lewat relasi parent()
     * sampai ketemu yang paling atas (parent_id null).
     */
    protected function resolveKodeRoot(?MasterKode $masterKode): ?MasterKode
    {
        $current = $masterKode;

        while ($current?->parent_id) {
            $current = $current->parent;
        }

        return $current;
    }
}
