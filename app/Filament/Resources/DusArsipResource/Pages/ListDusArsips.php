<?php

namespace App\Filament\Resources\DusArsipResource\Pages;

use App\Filament\Resources\DusArsipResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDusArsips extends ListRecords
{
    protected static string $resource = DusArsipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
