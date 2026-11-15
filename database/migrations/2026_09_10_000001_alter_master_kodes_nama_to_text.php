<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Migration create_master_kodes_table awalnya bikin `nama` sebagai
     * string(255) — ternyata pas import data asli (master_kodes V1,
     * 2.952 baris) ada 24 baris yang uraiannya lebih panjang dari 255
     * karakter (paling panjang 865 karakter, kode 900.1.9), sesuai
     * skema aslinya yang memang `text NOT NULL`. Ketauan pas seeding
     * gagal dengan error "Data too long for column 'nama'".
     *
     * Pakai DB::statement (raw SQL) di sini, bukan Schema::table(...)
     * ->change(), karena project ini belum pasang doctrine/dbal.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE master_kodes MODIFY nama TEXT NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE master_kodes MODIFY nama VARCHAR(255) NOT NULL');
    }
};
