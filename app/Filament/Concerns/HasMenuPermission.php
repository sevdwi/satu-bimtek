<?php

namespace App\Filament\Concerns;

use App\Models\MenuItem;
use App\Models\MenuPermission;

/**
 * Dipasang di tiap Filament Resource yang menunya diatur lewat fitur
 * "Manajemen Menu" (lihat App\Models\MenuItem, App\Models\MenuPermission,
 * App\Filament\Pages\ManajemenMenu). Resource yang pakai trait ini WAJIB
 * set constant `$menuKey` cocok dengan salah satu `module_key` di
 * MenuPermissionSeeder.
 *
 * canViewAny() dipakai Filament untuk 2 hal SEKALIGUS: nampilin/
 * nyembunyiin item Resource ini di sidebar, DAN nge-gate akses ke
 * halaman index-nya langsung lewat URL (lihat
 * Filament\Resources\Resource\Concerns\HasNavigation::registerNavigationItems()
 * -- item sidebar cuma didaftarkan kalau static::canAccess() true, dan
 * canAccess() defaultnya cuma manggil canViewAny()). Jadi ini SATU
 * sumber kebenaran, bukan cuma kosmetik sidebar -- persis seperti yang
 * disepakati di rencana fitur ini.
 *
 * Method viewAny() di Policy masing-masing model TETAP ada apa adanya
 * (masih dipakai di tempat lain, mis. cek manual $user->can(...)) --
 * cuma Resource-nya sendiri tidak lagi mengandalkan itu buat nav/akses
 * index, sudah diambil alih trait ini.
 */
trait HasMenuPermission
{
    public static function canViewAny(): bool
    {
        return MenuPermission::isAllowed(static::$menuKey, auth()->user()?->role);
    }

    public static function getNavigationSort(): ?int
    {
        return MenuItem::sortOrder(static::$menuKey);
    }
}
