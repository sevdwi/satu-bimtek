<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nomor_rak', 'opd_id', 'opd_induk_id'])]
class RakArsip extends Model
{
    use HasFactory;

    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class);
    }

    public function opdInduk(): BelongsTo
    {
        return $this->belongsTo(OpdInduk::class);
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
