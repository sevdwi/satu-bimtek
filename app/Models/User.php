<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'alias_user', 'nip', 'opd_id', 'opd_induk_id', 'email', 'role', 'status', 'phone_number', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
        ];
    }

    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class);
    }

    public function opdInduk(): BelongsTo
    {
        return $this->belongsTo(OpdInduk::class);
    }

    public function isSuperadmin(): bool
    {
        return $this->role === UserRole::Superadmin;
    }

    public function isAdminDinas(): bool
    {
        return $this->role === UserRole::AdminDinas;
    }

    public function isPengolah(): bool
    {
        return $this->role === UserRole::Pengolah;
    }

    /**
     * Hanya user dengan status aktif yang boleh masuk panel Filament —
     * kredensial bisa saja cocok tapi akunnya belum diverifikasi/diblokir.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->status === UserStatus::Active;
    }

    /**
     * Dipakai package stechstudio/filament-impersonate: siapa yang PUNYA
     * tombol impersonate. Detail siapa boleh impersonate SIAPA ada di
     * canBeImpersonated() di bawah (dicek per-target, dengan konteks user
     * yang sedang login lewat auth()->user()).
     */
    public function canImpersonate(): bool
    {
        return $this->isSuperadmin() || $this->isAdminDinas();
    }

    /**
     * Dipanggil pada RECORD yang mau di-impersonate ($this = target).
     * - Superadmin boleh impersonate siapa saja KECUALI superadmin lain.
     * - Admin Dinas cuma boleh impersonate Pengolah di dinasnya sendiri
     *   (opd_induk_id sama).
     * - Tidak ada yang boleh impersonate diri sendiri (otomatis gugur
     *   karena role/opd_induk_id pasti sama dengan aktor pada kasus itu,
     *   tapi tetap dicek eksplisit biar jelas).
     */
    public function canBeImpersonated(): bool
    {
        $actor = auth()->user();

        if (! $actor || $actor->is($this)) {
            return false;
        }

        if ($actor->isSuperadmin()) {
            return ! $this->isSuperadmin();
        }

        if ($actor->isAdminDinas()) {
            return $this->isPengolah() && $this->opd_induk_id === $actor->opd_induk_id;
        }

        return false;
    }
}
