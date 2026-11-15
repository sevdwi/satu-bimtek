<?php

namespace App\Filament\Resources\MasterKodeResource\Pages;

use App\Filament\Resources\MasterKodeResource;
use Filament\Resources\Pages\ListRecords;

class ListMasterKodes extends ListRecords
{
    protected static string $resource = MasterKodeResource::class;

    // Sengaja tidak override getHeaderActions() — tidak ada tombol
    // "Tambah" sama sekali, karena menu ini read only.
}
