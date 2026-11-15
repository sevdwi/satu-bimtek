<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Isi tiap folder Repositori Dokumentasi (lihat migration
 * create_repo_dok_folders_table) -- SATU baris bisa berupa (1) berkas
 * yang diunggah ke server (tipe='file', file_path terisi, disk 'public'
 * -- lihat App\Filament\Resources\RepoDokFolderResource\RelationManagers\
 * FilesRelationManager) ATAU (2) tautan/referensi ke sumber luar
 * (tipe='link', link_url terisi) -- lihat App\Enums\RepoDokFileType.
 * Kolom file_path & link_url SENGAJA dua-duanya nullable (bukan salah
 * satu NOT NULL) karena hanya salah satunya yang relevan sesuai `tipe`,
 * validasinya di form Filament (FilesRelationManager), bukan di skema DB.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repo_dok_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folder_id')
                ->constrained('repo_dok_folders')
                ->cascadeOnDelete();
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->string('tipe')->default('file');
            $table->string('file_path')->nullable();
            $table->string('link_url')->nullable();
            // Siapa yang mengunggah/menambahkan baris ini -- nullable &
            // nullOnDelete (bukan cascade) supaya berkas yang sudah
            // diunggah TIDAK ikut terhapus kalau user pengunggahnya
            // dihapus dari sistem, cuma jejak siapa pengunggahnya hilang.
            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->integer('urutan')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repo_dok_files');
    }
};
