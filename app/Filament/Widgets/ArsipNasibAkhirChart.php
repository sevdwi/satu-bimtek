<?php

namespace App\Filament\Widgets;

use App\Enums\NasibAkhir;
use App\Models\Arsip;
use Filament\Widgets\ChartWidget;

/**
 * Doughnut Musnah vs Permanen vs Belum Ditentukan. Urutan & warna 3
 * kategori ini TETAP (fixed) apa pun jumlah datanya -- warna nggak pernah
 * "loncat" ngikutin rank, biar Musnah selalu merah & Permanen selalu hijau
 * tiap kali dibuka.
 */
class ArsipNasibAkhirChart extends ChartWidget
{
    protected static ?int $sort = 5;

    protected ?string $heading = 'Arsip per Nasib Akhir';

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $user = auth()->user();
        $base = fn () => Arsip::query()->milikUser($user);

        $musnah = $base()->where('nasib_akhir', NasibAkhir::Musnah->value)->count();
        $permanen = $base()->where('nasib_akhir', NasibAkhir::Permanen->value)->count();
        $belumDitentukan = $base()->whereNull('nasib_akhir')->count();

        return [
            'datasets' => [
                [
                    'data' => [$musnah, $permanen, $belumDitentukan],
                    'backgroundColor' => ['#e34948', '#1baf7a', '#c3c2b7'],
                ],
            ],
            'labels' => ['Musnah', 'Permanen', 'Belum Ditentukan'],
        ];
    }
}
