<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dus_arsips', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_dus')->nullable();
            $table->foreignId('rak_arsip_id')->constrained('rak_arsips'); // RESTRICT (bukan cascade) — sengaja, biar rak nggak bisa dihapus selama masih ada dus di dalamnya
            $table->foreignId('opd_id')->nullable()->constrained('opds')->nullOnDelete();
            $table->foreignId('opd_induk_id')->nullable()->constrained('opd_induks')->nullOnDelete();
            $table->string('qrcode')->default('');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dus_arsips');
    }
};
