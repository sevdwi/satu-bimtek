<?php

namespace App\Filament\Resources\DusArsipResource\Pages;

use App\Filament\Resources\DusArsipResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Concerns\RedirectsToIndex;

class EditDusArsip extends EditRecord
{
    use RedirectsToIndex;

    protected static string $resource = DusArsipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * Sama seperti CreateDusArsip -- lihat DusArsipResource::resolveLokasi().
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return DusArsipResource::resolveLokasi($data, $this->getRecord());
    }

    /**
     * Jaga-jaga buat dus lama yang dibuat sebelum fitur QR code ini ada
     * (kolom `qrcode` masih kosong) -- ikut disi/dipastikan sinkron tiap
     * kali record ini disave lewat form Edit. Lihat juga
     * CreateDusArsip::afterCreate().
     *
     * `kode_qrcode` SENGAJA tidak pernah dibuat ULANG kalau sudah pernah
     * ada (beda dari `qrcode` di atas) -- kode itu sudah ketempel fisik di
     * dus, jadi harus tetap sama walau rak/dinasnya kelak dipindah. Cuma
     * dibuat kalau masih kosong (dus lama sebelum kolom ini ada).
     */
    protected function afterSave(): void
    {
        $this->record->updateQuietly([
            'qrcode' => route('dus-arsip.rangkuman', $this->record),
            'kode_qrcode' => $this->record->kode_qrcode ?: \App\Models\DusArsip::generateKodeQrcode(
                $this->record->opd_induk_id,
                $this->record->rak_arsip_id,
            ),
        ]);
    }
}
