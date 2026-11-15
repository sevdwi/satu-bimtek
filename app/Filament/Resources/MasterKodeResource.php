<?php

namespace App\Filament\Resources;

use App\Enums\NasibAkhir;
use App\Filament\Concerns\HasMenuPermission;
use App\Filament\Resources\MasterKodeResource\Pages;
use App\Models\MasterKode;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Menu READ ONLY buat lihat data kode klasifikasi arsip (master_kodes) —
 * hasil import dari database SATU V1, lihat MasterKodeSeeder. Cuma
 * Superadmin yang bisa akses (lihat MasterKodePolicy). Sengaja tidak ada
 * form/Create/Edit/Delete action sama sekali — bukan cuma disembunyikan,
 * memang tidak ada.
 */
class MasterKodeResource extends Resource
{
    use HasMenuPermission;

    /** Key modul buat fitur "Manajemen Menu" -- lihat DusArsipResource. */
    protected static string $menuKey = 'master_kode';

    protected static ?string $model = MasterKode::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?string $navigationLabel = 'Kode Klasifikasi';

    protected static ?int $navigationSort = 0;

    protected static ?string $modelLabel = 'Kode Klasifikasi';

    protected static ?string $pluralModelLabel = 'Kode Klasifikasi';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no')
                    ->label('No')
                    ->rowIndex(),
                TextColumn::make('kode')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('nama')
                    ->label('Uraian')
                    ->searchable()
                    ->wrap()
                    // indentasi visual sesuai level, biar kelihatan berjenjang
                    ->formatStateUsing(fn ($state, MasterKode $record): string => str_repeat('— ', max(0, $record->level - 1)).$state),
                TextColumn::make('level')
                    ->label('Level')
                    ->badge()
                    ->color('gray')
                    ->sortable(),
                TextColumn::make('parent.nama')
                    ->label('Induk Dari')
                    ->toggleable()
                    ->placeholder('— (level 1 / urusan utama)'),
                TextColumn::make('aktif')
                    ->label('Aktif (Thn)')
                    ->alignCenter()
                    ->toggleable(),
                TextColumn::make('inaktif')
                    ->label('Inaktif (Thn)')
                    ->alignCenter()
                    ->toggleable(),
                TextColumn::make('keterangan')
                    ->label('Nasib Akhir')
                    ->badge()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('level')
                    ->label('Level')
                    ->options([
                        1 => 'Level 1 (Urusan Utama)',
                        2 => 'Level 2',
                        3 => 'Level 3',
                    ]),
                SelectFilter::make('keterangan')
                    ->label('Nasib Akhir')
                    ->options(NasibAkhir::class),
                SelectFilter::make('urusan')
                    ->label('Urusan (Kode Level 1)')
                    ->options(fn (): array => MasterKode::query()
                        ->whereNull('parent_id')
                        ->orderBy('kode')
                        ->pluck('nama', 'id')
                        ->all())
                    ->query(function (Builder $query, array $data): Builder {
                        $rootId = $data['value'] ?? null;

                        if (blank($rootId)) {
                            return $query;
                        }

                        // Data sumber cuma sedalam 3 level (root -> anak -> cucu),
                        // jadi cakupan 2 tingkat di bawah root ini sudah menjangkau
                        // semua baris yang ada saat ini.
                        return $query->where(function (Builder $q) use ($rootId): void {
                            $q->where('id', $rootId)
                                ->orWhere('parent_id', $rootId)
                                ->orWhereIn('parent_id', function ($sub) use ($rootId): void {
                                    $sub->select('id')
                                        ->from('master_kodes')
                                        ->where('parent_id', $rootId);
                                });
                        });
                    }),
            ])
            // urut berdasarkan sort_key (bukan kode mentah) supaya rapi
            // sesuai hierarki: root -> anak -> cucu -> root berikutnya,
            // termasuk kasus "...9" vs "...10" yang salah kalau di-sort string biasa.
            ->defaultSort('sort_key')
            ->searchOnBlur();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMasterKodes::route('/'),
        ];
    }
}
