<?php

namespace App\Filament\Resources\RepoDokFolderResource\Pages;

use App\Filament\Resources\RepoDokFolderResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRepoDokFolder extends EditRecord
{
    protected static string $resource = RepoDokFolderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
