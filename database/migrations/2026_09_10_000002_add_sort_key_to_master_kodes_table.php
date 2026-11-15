<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom bantu buat urutan tampilan sesuai hierarki (root -> anak ->
     * cucu -> next root, dst). Nggak bisa cuma ORDER BY kode (string biasa)
     * karena ada campuran lebar digit per segmen (mis. "000.1.9" vs
     * "000.1.10" — urut string biasa naro "...10" SEBELUM "...9").
     * sort_key = tiap segmen kode di-zero-pad 4 digit, jadi ORDER BY
     * sort_key otomatis urut benar & tetap ngelompok per cabang. Dihitung
     * & diisi di MasterKodeSeeder, bukan lewat kolom generated MySQL,
     * biar nggak gantung ke versi MySQL tertentu.
     */
    public function up(): void
    {
        Schema::table('master_kodes', function (Blueprint $table): void {
            $table->string('sort_key')->nullable()->after('kode')->index();
        });
    }

    public function down(): void
    {
        Schema::table('master_kodes', function (Blueprint $table): void {
            $table->dropColumn('sort_key');
        });
    }
};
