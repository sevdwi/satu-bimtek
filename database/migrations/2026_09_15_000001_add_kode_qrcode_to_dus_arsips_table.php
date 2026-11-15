<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom baru "kode_qrcode" -- kode PENDEK & bisa diketik manual, format
 * "{opd_induk_id}-{rak_arsip_id}-{Ymd}" (mis. "3-12-20260915"), dibuat
 * otomatis lewat App\Models\DusArsip::generateKodeQrcode() (lihat
 * CreateDusArsip/EditDusArsip).
 *
 * BEDA dari kolom "qrcode" yang sudah ada (isinya URL lengkap ke halaman
 * rangkuman): kode ini yang di-encode ke GAMBAR QR (lihat
 * DusArsipRangkumanController::qrcode()), supaya begitu discan, HP
 * langsung menampilkan kode pendeknya sebagai teks (bukan otomatis buka
 * halaman) -- kode itu lalu diketik manual di halaman publik "Lacak Dus
 * Arsip" (/lacak-dus, TANPA LOGIN). Ini sengaja, karena selama aplikasi
 * baru jalan di localhost/jaringan lokal, HP yang scan QR biasanya TIDAK
 * bisa langsung membuka URL localhost tersebut.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dus_arsips', function (Blueprint $table) {
            $table->string('kode_qrcode')->nullable()->unique()->after('qrcode');
        });
    }

    public function down(): void
    {
        Schema::table('dus_arsips', function (Blueprint $table) {
            $table->dropColumn('kode_qrcode');
        });
    }
};
