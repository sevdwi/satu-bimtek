<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ArsipStatus: string implements HasColor, HasLabel
{
    case Verify = 'verify';
    case Input = 'input';
    case Draft = 'draft';

    public function getLabel(): string
    {
        return match ($this) {
            self::Verify => 'Terverifikasi',
            self::Input => 'Input',
            self::Draft => 'Draft',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Verify => 'success',
            self::Input => 'info',
            self::Draft => 'gray',
        };
    }
}
