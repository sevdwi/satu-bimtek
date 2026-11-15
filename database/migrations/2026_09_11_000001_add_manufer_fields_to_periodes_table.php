<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Field buat fitur "Manufer": begitu Admin Dinas/Pengolah menjalankan
     * Manufer atas suatu Periode (kombinasi opd+tahun+tahap), periode itu
     * dikunci -- arsip di dalamnya tidak bisa ditambah/diedit/dihapus/
     * dimanufer ulang sampai dibuka lagi oleh Superadmin (lihat ArsipPolicy,
     * ListInaktif::runManufer(), dan PeriodeResource action "Buka Kembali").
     *
     * manufer_at null = tahap masih terbuka seperti biasa.
     * manufer_at terisi = tahap terkunci sejak kapan, dan manufer_by mencatat
     * siapa yang menjalankannya.
     */
    public function up(): void
    {
        Schema::table('periodes', function (Blueprint $table) {
            $table->timestamp('manufer_at')->nullable()->after('status');
            $table->foreignId('manufer_by')->nullable()->after('manufer_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('periodes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('manufer_by');
            $table->dropColumn('manufer_at');
        });
    }
};
