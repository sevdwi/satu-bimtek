<?php

namespace App\Filament\Resources\OpdIndukResource\Pages;

use App\Filament\Resources\OpdIndukResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOpdInduks extends ListRecords
{
    protected static string $resource = OpdIndukResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
