<?php

namespace App\Filament\Resources\RepoDokFolderResource\Pages;

use App\Filament\Resources\RepoDokFolderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRepoDokFolders extends ListRecords
{
    protected static string $resource = RepoDokFolderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
