<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opd_induks', function (Blueprint $table) {
            $table->id();
            $table->string('kode_instansi');
            $table->string('instansi');
            $table->string('singkatan_instansi');
            // Ditambah timestamps() — tidak ada di skema lama, padahal
            // berguna untuk sorting/audit di tabel Filament.
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opd_induks');
    }
};
