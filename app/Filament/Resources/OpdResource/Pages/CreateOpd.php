<?php

namespace App\Filament\Resources\OpdResource\Pages;

use App\Filament\Concerns\RedirectsToIndex;
use App\Filament\Resources\OpdResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOpd extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = OpdResource::class;

    /**
     * Admin Dinas: dinas dipaksa dinasnya sendiri; kode_instansi & instansi
     * selalu diambil dari Dinas induk -- lihat OpdResource::enforceDinasScope().
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return OpdResource::enforceDinasScope($data);
    }
}
