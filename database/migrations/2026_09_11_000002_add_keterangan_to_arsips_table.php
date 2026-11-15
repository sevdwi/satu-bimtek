<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom baru "Keterangan" di arsips: jenis berkas fisik (Asli/Tembusan/
     * Fotocopy/dst -- lihat App\Enums\ArsipKeterangan), BUKAN kolom
     * "keterangan" yang ada di master_kodes (itu nasib akhir klasifikasi,
     * pakai App\Enums\NasibAkhir -- beda arti sama sekali walau nama
     * kolomnya sama, tabelnya beda). Dipakai di Kartu Deskripsi Arsip buat
     * nunjukin yang mana yang berlaku dari daftar pilihan (lihat
     * ArsipKartuController / resources/views/arsip/kartu.blade.php).
     */
    public function up(): void
    {
        Schema::table('arsips', function (Blueprint $table) {
            $table->string('keterangan')->nullable()->after('korektor');
        });
    }

    public function down(): void
    {
        Schema::table('arsips', function (Blueprint $table) {
            $table->dropColumn('keterangan');
        });
    }
};
