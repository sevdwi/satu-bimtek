<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom "Nomor Sementara" — terisi otomatis "{id user pembuat}-{id
     * arsip}" (contoh: "1-2" = dibuat user id 1, arsip id 2) begitu arsip
     * diinput, lihat App\Models\Arsip::booted(). Beda dengan kolom `nomor`
     * (Nomor Definitif) yang baru terisi setelah tahapnya dimanufer.
     */
    public function up(): void
    {
        Schema::table('arsips', function (Blueprint $table) {
            $table->string('nomor_sementara')->nullable()->after('nomor');
        });
    }

    public function down(): void
    {
        Schema::table('arsips', function (Blueprint $table) {
            $table->dropColumn('nomor_sementara');
        });
    }
};
