<?php

namespace App\Filament\Resources\PeriodeResource\Pages;

use App\Filament\Resources\PeriodeResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Concerns\RedirectsToIndex;

class CreatePeriode extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = PeriodeResource::class;

    /**
     * Field opd_id disembunyikan dari form buat Pengolah (lihat
     * PeriodeResource::form()) — nilainya dipaksa di sini dari data user yang
     * login, bukan dipercaya dari input form, biar Pengolah nggak bisa
     * bikin periode buat bidang lain lewat trik apa pun.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        if ($user?->isPengolah()) {
            $data['opd_id'] = $user->opd_id;
        }

        // Tahap selalu dihitung ulang di server (bukan dipercaya dari form):
        // tahap terakhir bidang ini di tahun yang sama + 1. Sekaligus
        // mencegah bentrok kalau ada orang lain yang barusan menambah tahap.
        $data['tahap'] = PeriodeResource::tahapBerikutnya(
            isset($data['opd_id']) ? (int) $data['opd_id'] : null,
            isset($data['tahun']) ? (int) $data['tahun'] : null,
        );

        if ($data['tahap'] > PeriodeResource::MAKS_TAHAP) {
            Notification::make()
                ->title('Tahap sudah mencapai batas maksimal (Tahap ' . PeriodeResource::MAKS_TAHAP . ') untuk tahun ini.')
                ->danger()
                ->send();

            $this->halt();
        }

        return $data;
    }
}
