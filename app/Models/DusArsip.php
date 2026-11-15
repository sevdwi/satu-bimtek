<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nomor_dus', 'rak_arsip_id', 'opd_id', 'opd_induk_id', 'qrcode', 'kode_qrcode'])]
class DusArsip extends Model
{
    use HasFactory;

    /**
     * Bikin kode pendek unik buat di-encode ke gambar QR code dus ini --
     * format "{opd_induk_id}-{rak_arsip_id}-{Ymd}" (mis. "3-12-20260915"),
     * dipanggil dari CreateDusArsip::afterCreate() / EditDusArsip::afterSave()
     * kalau dus ini belum punya kode_qrcode. SEKALI dibuat, kode ini TIDAK
     * pernah diubah lagi oleh sistem -- sudah ketempel/tercetak fisik di
     * dus, jadi harus tetap valid walau rak/dinas dus ini kelak dipindah.
     *
     * Tambahan angka urut ("-2", "-3", dst.) kalau kombinasi dasarnya
     * (dinas+rak+tanggal yang sama) sudah dipakai dus lain -- jaga-jaga
     * kalau ada lebih dari satu dus baru dibuat untuk rak yang sama di
     * hari yang sama.
     *
     * $tanggal opsional -- default "sekarang" (dipakai CreateDusArsip/
     * EditDusArsip), tapi bisa diisi tanggal lain buat backfill data lama
     * (lihat App\Console\Commands\GenerateKodeQrcodeDusArsip, yang pakai
     * created_at dus itu sendiri biar kodenya mencerminkan tanggal dus
     * SEBENARNYA dibuat, bukan tanggal command-nya dijalankan).
     */
    public static function generateKodeQrcode(?int $opdIndukId, ?int $rakArsipId, ?Carbon $tanggal = null): string
    {
        $tanggal ??= now();

        // Dus tanpa rak (Nomor Rak opsional) -> bagian rak diisi 0, mis. "3-0-20261113".
        $base = sprintf('%s-%d-%s', $opdIndukId ?? '0', $rakArsipId ?? 0, $tanggal->format('Ymd'));
        $kode = $base;
        $suffix = 1;

        while (static::query()->where('kode_qrcode', $kode)->exists()) {
            $suffix++;
            $kode = "{$base}-{$suffix}";
        }

        return $kode;
    }

    public function rakArsip(): BelongsTo
    {
        return $this->belongsTo(RakArsip::class);
    }

    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class);
    }

    public function opdInduk(): BelongsTo
    {
        return $this->belongsTo(OpdInduk::class);
    }

    public function arsips(): HasMany
    {
        return $this->hasMany(Arsip::class);
    }
}
