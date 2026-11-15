<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fitur "Repositori Dokumentasi" (/repo_dok, semua user login) -- struktur
 * folder BERJENJANG (self-referencing tree), dikelola dinamis oleh
 * Superadmin lewat App\Filament\Resources\RepoDokFolderResource. Isi awal
 * (01. Dokumen Aplikasi s.d. 06. Terminasi) di-seed lewat
 * Database\Seeders\RepoDokFolderSeeder, TAPI Superadmin bebas menambah/
 * mengubah/menghapus foldernya sendiri lewat UI -- struktur ini SENGAJA
 * tidak di-hardcode di kode.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repo_dok_folders', function (Blueprint $table) {
            $table->id();
            // Nullable = folder tingkat atas. onDelete cascade -- hapus
            // folder induk otomatis hapus semua sub-foldernya (dan lewat
            // FK di repo_dok_files, semua berkas/tautan di dalamnya juga
            // ikut terhapus, lihat migration tabel itu).
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('repo_dok_folders')
                ->cascadeOnDelete();
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            // Urutan tampil manual (drag di tabel Filament) -- default
            // urutan alfabetis nama kalau semua nilainya sama (0).
            $table->integer('urutan')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repo_dok_folders');
    }
};
