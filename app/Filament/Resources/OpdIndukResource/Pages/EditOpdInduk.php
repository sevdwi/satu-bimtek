<?php

namespace App\Filament\Resources\OpdIndukResource\Pages;

use App\Filament\Concerns\RedirectsToIndex;
use App\Filament\Resources\OpdIndukResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOpdInduk extends EditRecord
{
    use RedirectsToIndex;

    protected static string $resource = OpdIndukResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
