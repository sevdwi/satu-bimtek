<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum NasibAkhir: string implements HasColor, HasLabel
{
    case Musnah = 'musnah';
    case Permanen = 'permanen';

    public function getLabel(): string
    {
        return match ($this) {
            self::Musnah => 'Musnah',
            self::Permanen => 'Permanen',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Musnah => 'danger',
            self::Permanen => 'success',
        };
    }
}
