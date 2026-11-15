<?php

namespace App\Filament\Resources\UsulPermanenResource\Pages;

use App\Filament\Resources\UsulPermanenResource;
use Filament\Resources\Pages\ListRecords;

class ListUsulPermanen extends ListRecords
{
    protected static string $resource = UsulPermanenResource::class;

    // Sengaja tidak override getHeaderActions() — tidak ada tombol
    // "Tambah" sama sekali, karena menu ini read only (daftar usul, bukan
    // CRUD).
}
