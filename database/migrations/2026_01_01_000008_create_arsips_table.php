<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Perbaikan dari skema lama: dus_arsip_id & rak_arsip_id dulu cuma
     * index biasa TANPA foreign key, karena tabel rak_arsips/dus_arsips
     * di project lama dibuat SETELAH arsips (urutan migrasi kebalik).
     * Di sini rak_arsips & dus_arsips sudah dibuat lebih dulu, jadi
     * keduanya sekarang bisa jadi FK proper.
     */
    public function up(): void
    {
        Schema::create('arsips', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->string('file')->nullable();
            $table->year('tahun')->nullable();

            $table->foreignId('periode_id')->nullable()->constrained('periodes')->nullOnDelete();
            $table->date('tanggal')->nullable();
            $table->date('tanggal_musnah')->nullable();

            $table->foreignId('master_kode_id')->nullable()->constrained('master_kodes')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('opd_id')->nullable()->constrained('opds')->nullOnDelete();
            $table->foreignId('opd_induk_id')->constrained('opd_induks');

            // Sekarang FK proper (bukan cuma index seperti skema lama):
            $table->foreignId('dus_arsip_id')->nullable()->constrained('dus_arsips')->nullOnDelete();
            $table->foreignId('rak_arsip_id')->nullable()->constrained('rak_arsips')->nullOnDelete();

            $table->integer('aktif')->nullable();
            $table->integer('inaktif')->nullable();
            $table->string('nomor')->nullable();
            $table->string('status')->nullable();        // backed enum App\Enums\ArsipStatus
            $table->string('nasib_akhir')->nullable();    // backed enum App\Enums\NasibAkhir
            $table->string('korektor')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arsips');
    }
};
