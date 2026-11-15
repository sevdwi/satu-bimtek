<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasMenuPermission;
use App\Filament\Resources\RakArsipResource\Pages;
use App\Models\Opd;
use App\Models\RakArsip;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Modul CRUD Rak Arsip — penyimpanan fisik level rak. Satu rak bisa
 * berisi banyak Dus Arsip (lihat DusArsipResource), jadi rak HARUS dibuat
 * lebih dulu sebelum dus bisa dibuat untuknya (dipaksa lewat field
 * rak_arsip_id yang wajib & opsinya kosong di DusArsipResource::form()
 * kalau belum ada rak). Sama seperti Periode & Arsip: data rak "milik"
 * dinas (Admin Dinas) / bidang (Pengolah) tertentu, bukan milik
 * Superadmin — sesuai permintaan "setiap dinas membuat data rak arsip
 * dan dus arsip nya masing masing".
 */
class RakArsipResource extends Resource
{
    use HasMenuPermission;

    /** Key modul buat fitur "Manajemen Menu" -- lihat DusArsipResource. */
    protected static string $menuKey = 'rak_arsip';

    protected static ?string $model = RakArsip::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Rak Arsip';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Rak Arsip';

    protected static ?string $pluralModelLabel = 'Rak Arsip';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Data Rak')
                ->columns(2)
                ->components([
                    TextInput::make('nomor_rak')
                        ->label('Nomor Rak')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    // Pengolah cuma boleh punya rak buat bidangnya sendiri — field ini
                    // disembunyikan buat dia, nilainya dipaksa dari data user login di
                    // CreateRakArsip/EditRakArsip (pola sama kayak Periode & Arsip).
                    Select::make('opd_id')
                        ->label('Bidang (OPD)')
                        ->relationship(
                            name: 'opd',
                            titleAttribute: 'unit_kerja',
                            modifyQueryUsing: fn (Builder $query) => static::scopeOpdOptions($query),
                        )
                        ->getOptionLabelFromRecordUsing(fn (Opd $record): string => "{$record->unit_kerja} — {$record->opdInduk?->instansi}")
                        ->searchable(['unit_kerja'])
                        ->preload()
                        ->visible(fn (): bool => ! (auth()->user()?->isPengolah()))
                        ->required(fn (): bool => ! (auth()->user()?->isPengolah())),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nomor_rak')
                    ->label('Nomor Rak')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('opd.opdInduk.instansi')
                    ->label('Dinas')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('opd.unit_kerja')
                    ->label('Bidang')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('dus_arsips_count')
                    ->label('Jumlah Dus')
                    ->counts('dusArsips')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('opd_induk_id')
                    ->label('Dinas')
                    ->relationship('opdInduk', 'instansi'),
                SelectFilter::make('opd_id')
                    ->label('Bidang')
                    ->relationship('opd', 'unit_kerja', fn (Builder $query) => static::scopeOpdOptions($query)),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    // Constraint DB rak_arsips → dus_arsips itu RESTRICT (lihat migrasi
                    // dus_arsips: "biar rak nggak bisa dihapus selama masih ada dus di
                    // dalamnya") — dicegah di sini dulu (UX yang jelas) sebelum kena
                    // error mentah dari database.
                    ->disabled(fn (RakArsip $record): bool => $record->dusArsips()->exists())
                    ->tooltip(fn (RakArsip $record): ?string => $record->dusArsips()->exists()
                        ? 'Rak ini masih punya data Dus Arsip — hapus semua dus-nya dulu.'
                        : null),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // Catatan: hapus massal TIDAK dicegah per-baris seperti tombol
                    // hapus tunggal di atas — kalau ada rak yang masih punya dus di
                    // dalam pilihan massal, baris itu akan gagal dihapus karena
                    // constraint DB (RESTRICT), sisanya tetap terhapus normal.
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /**
     * Batasi daftar rekord Rak Arsip sesuai role — sama pola dengan
     * Periode & Arsip.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
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
     * Batasi opsi pilihan Bidang (form & filter): Admin Dinas & Pengolah
     * cuma boleh pilih/lihat bidang SE-DINAS sendiri (bukan cuma bidangnya
     * sendiri, biar filter di tabel tetap berguna buat Pengolah). Superadmin
     * bebas lihat semua dinas.
     */
    protected static function scopeOpdOptions(Builder $query): Builder
    {
        $user = auth()->user();

        if ($user?->isAdminDinas() || $user?->isPengolah()) {
            return $query->where('opd_induk_id', $user->opd_induk_id);
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRakArsips::route('/'),
            'create' => Pages\CreateRakArsip::route('/create'),
            'edit' => Pages\EditRakArsip::route('/{record}/edit'),
        ];
    }
}
