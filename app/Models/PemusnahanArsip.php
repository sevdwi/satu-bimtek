<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['id_arsip', 'pemusnahan', 'no_ba', 'file_ba', 'master_kode_id', 'opd_id', 'korektor', 'created_by', 'status'])]
class PemusnahanArsip extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'pemusnahan' => 'date',
        ];
    }

    public function arsip(): BelongsTo
    {
        return $this->belongsTo(Arsip::class, 'id_arsip');
    }

    public function masterKode(): BelongsTo
    {
        return $this->belongsTo(MasterKode::class);
    }

    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
