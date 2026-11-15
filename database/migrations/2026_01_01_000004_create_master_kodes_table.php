<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catatan perbaikan dari skema lama: kolom `is_parent` DIHAPUS — nilainya
     * murni turunan dari parent_id (true kalau ada baris lain yang
     * parent_id-nya nunjuk ke baris ini), jadi redundan dan rawan nggak
     * sinkron kalau lupa di-maintain manual. Model MasterKode nanti
     * sediakan accessor/scope untuk itu, bukan kolom tersimpan.
     * `level` tetap dipertahankan karena itu denormalisasi yang wajar
     * (dipakai buat filter cepat "semua kode level 1", dst).
     */
    public function up(): void
    {
        Schema::create('master_kodes', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->foreignId('parent_id')->nullable()->constrained('master_kodes')->nullOnDelete();
            $table->unsignedInteger('level')->default(1);
            $table->string('nama');
            $table->integer('aktif')->nullable();
            $table->integer('inaktif')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_kodes');
    }
};
