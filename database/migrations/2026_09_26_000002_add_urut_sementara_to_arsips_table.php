<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nomor urut arsip DI DALAM SATU PERIODE (1, 2, 3, ... reset ke 1 tiap
     * periode beda) -- bagian belakang Nomor Sementara "{alias_user}-{urut}".
     * Disimpan di kolom sendiri (bukan di-parse dari teks nomor_sementara)
     * biar hitungan urut berikutnya tetap akurat walau alias user berubah.
     *
     * Data lama diisi lewat: php artisan arsip:susun-nomor-sementara
     */
    public function up(): void
    {
        Schema::table('arsips', function (Blueprint $table) {
            $table->unsignedInteger('urut_sementara')->nullable()->after('nomor_sementara');
            $table->index(['periode_id', 'urut_sementara'], 'arsips_periode_urut_sementara_index');
        });
    }

    public function down(): void
    {
        Schema::table('arsips', function (Blueprint $table) {
            $table->dropIndex('arsips_periode_urut_sementara_index');
            $table->dropColumn('urut_sementara');
        });
    }
};
