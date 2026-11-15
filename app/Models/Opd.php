<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['opd_induk_id', 'kode_instansi', 'unit_kerja', 'singkatan_uk', 'instansi'])]
class Opd extends Model
{
    use HasFactory;

    public function opdInduk(): BelongsTo
    {
        return $this->belongsTo(OpdInduk::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function periodes(): HasMany
    {
        return $this->hasMany(Periode::class);
    }

    public function rakArsips(): HasMany
    {
        return $this->hasMany(RakArsip::class);
    }

    public function dusArsips(): HasMany
    {
        return $this->hasMany(DusArsip::class);
    }

    public function arsips(): HasMany
    {
        return $this->hasMany(Arsip::class);
    }

    /**
     * True kalau bidang ini masih dipakai data lain (pengguna, periode, rak,
     * dus, atau arsip). Bidang seperti ini tidak boleh dihapus -- semua FK
     * opd_id di tabel lain nullOnDelete, jadi data itu bakal jadi "yatim".
     * Dipakai tombol Delete (tabel, halaman Edit, & bulk) di OpdResource.
     */
    public function punyaDataTerkait(): bool
    {
        return $this->users()->exists()
            || $this->periodes()->exists()
            || $this->rakArsips()->exists()
            || $this->dusArsips()->exists()
            || $this->arsips()->exists();
    }
}
