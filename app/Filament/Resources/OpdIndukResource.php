<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasMenuPermission;
use App\Filament\Resources\OpdIndukResource\Pages;
use App\Models\OpdInduk;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Modul CRUD Dinas (opd_induks). Cuma Superadmin yang bisa akses (lihat
 * OpdIndukPolicy) -- menu ini tidak muncul sama sekali buat Admin
 * Dinas/Pengolah.
 *
 * Ini langkah PERTAMA dari alur "input dinas dulu, baru bidang": data
 * Bidang (OpdResource) wajib pilih salah satu Dinas dari sini lewat field
 * opd_induk_id, dan opsinya kosong kalau belum ada Dinas sama sekali --
 * jadi Superadmin dipaksa isi menu ini dulu sebelum bisa menambah Bidang.
 */
class OpdIndukResource extends Resource
{
    use HasMenuPermission;

    /** Key modul buat fitur "Manajemen Menu" -- lihat DusArsipResource. */
    protected static string $menuKey = 'opd_induk';

    protected static ?string $model = OpdInduk::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static ?string $navigationLabel = 'Dinas';

    protected static ?int $navigationSort = 6;

    protected static ?string $modelLabel = 'Dinas';

    protected static ?string $pluralModelLabel = 'Dinas';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Data Dinas')
                ->columns(2)
                ->components([
                    TextInput::make('kode_instansi')
                        ->label('Kode Instansi')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('singkatan_instansi')
                        ->label('Singkatan')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('instansi')
                        ->label('Nama Dinas')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kode_instansi')
                    ->label('Kode Instansi')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('instansi')
                    ->label('Nama Dinas')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('singkatan_instansi')
                    ->label('Singkatan')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('opds_count')
                    ->label('Jumlah Bidang')
                    ->counts('opds')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    // Dinas ini masih jadi induk dari data Bidang (opds.opd_induk_id
                    // itu nullOnDelete, jadi DB TIDAK akan menolak) -- tapi kalau
                    // dibiarkan, Bidang-nya jadi "yatim" (opd_induk_id null) dan
                    // merusak asumsi scoping per-dinas di seluruh aplikasi (Admin
                    // Dinas/Pengolah, Arsip, Rak, Dus, dll). Makanya dicegah manual
                    // di sini, bukan cuma mengandalkan constraint DB.
                    ->disabled(fn (OpdInduk $record): bool => $record->opds()->exists() || $record->users()->exists())
                    ->tooltip(fn (OpdInduk $record): ?string => match (true) {
                        $record->opds()->exists() => 'Dinas ini masih punya data Bidang — hapus/pindahkan bidangnya dulu.',
                        $record->users()->exists() => 'Dinas ini masih punya pengguna terdaftar — pindahkan dulu penggunanya.',
                        default => null,
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('instansi');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOpdInduks::route('/'),
            'create' => Pages\CreateOpdInduk::route('/create'),
            'edit' => Pages\EditOpdInduk::route('/{record}/edit'),
        ];
    }
}
