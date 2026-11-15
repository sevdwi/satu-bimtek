<?php

namespace App\Filament\Resources\RakArsipResource\Pages;

use App\Filament\Resources\RakArsipResource;
use App\Models\Opd;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Concerns\RedirectsToIndex;

class EditRakArsip extends EditRecord
{
    use RedirectsToIndex;

    protected static string $resource = RakArsipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * Sama seperti CreateRakArsip: opd_id tetap dikunci buat Pengolah,
     * opd_induk_id diturunkan otomatis dari opd_id buat role lain.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = auth()->user();

        if ($user?->isPengolah()) {
            $data['opd_id'] = $user->opd_id;
            $data['opd_induk_id'] = $user->opd_induk_id;
        } elseif (! empty($data['opd_id'])) {
            $data['opd_induk_id'] = Opd::find($data['opd_id'])?->opd_induk_id;
        }

        return $data;
    }
}
