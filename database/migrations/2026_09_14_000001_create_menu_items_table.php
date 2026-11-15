<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Master menu sidebar (1 baris = 1 modul/Resource Filament) buat fitur
 * "Manajemen Menu" (lihat App\Models\MenuItem & App\Filament\Pages\
 * ManajemenMenu). `sort_order` menentukan urutan tampil di sidebar --
 * SATU urutan berlaku untuk semua role (yang beda antar role cuma boleh/
 * tidaknya modul itu tampil, lihat tabel menu_permissions).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->string('module_key')->unique();
            $table->string('label');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
