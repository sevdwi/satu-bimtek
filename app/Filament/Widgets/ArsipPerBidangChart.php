<?php

namespace App\Filament\Widgets;

use App\Models\Arsip;
use Filament\Widgets\ChartWidget;

/**
 * Chart batang jumlah arsip per Bidang (OPD). Cuma satu seri data (jumlah
 * arsip), jadi warnanya SATU warna aja -- bukan tiap batang beda warna,
 * karena identitas kategori di sini sudah dibawa oleh label sumbu-X
 * (nama bidang), bukan oleh warna.
 *
 * Disembunyikan buat Pengolah (lihat canView()) -- scope datanya cuma satu
 * bidang aja buat role itu, jadi chart perbandingan antar bidang nggak ada
 * gunanya (cuma bakal nampilin 1 batang).
 */
class ArsipPerBidangChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'Arsip per Bidang';

    public static function canView(): bool
    {
        return ! (auth()->user()?->isPengolah());
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $user = auth()->user();

        // Sengaja TIDAK pakai Arsip::scopeMilikUser() di sini: scope itu
        // nulis "where('opd_induk_id', ...)" tanpa nama tabel, yang jadi
        // ambigu begitu di-join ke `opds` (tabel itu juga punya kolom
        // opd_induk_id) -- error "Column 'opd_induk_id' in where clause is
        // ambiguous". Logika pembatasannya sama persis dengan
        // scopeMilikUser, cuma kolomnya dikunci ke `arsips.` biar tidak
        // bentrok dengan kolom sama di tabel `opds` hasil join.
        $query = Arsip::query()
            ->join('opds', 'opds.id', '=', 'arsips.opd_id')
            ->selectRaw('opds.unit_kerja as bidang, count(*) as total');

        if ($user && ! $user->isSuperadmin()) {
            if ($user->isAdminDinas()) {
                $query->where('arsips.opd_induk_id', $user->opd_induk_id);
            } elseif ($user->isPengolah()) {
                $query->where('arsips.opd_id', $user->opd_id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $rows = $query
            ->groupBy('opds.unit_kerja')
            ->orderByDesc('total')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Arsip',
                    'data' => $rows->pluck('total')->all(),
                    'backgroundColor' => '#2a78d6',
                ],
            ],
            'labels' => $rows->pluck('bidang')->all(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => [
                'legend' => ['display' => false],
            ],
            'scales' => [
                'x' => ['beginAtZero' => true, 'ticks' => ['stepSize' => 1]],
            ],
        ];
    }
}
