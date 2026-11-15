<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Concerns\RedirectsToIndex;
use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = UserResource::class;

    /**
     * Admin Dinas: role dipaksa Pengolah, dinas dipaksa dinasnya sendiri,
     * bidang wajib di dinas itu -- lihat UserResource::enforceAdminDinasScope().
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return UserResource::enforceAdminDinasScope($data);
    }
}
