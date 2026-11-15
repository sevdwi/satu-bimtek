<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Matriks "modul X boleh dibuka role Y atau tidak" -- lihat menu_items
 * (migration sebelumnya) & App\Models\MenuPermission. Ini SATU sumber
 * kebenaran akses per role (menggantikan pengecekan role yang tadinya
 * di-hardcode di viewAny() tiap Policy) buat 10 modul yang ada sekarang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_item_id')->constrained('menu_items')->cascadeOnDelete();
            $table->string('role');
            $table->boolean('is_allowed')->default(false);
            $table->timestamps();

            $table->unique(['menu_item_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_permissions');
    }
};
