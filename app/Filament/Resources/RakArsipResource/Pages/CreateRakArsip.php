<?php

namespace App\Filament\Resources\RakArsipResource\Pages;

use App\Filament\Resources\RakArsipResource;
use App\Models\Opd;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Concerns\RedirectsToIndex;

class CreateRakArsip extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = RakArsipResource::class;

    /**
     * - opd_id dipaksa dari data user login buat Pengolah (field opd_id
     *   disembunyikan buat dia di form, lihat RakArsipResource::form()) —
     *   sama pola dengan Periode & Arsip.
     * - opd_induk_id diturunkan dari OPD yang dipilih (bukan diisi manual
     *   terpisah), biar dua kolom itu nggak pernah nggak-sinkron.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
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
