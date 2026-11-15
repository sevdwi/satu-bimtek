<?php

namespace App\Filament\Widgets;

use App\Enums\NasibAkhir;
use App\Filament\Resources\InaktifResource;
use App\Models\OpdInduk;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Widget dashboard "Arsip per Instansi" -- daftar Instansi (opd_induks)
 * dengan jumlah Unit Kerja (opds) & jumlah Arsip (total, Musnah, Permanen)
 * masing-masing, plus 2 opsi per baris: lihat rincian per Unit Kerja
 * (modal), atau lihat seluruh arsip milik instansi itu (deep-link ke
 * InaktifResource dengan filter 'opd_induk_id' [+ 'nasib_akhir']).
 *
 * Diletakkan sebelum ArsipPerBidangChart (sort 3) lewat sort 2 -- persis
 * setelah ArsipStatsOverview (sort 1) di baris paling atas Dashboard.
 *
 * Ditampilkan buat SEMUA role (bukan cuma Superadmin lagi), tapi baris &
 * hitungannya di-scope sesuai permintaan:
 * - Superadmin: semua instansi, semua unit kerja, semua arsip.
 * - Admin Dinas: cuma 1 baris (instansi/dinasnya sendiri), tapi hitungan
 *   Unit Kerja & Arsip mencakup SELURUH unit kerja di dinas itu (dia
 *   memang berwenang atas satu dinas penuh).
 * - Pengolah: cuma 1 baris (dinasnya sendiri) juga, TAPI hitungan Unit
 *   Kerja & Arsip cuma mencakup unit kerjanya SENDIRI (konsisten dengan
 *   scope Arsip::milikUser() yang dipakai widget2 lain & Resource lain di
 *   seluruh aplikasi -- Pengolah nggak pernah bisa lihat data bidang lain
 *   di manapun, jadi di sini pun sama).
 *
 * Modal "Unit Kerja" ikut pola yang sama: Superadmin & Admin Dinas lihat
 * SEMUA unit kerja di dinas itu, Pengolah cuma lihat unit kerjanya sendiri
 * di dalam modal itu.
 */
class InstansiOverviewTable extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $user = auth()->user();

        $query = OpdInduk::query()
            ->withCount(['opds' => fn (Builder $q) => static::scopeOpdsCount($q, $user)])
            ->withCount(['arsips' => fn (Builder $q) => static::scopeArsipsCount($q, $user)])
            ->withCount(['arsips as arsips_musnah_count' => fn (Builder $q) => static::scopeArsipsCount($q, $user)
                ->where('nasib_akhir', NasibAkhir::Musnah->value)])
            ->withCount(['arsips as arsips_permanen_count' => fn (Builder $q) => static::scopeArsipsCount($q, $user)
                ->where('nasib_akhir', NasibAkhir::Permanen->value)]);

        // Admin Dinas & Pengolah cuma boleh lihat 1 baris: instansi/dinas
        // mereka sendiri. Superadmin lihat semua baris.
        if ($user && ! $user->isSuperadmin()) {
            $query->where('id', $user->opd_induk_id);
        }

        return $table
            ->heading('Arsip per Instansi')
            ->description('Ringkasan arsip tiap instansi -- lihat rincian per unit kerja, atau lihat seluruh arsip instansi tersebut.')
            ->query($query)
            ->columns([
                TextColumn::make('no')
                    ->label('No')
                    ->rowIndex(),
                TextColumn::make('instansi')
                    ->label('Instansi')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('singkatan_instansi')
                    ->label('Singkatan'),
                TextColumn::make('opds_count')
                    ->label('Jumlah Unit Kerja')
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('arsips_count')
                    ->label('Jumlah Arsip')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('primary'),
                TextColumn::make('arsips_musnah_count')
                    ->label('Musnah')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('danger')
                    // Klik jumlah -> daftar Arsip instansi ini yang nasib_akhir-nya
                    // Musnah (filter 'opd_induk_id' + 'nasib_akhir' sekaligus).
                    // Aman buat Admin Dinas/Pengolah: InaktifResource::getEloquentQuery()
                    // tetap membatasi hasilnya ke scope mereka sendiri apa pun filter
                    // yang ditempel di URL (lihat catatan filter 'opd_induk_id' di sana).
                    ->url(fn (OpdInduk $record): string => InaktifResource::getUrl('index', [
                        'tableFilters' => [
                            'opd_induk_id' => ['value' => $record->id],
                            'nasib_akhir' => ['value' => NasibAkhir::Musnah->value],
                        ],
                    ])),
                TextColumn::make('arsips_permanen_count')
                    ->label('Permanen')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('success')
                    ->url(fn (OpdInduk $record): string => InaktifResource::getUrl('index', [
                        'tableFilters' => [
                            'opd_induk_id' => ['value' => $record->id],
                            'nasib_akhir' => ['value' => NasibAkhir::Permanen->value],
                        ],
                    ])),
            ])
            ->defaultSort('instansi', 'asc')
            ->recordActions([
                Action::make('unitKerja')
                    ->label('Unit Kerja')
                    ->icon(Heroicon::OutlinedBuildingOffice2)
                    ->color('gray')
                    ->modalHeading(fn (OpdInduk $record): string => "Unit Kerja — {$record->instansi}")
                    ->modalContent(function (OpdInduk $record) {
                        $user = auth()->user();

                        $opdsQuery = $record->opds()
                            ->withCount('arsips')
                            ->withCount(['arsips as arsips_musnah_count' => fn (Builder $q) => $q
                                ->where('nasib_akhir', NasibAkhir::Musnah->value)])
                            ->withCount(['arsips as arsips_permanen_count' => fn (Builder $q) => $q
                                ->where('nasib_akhir', NasibAkhir::Permanen->value)]);

                        // Pengolah cuma boleh lihat unit kerjanya sendiri di dalam
                        // modal ini -- Superadmin & Admin Dinas tetap lihat semua
                        // unit kerja di dinas itu (lihat docblock kelas ini).
                        if ($user?->isPengolah()) {
                            $opdsQuery->where('id', $user->opd_id);
                        }

                        return view('filament.widgets.instansi-unit-kerja-modal', [
                            'opdInduk' => $record,
                            'opds' => $opdsQuery->orderBy('unit_kerja')->get(),
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
                Action::make('lihatArsip')
                    ->label('Semua Arsip')
                    ->icon(Heroicon::OutlinedArchiveBox)
                    ->color('primary')
                    ->url(fn (OpdInduk $record): string => InaktifResource::getUrl('index', [
                        'tableFilters' => [
                            'opd_induk_id' => ['value' => $record->id],
                        ],
                    ])),
            ])
            ->paginated([10, 25, 50]);
    }

    /**
     * Batasi query COUNT arsip (dipakai buat 'arsips_count',
     * 'arsips_musnah_count', 'arsips_permanen_count') sesuai role -- persis
     * pola Arsip::scopeMilikUser(), cuma ditulis ulang di sini karena
     * withCount() butuh closure yang dipanggil di atas query relasi, bukan
     * query model Arsip langsung (scope model tidak bisa dipanggil lewat
     * withCount(['arsips' => ...]) begitu saja tanpa alias tambahan).
     */
    protected static function scopeArsipsCount(Builder $query, ?User $user): Builder
    {
        if (! $user || $user->isSuperadmin()) {
            return $query;
        }

        if ($user->isAdminDinas()) {
            return $query->where('opd_induk_id', $user->opd_induk_id);
        }

        if ($user->isPengolah()) {
            return $query->where('opd_id', $user->opd_id);
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * Batasi query COUNT unit kerja ('opds_count') sesuai role. Admin
     * Dinas SENGAJA tidak dibatasi tambahan di sini (dia berwenang atas
     * SELURUH unit kerja di dinasnya -- baris OpdInduk-nya sendiri sudah
     * dibatasi ke dinasnya lewat where('id', ...) di table() di atas,
     * jadi relasi opds() dari baris itu otomatis cuma unit kerja dinas
     * itu). Pengolah dibatasi ke unit kerjanya sendiri saja.
     */
    protected static function scopeOpdsCount(Builder $query, ?User $user): Builder
    {
        if (! $user || $user->isSuperadmin() || $user->isAdminDinas()) {
            return $query;
        }

        if ($user->isPengolah()) {
            return $query->where('id', $user->opd_id);
        }

        return $query->whereRaw('1 = 0');
    }
}
