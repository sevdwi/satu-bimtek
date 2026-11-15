<?php

namespace App\Filament\Widgets;

use App\Models\Arsip;
use Filament\Widgets\ChartWidget;

/**
 * Tren jumlah arsip masuk per bulan, buat tahun yang dipilih lewat filter
 * dropdown bawaan ChartWidget ($this->filter). Dihitung dari kolom
 * `tanggal` (tanggal arsipnya sendiri), bukan `created_at` (kapan
 * record-nya diinput ke sistem) -- lebih mewakili "kapan arsipnya
 * sebenarnya masuk" secara kearsipan.
 */
class ArsipTrenBulananChart extends ChartWidget
{
    protected static ?int $sort = 4;

    protected ?string $heading = 'Tren Data Arsip per Bulan';

    protected function getFilters(): ?array
    {
        $tahunSekarang = (int) now()->year;

        return collect(range($tahunSekarang, $tahunSekarang - 4))
            ->mapWithKeys(fn (int $tahun): array => [(string) $tahun => (string) $tahun])
            ->all();
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $user = auth()->user();
        $tahun = $this->filter ?? now()->year;

        $counts = Arsip::query()->milikUser($user)
            ->whereYear('tanggal', $tahun)
            ->selectRaw('MONTH(tanggal) as bulan, count(*) as total')
            ->groupBy('bulan')
            ->pluck('total', 'bulan');

        $labelBulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        return [
            'datasets' => [
                [
                    'label' => "Arsip Masuk {$tahun}",
                    'data' => collect(range(1, 12))->map(fn (int $bulan) => $counts[$bulan] ?? 0)->all(),
                    'borderColor' => '#2a78d6',
                    'backgroundColor' => 'rgba(42, 120, 214, 0.15)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labelBulan,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['display' => false],
            ],
            'scales' => [
                'y' => ['beginAtZero' => true, 'ticks' => ['stepSize' => 1]],
            ],
        ];
    }
}
