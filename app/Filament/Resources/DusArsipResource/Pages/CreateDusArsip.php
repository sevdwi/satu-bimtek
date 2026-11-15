<?php

namespace App\Filament\Resources\DusArsipResource\Pages;

use App\Filament\Resources\DusArsipResource;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Concerns\RedirectsToIndex;

class CreateDusArsip extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = DusArsipResource::class;

    /**
     * Dus baru selalu tanpa rak; bidang/dinasnya ikut bidang user (Pengolah)
     * atau bidang yang dipilih. Lihat DusArsipResource::resolveLokasi().
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return DusArsipResource::resolveLokasi($data);
    }

    /**
     * Isi kolom `qrcode` dengan link halaman "Rangkuman Berkas" dus ini
     * (route 'dus-arsip.rangkuman') -- baru bisa dilakukan SETELAH record
     * tersimpan karena URL-nya butuh ID dus.
     *
     * `kode_qrcode` dibuat sekali di sini lewat
     * App\Models\DusArsip::generateKodeQrcode() -- INI (bukan `qrcode` di
     * atas) yang di-encode ke GAMBAR QR code fisiknya, lihat
     * DusArsipRangkumanController::qrcode().
     */
    protected function afterCreate(): void
    {
        $this->record->update([
            'qrcode' => route('dus-arsip.rangkuman', $this->record),
            'kode_qrcode' => \App\Models\DusArsip::generateKodeQrcode(
                $this->record->opd_induk_id,
                $this->record->rak_arsip_id,
            ),
        ]);
    }
}
