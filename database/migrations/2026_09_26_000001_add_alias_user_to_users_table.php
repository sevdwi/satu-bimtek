<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Alias user -- dipakai sebagai awalan Nomor Sementara arsip
     * ("{alias_user}-{urut}", lihat App\Models\Arsip::isiNomorSementara()).
     * Diisi/diubah sendiri oleh tiap user lewat halaman Profil
     * (App\Filament\Pages\Auth\EditProfile). Unik biar awalan nomor
     * sementara nggak ketuker antar user.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('alias_user', 30)->nullable()->unique()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['alias_user']);
            $table->dropColumn('alias_user');
        });
    }
};
