<?php

namespace App\Models;

use App\Enums\PeriodeStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['opd_id', 'tahun', 'tahap', 'status'])]
class Periode extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => PeriodeStatus::class,
            'manufer_at' => 'datetime',
        ];
    }

    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class);
    }

    public function arsips(): HasMany
    {
        return $this->hasMany(Arsip::class);
    }

    /**
     * User (biasanya Admin Dinas/Pengolah) yang menjalankan Manufer atas
     * periode ini. Null kalau belum pernah dimanufer.
     */
    public function manuferBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manufer_by');
    }

    /**
     * Label ringkas buat ditampilkan di tabel/select, mis. "2026 - Tahap 1".
     */
    public function label(): string
    {
        return "{$this->tahun} - Tahap {$this->tahap}";
    }

    public function isBuka(): bool
    {
        return $this->status === PeriodeStatus::Buka;
    }

    /**
     * True kalau periode ini sudah dimanufer (dikunci) -- arsip di
     * dalamnya tidak bisa ditambah/diedit/dihapus/dimanufer ulang sampai
     * dibuka lagi oleh Superadmin lewat action "Buka Kembali".
     */
    public function isManufer(): bool
    {
        return ! is_null($this->manufer_at);
    }

    /**
     * Batasi query Periode sesuai role user -- sama pola dengan
     * Arsip::scopeMilikUser(). Dipakai di widget Dashboard (lihat
     * app/Filament/Widgets/) biar statistiknya ikut hak akses yang sama
     * dengan menu Periode/Arsip, bukan query terpisah yang gampang beda
     * sendiri dari PeriodeResource::getEloquentQuery().
     */
    public function scopeMilikUser($query, ?User $user = null)
    {
        $user = $user ?: auth()->user();

        if (! $user || $user->isSuperadmin()) {
            return $query;
        }

        if ($user->isAdminDinas()) {
            return $query->whereHas('opd', fn ($q) => $q->where('opd_induk_id', $user->opd_induk_id));
        }

        if ($user->isPengolah()) {
            return $query->where('opd_id', $user->opd_id);
        }

        return $query->whereRaw('1 = 0');
    }
}
