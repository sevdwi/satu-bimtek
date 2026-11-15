<?php

namespace App\Filament\Widgets;

use App\Enums\NasibAkhir;
use App\Enums\PeriodeStatus;
use App\Models\Arsip;
use App\Models\Periode;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Ringkasan angka di paling atas Dashboard. Semua query lewat
 * Arsip::milikUser()/Periode::milikUser() -- scope yang sama dipakai di
 * InaktifResource/PeriodeResource -- biar Pengolah cuma lihat angka
 * bidangnya sendiri, Admin Dinas satu dinas, Superadmin semua (sesuai yang
 * diminta user: "ngikut role sama seperti menu Arsip lainnya").
 */
class ArsipStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user = auth()->user();

        return [
            Stat::make('Total Arsip', Arsip::query()->milikUser($user)->count())
                ->description('Seluruh arsip inaktif')
                ->descriptionIcon(Heroicon::OutlinedArchiveBox)
                ->color('primary'),
            Stat::make('Periode Berjalan', Periode::query()->milikUser($user)->where('status', PeriodeStatus::Buka->value)->count())
                ->description('Tahap yang masih buka')
                ->descriptionIcon(Heroicon::OutlinedCalendarDays)
                ->color('info'),
            Stat::make('Usul Musnah', Arsip::query()->milikUser($user)->where('nasib_akhir', NasibAkhir::Musnah->value)->count())
                ->description('Nasib akhir: Musnah')
                ->descriptionIcon(Heroicon::OutlinedDocumentMinus)
                ->color('danger'),
            Stat::make('Usul Permanen', Arsip::query()->milikUser($user)->where('nasib_akhir', NasibAkhir::Permanen->value)->count())
                ->description('Nasib akhir: Permanen')
                ->descriptionIcon(Heroicon::OutlinedDocumentCheck)
                ->color('success'),
        ];
    }
}
