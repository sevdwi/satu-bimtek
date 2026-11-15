<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Jenis berkas fisik arsip (dicetak di kolom "Keterangan" tabel arsips &
 * dilingkari di Kartu Deskripsi Arsip -- lihat resources/views/arsip/kartu.blade.php).
 * Tidak ada hubungannya dengan MasterKode::$keterangan (itu App\Enums\NasibAkhir,
 * nasib akhir klasifikasi arsip -- musnah/permanen, konsep yang sama sekali
 * beda meski kebetulan sama-sama nama kolom "keterangan").
 */
enum ArsipKeterangan: string implements HasColor, HasLabel
{
    case Asli = 'asli';
    case Tembusan = 'tembusan';
    case Fotocopy = 'fotocopy';
    case AsliTembusan = 'asli_tembusan';
    case FotocopyTembusan = 'fotocopy_tembusan';

    public function getLabel(): string
    {
        return match ($this) {
            self::Asli => 'Asli',
            self::Tembusan => 'Tembusan',
            self::Fotocopy => 'Fotocopy',
            self::AsliTembusan => 'Asli Tembusan',
            self::FotocopyTembusan => 'Fotocopy Tembusan',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Asli => 'success',
            self::Tembusan => 'info',
            self::Fotocopy => 'warning',
            self::AsliTembusan => 'primary',
            self::FotocopyTembusan => 'gray',
        };
    }
}
