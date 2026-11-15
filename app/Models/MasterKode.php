<?php

namespace App\Models;

use App\Enums\NasibAkhir;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['kode', 'sort_key', 'parent_id', 'level', 'nama', 'aktif', 'inaktif', 'keterangan'])]
class MasterKode extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            // 'keterangan' = nasib akhir klasifikasi ini (musnah/permanen), bukan
            // teks bebas — dipakai enum yang sama dengan yang direncanakan buat
            // arsips.nasib_akhir biar konsisten satu istilah di seluruh aplikasi.
            'keterangan' => NasibAkhir::class,
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(MasterKode::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(MasterKode::class, 'parent_id');
    }

    public function arsips(): HasMany
    {
        return $this->hasMany(Arsip::class);
    }

    public function pemusnahanArsips(): HasMany
    {
        return $this->hasMany(PemusnahanArsip::class);
    }

    /**
     * Ganti kolom is_parent yang dulu ada di skema lama — dihitung dari
     * relasi children(), bukan kolom tersimpan yang bisa nggak sinkron.
     */
    public function isParent(): bool
    {
        return $this->children()->exists();
    }
}
