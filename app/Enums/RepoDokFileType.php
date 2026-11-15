<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Jenis isi baris App\Models\RepoDokFile -- lihat catatan lengkap di
 * migration create_repo_dok_files_table.
 */
enum RepoDokFileType: string implements HasColor, HasLabel
{
    case File = 'file';
    case Link = 'link';

    public function getLabel(): string
    {
        return match ($this) {
            self::File => 'Berkas Terunggah',
            self::Link => 'Tautan/Referensi Eksternal',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::File => 'primary',
            self::Link => 'gray',
        };
    }
}
