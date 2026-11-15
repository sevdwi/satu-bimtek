<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use STS\FilamentImpersonate\Actions\Impersonate;
use App\Filament\Concerns\RedirectsToIndex;

class EditUser extends EditRecord
{
    use RedirectsToIndex;

    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Impersonate::make()->record($this->getRecord()),
            DeleteAction::make(),
        ];
    }

    /**
     * Sama seperti CreateUser -- lihat UserResource::enforceAdminDinasScope().
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return UserResource::enforceAdminDinasScope($data);
    }
}
