<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opd_id')->nullable()->constrained('opds')->nullOnDelete();
            $table->year('tahun');
            // tahap = tahapan input arsip dalam satu tahun (mis. Tahap 1-4). Wajib diisi —
            // beda dengan skema lama yang bikin kolom ini nullable lalu jadi sumber bug
            // (lihat audit V1: index_tahap() bandingin string tanggal vs angka tahap).
            $table->unsignedTinyInteger('tahap');
            $table->string('status')->default('buka'); // backed enum App\Enums\PeriodeStatus (buka/tutup)
            $table->timestamps();

            // satu bidang cuma boleh punya satu baris periode per kombinasi tahun+tahap
            $table->unique(['opd_id', 'tahun', 'tahap'], 'periodes_opd_tahun_tahap_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periodes');
    }
};
