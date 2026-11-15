<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jumlah isi berkas (berapa lembar/item dokumen di dalam satu berkas
     * arsip), diinput manual di form Arsip.
     */
    public function up(): void
    {
        Schema::table('arsips', function (Blueprint $table) {
            $table->unsignedInteger('jumlah_isi_berkas')->nullable()->after('deskripsi');
        });
    }

    public function down(): void
    {
        Schema::table('arsips', function (Blueprint $table) {
            $table->dropColumn('jumlah_isi_berkas');
        });
    }
};
