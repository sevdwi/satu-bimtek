<?php

namespace App\Filament\Resources;

use App\Enums\ArsipStatus;
use App\Enums\NasibAkhir;
use App\Filament\Concerns\HasMenuPermission;
use App\Filament\Resources\UsulPermanenResource\Pages;
use App\Models\Arsip;
use App\Filament\Exports\ArsipExporter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\Exports\Models\Export;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Daftar arsip yang nasib akhirnya "Permanen" (arsips.nasib_akhir,
 * diturunkan otomatis dari master_kodes.keterangan -- lihat
 * InaktifResource::form()) -- jadi daftar usul/kandidat arsip yang
 * ditetapkan permanen (tidak dimusnahkan), sesuai menu "Arsip M/P > Daftar
 * Usul Permanen" yang diminta user.
 *
 * Read only + filter aja, kembaran dari UsulMusnahResource -- lihat
 * dokumentasi di sana buat detail. Bedanya cuma filter nasib_akhir dan
 * kolom "Tanggal Musnah" di sini tidak pernah relevan buat aksi
 * pemusnahan (arsip permanen memang sengaja tidak pernah dimusnahkan),
 * jadi tidak diberi highlight warna seperti di UsulMusnahResource.
 */
class UsulPermanenResource extends Resource
{
    use HasMenuPermission;

    /**
     * Key modul buat fitur "Manajemen Menu" -- lihat DusArsipResource &
     * catatan penting di UsulMusnahResource soal kenapa key-nya per-
     * Resource (bukan per Policy/model).
     */
    protected static string $menuKey = 'usul_permanen';

    protected static ?string $model = Arsip::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    protected static string | \UnitEnum | null $navigationGroup = 'Arsip M/P';

    protected static ?string $navigationLabel = 'Daftar Usul Permanen';

    protected static ?string $modelLabel = 'Usul Permanen';

    protected static ?string $pluralModelLabel = 'Usul Permanen';

    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no')
                    ->label('No')
                    ->rowIndex(),
                TextColumn::make('nomor')
                    ->label('Nomor Definitif')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('masterKode.kode')
                    ->label('Kode')
                    ->formatStateUsing(fn (Arsip $record): ?string => $record->masterKode
                        ? "{$record->masterKode->kode} — {$record->masterKode->nama}"
                        : null)
                    ->wrap()
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas(
                        'masterKode',
                        fn (Builder $q) => $q->where('kode', 'like', "%{$search}%")->orWhere('nama', 'like', "%{$search}%"),
                    ))
                    ->toggleable(),
                TextColumn::make('judul')
                    ->label('Redaksi')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('deskripsi')
                    ->label('Deskripsi')
                    ->searchable()
                    ->wrap()
                    ->limit(150)
                    ->toggleable(),
                TextColumn::make('tanggal')
                    ->label('Tanggal Arsip')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('tanggal_musnah')
                    ->label('Retensi Berakhir')
                    ->date('d M Y')
                    // Kotak search bisa dipakai cari per tahun: ketik "2030" ->
                    // muncul arsip yang tahun musnah/retensinya 2030.
                    ->searchable(query: fn (Builder $query, string $search): Builder => preg_match('/^\d{4}$/', trim($search))
                        ? $query->whereYear('tanggal_musnah', (int) trim($search))
                        : $query->whereRaw('1 = 0'))
                    ->sortable(),
                TextColumn::make('opd.unit_kerja')
                    ->label('Bidang')
                    ->toggleable(),
                TextColumn::make('periode.tahap')
                    ->label('Tahap')
                    ->formatStateUsing(fn (Arsip $record): ?string => $record->periode?->label())
                    ->toggleable(),
                TextColumn::make('rakArsip.nomor_rak')
                    ->label('Nomor Rak')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('dusArsip.nomor_dus')
                    ->label('Nomor Dus')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->badge()
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('nomor', 'asc')
            ->filters([
                Filter::make('tahap')
                    ->schema([
                        Select::make('tahap')
                            ->label('Tahap')
                            ->options([
                                1 => 'Tahap 1',
                                2 => 'Tahap 2',
                                3 => 'Tahap 3',
                                4 => 'Tahap 4',
                            ]),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['tahap'] ?? null,
                        fn (Builder $q, $tahap) => $q->whereHas('periode', fn (Builder $p) => $p->where('tahap', $tahap)),
                    )),
                // Filter RENTANG tahun (mis. 2026 - 2028), pengganti dropdown tahun
                // tunggal -- dua Select "Dari"/"Sampai", opsinya sama-sama diambil
                // dari tahun yang benar-benar ada di data (sudah ikut scoping role
                // user). Boleh diisi salah satu saja (mis. cuma "Dari" -> tahun itu
                // ke atas).
                Filter::make('rentang_tahun_musnah')
                    ->label('Tahun Retensi Berakhir')
                    ->schema([
                        Select::make('dari')
                            ->label('Dari Tahun')
                            ->options(fn (): array => static::opsiTahunMusnah())
                            ->searchable(),
                        Select::make('sampai')
                            ->label('Sampai Tahun')
                            ->options(fn (): array => static::opsiTahunMusnah())
                            ->searchable(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['dari'] ?? null, fn (Builder $q, $dari) => $q->whereYear('tanggal_musnah', '>=', (int) $dari))
                            ->when($data['sampai'] ?? null, fn (Builder $q, $sampai) => $q->whereYear('tanggal_musnah', '<=', (int) $sampai));
                    })
                    ->indicateUsing(function (array $data): ?string {
                        $dari = $data['dari'] ?? null;
                        $sampai = $data['sampai'] ?? null;

                        if (! $dari && ! $sampai) {
                            return null;
                        }

                        return match (true) {
                            $dari && $sampai => "Tahun Retensi Berakhir: {$dari} - {$sampai}",
                            (bool) $dari => "Tahun Retensi Berakhir: sejak {$dari}",
                            default => "Tahun Retensi Berakhir: sampai {$sampai}",
                        };
                    }),
                SelectFilter::make('status')->options(ArsipStatus::class),
                SelectFilter::make('opd_id')
                    ->label('Bidang')
                    ->relationship('opd', 'unit_kerja', fn (Builder $query) => static::scopeOpdOptions($query)),
            ])
            // Export Excel -- sama seperti di list Arsip (InaktifResource),
            // pakai ArsipExporter yang sama. Data yang diekspor otomatis
            // cuma arsip Usul Permanen (lihat getEloquentQuery()), sudah ikut
            // scoping role + filter/pencarian yang sedang aktif.
            ->headerActions([
                ExportAction::make()
                    ->label('Export Excel')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->exporter(ArsipExporter::class)
                    ->formats([ExportFormat::Xlsx])
                    ->fileName(fn (Export $export): string => "usul-permanen-{$export->getKey()}"),
            ])
            ->recordActions([])
            ->toolbarActions([
                BulkActionGroup::make([
                    // Export Excel cuma buat baris yang dicentang.
                    ExportBulkAction::make()
                        ->label('Export Excel (Terpilih)')
                        ->exporter(ArsipExporter::class)
                        ->formats([ExportFormat::Xlsx])
                        ->fileName(fn (Export $export): string => "usul-permanen-terpilih-{$export->getKey()}"),
                ]),
            ])
            ->defaultSort('tanggal_musnah')
            ->searchOnBlur();
    }

    /**
     * Sama pola scoping dengan InaktifResource::getEloquentQuery(), ditambah
     * filter nasib_akhir = Permanen.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->where('nasib_akhir', NasibAkhir::Permanen->value);
        $user = auth()->user();

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
     * Daftar tahun (YEAR(tanggal_musnah)) yang benar-benar ada di data,
     * sudah ikut scoping role user -- dipakai sebagai opsi Select "Dari"
     * & "Sampai" di filter rentang tahun.
     *
     * @return array<int, int>
     */
    protected static function opsiTahunMusnah(): array
    {
        return static::getEloquentQuery()
            ->whereNotNull('tanggal_musnah')
            ->selectRaw('YEAR(tanggal_musnah) as tahun_musnah')
            ->distinct()
            ->orderBy('tahun_musnah')
            ->pluck('tahun_musnah', 'tahun_musnah')
            ->all();
    }

    protected static function scopeOpdOptions(Builder $query): Builder
    {
        $user = auth()->user();

        if ($user?->isAdminDinas()) {
            return $query->where('opd_induk_id', $user->opd_induk_id);
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsulPermanen::route('/'),
        ];
    }
}
