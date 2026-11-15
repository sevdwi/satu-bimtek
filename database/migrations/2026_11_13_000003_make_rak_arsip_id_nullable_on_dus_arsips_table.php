<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nomor Rak di Dus Arsip jadi OPSIONAL -- dus boleh dibuat dulu tanpa rak
 * (rak bisa diisi belakangan lewat Edit Dus). Kalau rak kosong, bidang/dinas
 * dus diambil dari bidang user (Pengolah) atau bidang yang dipilih di form
 * (Admin Dinas/Superadmin) -- lihat DusArsipResource::resolveLokasi().
 *
 * Cuma mengubah kolom jadi nullable (data yang ada TIDAK berubah).
 * Foreign key ke rak_arsips tetap ada & tetap RESTRICT on delete.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dus_arsips', function (Blueprint $table) {
            $table->unsignedBigInteger('rak_arsip_id')->nullable()->change();
        });
    }

    /**
     * Rollback akan GAGAL kalau sudah ada dus tanpa rak -- isi dulu rak-nya
     * (atau hapus dus tersebut) sebelum rollback.
     */
    public function down(): void
    {
        Schema::table('dus_arsips', function (Blueprint $table) {
            $table->unsignedBigInteger('rak_arsip_id')->nullable(false)->change();
        });
    }
};
