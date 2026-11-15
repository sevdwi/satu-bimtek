<?php

namespace App\Filament\Resources\RakArsipResource\Pages;

use App\Filament\Resources\RakArsipResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRakArsips extends ListRecords
{
    protected static string $resource = RakArsipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
