<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * 3 tingkat role (diputuskan ulang dari 5 role starter sebelumnya):
 * - Superadmin : akses penuh semua instansi/dinas.
 * - AdminDinas : admin yang di-scope ke 1 dinas (opd_induk) miliknya.
 * - Pengolah   : staf pengolah arsip, di-scope ke 1 bidang (opd) di dinasnya.
 */
enum UserRole: string implements HasColor, HasLabel
{
    case Superadmin = 'superadmin';
    case AdminDinas = 'admin_dinas';
    case Pengolah = 'pengolah';

    public function getLabel(): string
    {
        return match ($this) {
            self::Superadmin => 'Superadmin',
            self::AdminDinas => 'Admin Dinas',
            self::Pengolah => 'Pengolah',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Superadmin => 'danger',
            self::AdminDinas => 'warning',
            self::Pengolah => 'primary',
        };
    }
}
