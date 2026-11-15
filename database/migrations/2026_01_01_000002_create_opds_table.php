<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opd_induk_id')
                ->nullable()
                ->constrained('opd_induks')
                ->nullOnDelete(); // skema lama: FK tanpa ON DELETE meski kolomnya nullable — dirapikan
            $table->string('kode_instansi');
            $table->string('unit_kerja');
            $table->string('singkatan_uk');
            $table->string('instansi');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opds');
    }
};
