<?php

namespace App\Filament\Resources\PeriodeResource\Pages;

use App\Filament\Resources\PeriodeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Concerns\RedirectsToIndex;

class EditPeriode extends EditRecord
{
    use RedirectsToIndex;

    protected static string $resource = PeriodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * Sama seperti CreatePeriode: opd_id tidak boleh berubah lewat form buat
     * Pengolah, tetap dikunci ke bidangnya sendiri.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = auth()->user();

        if ($user?->isPengolah()) {
            $data['opd_id'] = $user->opd_id;
        }

        return $data;
    }
}
