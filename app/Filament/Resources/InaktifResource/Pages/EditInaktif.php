<?php

namespace App\Filament\Resources\InaktifResource\Pages;

use App\Enums\ArsipStatus;
use App\Filament\Resources\InaktifResource;
use App\Models\Opd;
use App\Models\Periode;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use App\Filament\Concerns\RedirectsToIndex;

class EditInaktif extends EditRecord
{
    use RedirectsToIndex;

    protected static string $resource = InaktifResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * Sama seperti CreateInaktif: opd_id/opd_induk_id tetap dikunci buat
     * Pengolah, opd_induk_id diturunkan otomatis dari opd_id buat role
     * lain, dan tahun diturunkan dari periode.
     *
     * Halaman edit ini sendiri sudah otomatis 403 kalau periode ASAL arsip
     * ini sudah terkunci (lihat ArsipPolicy::update()) — cek isManufer() di
     * sini khusus buat jaga-jaga kalau periode TUJUAN (hasil ganti
     * periode_id di form) ternyata terkunci, atau periode aslinya baru saja
     * dikunci pas form ini masih kebuka.
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

        // Periode dikunci di form (tidak bisa dipilih manual) -- ditentukan
        // di server, bukan dipercaya dari input:
        // - bidang tidak berubah  -> tetap periode lama arsip ini;
        // - bidang diganti (Admin Dinas/Superadmin) -> pindah ke periode
        //   terbuka terkecil milik bidang baru, biar periode & bidang arsip
        //   tetap sinkron.
        $record = $this->getRecord();
        $opdBaru = ! empty($data['opd_id']) ? (int) $data['opd_id'] : null;

        if ($opdBaru === (int) $record->opd_id) {
            $data['periode_id'] = $record->periode_id;
        } else {
            $data['periode_id'] = InaktifResource::periodeTerbukaTerkecil($opdBaru);

            if (empty($data['periode_id'])) {
                Notification::make()
                    ->title('Bidang tujuan belum punya periode yang terbuka.')
                    ->body('Buat atau buka periode dulu di menu Periode untuk bidang tersebut.')
                    ->danger()
                    ->send();

                throw new Halt();
            }
        }

        if (! empty($data['periode_id'])) {
            $periode = Periode::find($data['periode_id']);

            if ($periode?->isManufer()) {
                Notification::make()
                    ->title('Tahap ini sudah dimanufer dan terkunci — tidak bisa dipakai lagi sampai dibuka Superadmin.')
                    ->danger()
                    ->send();

                throw new Halt();
            }

            $data['tahun'] = $periode?->tahun;
        }

        if ($this->getRecord()->periode?->isManufer()) {
            Notification::make()
                ->title('Arsip ini sudah dikunci (tahapnya sudah dimanufer) — tidak bisa disimpan.')
                ->danger()
                ->send();

            throw new Halt();
        }

        // Status sekarang tidak wajib diisi (lihat InaktifResource::form()) --
        // kalau sempat dikosongkan lewat form, jaga-jaga biar tetap kesimpen
        // "Draft", bukan null.
        $data['status'] = $data['status'] ?: ArsipStatus::Draft->value;

        return $data;
    }
}
