<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum UserStatus: string implements HasColor, HasLabel
{
    case Verify = 'verify';
    case Active = 'active';
    case Banned = 'banned';

    public function getLabel(): string
    {
        return match ($this) {
            self::Verify => 'Menunggu Verifikasi',
            self::Active => 'Aktif',
            self::Banned => 'Diblokir',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Verify => 'warning',
            self::Active => 'success',
            self::Banned => 'danger',
        };
    }
}
