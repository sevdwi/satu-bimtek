<?php

namespace App\Filament\Resources\OpdResource\Pages;

use App\Filament\Concerns\RedirectsToIndex;
use App\Filament\Resources\OpdResource;
use App\Models\Opd;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOpd extends EditRecord
{
    use RedirectsToIndex;

    protected static string $resource = OpdResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Sama seperti tombol Delete di tabel -- bidang yang masih punya
            // pengguna/periode/rak/dus/arsip tidak boleh dihapus.
            DeleteAction::make()
                ->disabled(fn (Opd $record): bool => $record->punyaDataTerkait()),
        ];
    }

    /**
     * Sama seperti CreateOpd -- lihat OpdResource::enforceDinasScope().
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return OpdResource::enforceDinasScope($data);
    }
}
