<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PeriodeStatus: string implements HasColor, HasLabel
{
    case Buka = 'buka';
    case Tutup = 'tutup';

    public function getLabel(): string
    {
        return match ($this) {
            self::Buka => 'Buka',
            self::Tutup => 'Tutup',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Buka => 'success',
            self::Tutup => 'gray',
        };
    }
}
