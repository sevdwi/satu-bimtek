<?php

namespace App\Filament\Resources\UsulMusnahResource\Pages;

use App\Filament\Resources\UsulMusnahResource;
use Filament\Resources\Pages\ListRecords;

class ListUsulMusnah extends ListRecords
{
    protected static string $resource = UsulMusnahResource::class;

    // Sengaja tidak override getHeaderActions() — tidak ada tombol
    // "Tambah" sama sekali, karena menu ini read only (daftar usul, bukan
    // CRUD).
}
