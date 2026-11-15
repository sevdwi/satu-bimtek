<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RepoDokFolderResource\Pages;
use App\Filament\Resources\RepoDokFolderResource\RelationManagers\FilesRelationManager;
use App\Models\RepoDokFolder;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Kelola struktur folder "Repositori Dokumentasi" (/repo_dok) -- lihat
 * catatan lengkap di migration create_repo_dok_folders_table &
 * App\Models\RepoDokFolder. Isi tiap folder (berkas/tautan) dikelola
 * lewat RelationManagers\FilesRelationManager di bawah, dibuka dari
 * halaman Edit folder.
 *
 * SENGAJA cuma Superadmin (sama pola dengan MenuItemResource) -- fitur
 * ini soal STRUKTUR & isi dokumentasi resmi aplikasi, bukan data
 * operasional arsip yang perlu di-scope per dinas/unit kerja seperti
 * Resource lain. Halaman BACA-nya (/repo_dok, lihat
 * App\Http\Controllers\RepoDokController) tetap terbuka untuk SEMUA role
 * yang login -- pemisahan ini yang diminta user.
 */
class RepoDokFolderResource extends Resource
{
    protected static ?string $model = RepoDokFolder::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedFolderOpen;

    protected static ?string $navigationLabel = 'Repositori Dokumentasi';

    protected static ?string $modelLabel = 'Folder Dokumentasi';

    protected static ?string $pluralModelLabel = 'Repositori Dokumentasi';

    protected static ?string $slug = 'repo-dokumentasi';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isSuperadmin();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return (bool) auth()->user()?->isSuperadmin();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Folder')
                ->columns(2)
                ->components([
                    TextInput::make('nama')
                        ->label('Nama Folder')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Select::make('parent_id')
                        ->label('Folder Induk')
                        ->native(false)
                        ->searchable()
                        ->options(function (?RepoDokFolder $record): array {
                            // Folder tidak boleh jadi induk buat dirinya
                            // sendiri ATAU keturunannya sendiri (bikin
                            // rantai tak berujung) -- lihat
                            // RepoDokFolder::selfAndDescendantIds().
                            $terlarang = $record ? $record->selfAndDescendantIds() : [];

                            return RepoDokFolder::query()
                                ->when($terlarang !== [], fn ($query) => $query->whereNotIn('id', $terlarang))
                                ->orderBy('nama')
                                ->pluck('nama', 'id')
                                ->all();
                        })
                        ->helperText('Kosongkan untuk folder tingkat atas.')
                        ->columnSpanFull(),
                    Textarea::make('deskripsi')
                        ->label('Deskripsi')
                        ->rows(3)
                        ->columnSpanFull(),
                    TextInput::make('urutan')
                        ->label('Urutan Tampil')
                        ->numeric()
                        ->integer()
                        ->default(0)
                        ->helperText('Folder dengan angka lebih kecil tampil lebih dulu.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama')
                    ->label('Nama Folder')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (RepoDokFolder $record): ?string => $record->deskripsi),
                TextColumn::make('parent.nama')
                    ->label('Folder Induk')
                    ->placeholder('— (tingkat atas)')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('children_count')
                    ->label('Sub-folder')
                    ->counts('children')
                    ->alignCenter(),
                TextColumn::make('files_count')
                    ->label('Berkas/Tautan')
                    ->counts('files')
                    ->alignCenter()
                    ->badge()
                    ->color('primary'),
                TextColumn::make('urutan')
                    ->label('Urutan')
                    ->sortable(),
            ])
            ->defaultSort('urutan')
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->modalDescription('Menghapus folder ini akan menghapus SEMUA sub-folder dan berkas/tautan di dalamnya. Tindakan ini tidak bisa dibatalkan.'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            FilesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRepoDokFolders::route('/'),
            'create' => Pages\CreateRepoDokFolder::route('/create'),
            'edit' => Pages\EditRepoDokFolder::route('/{record}/edit'),
        ];
    }
}
