<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Baris matriks "modul X boleh dibuka role Y atau tidak" -- lihat
 * App\Models\MenuItem untuk penjelasan lengkap fitur "Manajemen Menu".
 */
#[Fillable(['menu_item_id', 'role', 'is_allowed'])]
class MenuPermission extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'role' => UserRole::class,
            'is_allowed' => 'boolean',
        ];
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    /**
     * Boleh/tidaknya $role membuka modul $moduleKey -- dipanggil dari
     * App\Filament\Concerns\HasMenuPermission::canViewAny() tiap Resource.
     *
     * Modul yang BELUM terdaftar di tabel menu_items dianggap TIDAK ada
     * gerbang menu sama sekali (fallback: boleh), bukan otomatis
     * terkunci -- supaya Resource baru yang belum sempat di-seed ke
     * menu_items tidak mendadak hilang dari sidebar SEMUA orang begitu
     * kode-nya dideploy (baru terkunci setelah Superadmin sengaja
     * menambahkannya lewat halaman "Manajemen Menu" & mengatur izinnya).
     */
    public static function isAllowed(string $moduleKey, ?UserRole $role): bool
    {
        if (! $role) {
            return false;
        }

        $menuItem = MenuItem::allCached()->get($moduleKey);

        if (! $menuItem) {
            return true;
        }

        return (bool) ($menuItem['permissions'][$role->value] ?? false);
    }

    /** Auto-flush cache MenuItem tiap kali matriks izin ini berubah. */
    protected static function booted(): void
    {
        static::saved(fn () => MenuItem::flushCache());
        static::deleted(fn () => MenuItem::flushCache());
    }
}
