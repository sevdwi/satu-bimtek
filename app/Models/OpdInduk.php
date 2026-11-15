<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['kode_instansi', 'instansi', 'singkatan_instansi'])]
class OpdInduk extends Model
{
    use HasFactory;

    public function opds(): HasMany
    {
        return $this->hasMany(Opd::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
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
}
