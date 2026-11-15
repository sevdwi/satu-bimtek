<?php

namespace App\Filament\Resources\InaktifResource\Pages;

use App\Enums\ArsipStatus;
use App\Filament\Resources\InaktifResource;
use App\Models\Opd;
use App\Models\Periode;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use App\Filament\Concerns\RedirectsToIndex;

class CreateInaktif extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = InaktifResource::class;

    /**
     * - opd_id/opd_induk_id dipaksa dari data user login buat Pengolah
     *   (field opd_id disembunyikan buat dia di form, lihat
     *   InaktifResource::form()) — sama pola dengan Periode.
     * - Buat Admin Dinas/Superadmin, opd_induk_id diturunkan dari OPD yang
     *   dipilih (bukan diisi manual terpisah), biar dua kolom itu nggak
     *   pernah nggak-sinkron (lihat audit 9.3 soal opd_induk_id redundan
     *   di beberapa tabel).
     * - created_by diisi otomatis dari user yang login, bukan dari form.
     * - tahun diturunkan dari periode yang dipilih, bukan input terpisah.
     * - Jaga-jaga (defense in depth): periode_id Select di form sudah
     *   nyaring periode yang terkunci (lihat InaktifResource::form()), tapi
     *   dicek ulang di sini juga biar nggak bisa diakalin lewat request
     *   manual / race condition (periode dikunci pas form lagi kebuka).
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

        // Periode tidak dipilih manual lagi (field-nya dikunci di form) --
        // selalu ditentukan di server: periode terbuka terkecil milik bidang
        // arsip ini (lihat InaktifResource::periodeTerbukaTerkecil()).
        $data['periode_id'] = InaktifResource::periodeTerbukaTerkecil(
            ! empty($data['opd_id']) ? (int) $data['opd_id'] : null,
        );

        if (empty($data['periode_id'])) {
            Notification::make()
                ->title('Bidang ini belum punya periode yang terbuka.')
                ->body('Buat atau buka periode dulu di menu Periode sebelum menginput arsip.')
                ->danger()
                ->send();

            throw new Halt();
        }

        if (! empty($data['periode_id'])) {
            $periode = Periode::find($data['periode_id']);

            if ($periode?->isManufer()) {
                Notification::make()
                    ->title('Tahap ini sudah dimanufer dan terkunci — tidak bisa ditambahi arsip baru.')
                    ->danger()
                    ->send();

                throw new Halt();
            }

            $data['tahun'] = $periode?->tahun;
        }

        $data['created_by'] = $user?->id;

        // Status sekarang tidak wajib diisi manual (lihat InaktifResource::form())
        // -- kalau dikosongkan, jaga-jaga di sini biar tetap kesimpen "Draft"
        // (bukan null), bukan cuma andalkan ->default() di Select yang cuma
        // nilai awal tampilan form, tidak menjamin ikut kesubmit kalau field
        // sempat dikosongkan/disentuh user.
        $data['status'] = $data['status'] ?: ArsipStatus::Draft->value;

        // Unit Pengolah & Admin Dinas SELALU "Draft" -- field-nya sudah
        // dikunci (disabled) di InaktifResource::form(), tapi dipaksa ulang
        // di sini juga (defense in depth) biar tidak bisa diakalin lewat
        // request manual, sama pola dengan opd_id/opd_induk_id di atas.
        // Cuma Superadmin yang boleh set status manual (termasuk
        // "Terverifikasi"), jadi nilai dari form-nya dibiarkan lewat apa
        // adanya.
        if (! $user?->isSuperadmin()) {
            $data['status'] = ArsipStatus::Draft->value;
        }

        return $data;
    }
}
