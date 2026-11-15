<?php

namespace App\Filament\Resources\RepoDokFolderResource\RelationManagers;

use App\Enums\RepoDokFileType;
use App\Models\RepoDokFile;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Isi satu folder Repositori Dokumentasi -- berkas terunggah ATAU tautan
 * eksternal per baris (lihat App\Enums\RepoDokFileType &
 * App\Models\RepoDokFile). Dibuka dari halaman Edit folder
 * (App\Filament\Resources\RepoDokFolderResource), Superadmin saja --
 * ditampilkan ke SEMUA user login lewat App\Http\Controllers\
 * RepoDokController, bukan lewat sini.
 */
class FilesRelationManager extends RelationManager
{
    protected static string $relationship = 'files';

    protected static ?string $title = 'Berkas & Tautan';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->components([
                    TextInput::make('nama')
                        ->label('Nama')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Radio::make('tipe')
                        ->label('Jenis')
                        ->options(collect(RepoDokFileType::cases())->mapWithKeys(
                            fn (RepoDokFileType $tipe): array => [$tipe->value => $tipe->getLabel()],
                        ))
                        ->default(RepoDokFileType::File->value)
                        ->inline()
                        ->live()
                        ->required()
                        ->columnSpanFull(),
                    FileUpload::make('file_path')
                        ->label('Berkas')
                        ->disk('public')
                        ->directory('repo_dok')
                        ->maxSize(51200)
                        ->downloadable()
                        ->openable()
                        ->visible(fn (Get $get): bool => $get('tipe') === RepoDokFileType::File->value)
                        ->required(fn (Get $get): bool => $get('tipe') === RepoDokFileType::File->value)
                        // Kosongkan value-nya dari data yang disimpan kalau
                        // jenisnya lagi Tautan -- supaya ganti jenis dari
                        // Berkas ke Tautan tidak nyisain file_path lama yang
                        // sudah tidak relevan lagi di baris ini.
                        ->dehydrated(fn (Get $get): bool => $get('tipe') === RepoDokFileType::File->value)
                        ->columnSpanFull(),
                    TextInput::make('link_url')
                        ->label('URL Tautan')
                        ->url()
                        ->maxLength(2048)
                        ->helperText('Alamat lengkap (diawali https://) ke dokumen/sumber di luar aplikasi ini.')
                        ->visible(fn (Get $get): bool => $get('tipe') === RepoDokFileType::Link->value)
                        ->required(fn (Get $get): bool => $get('tipe') === RepoDokFileType::Link->value)
                        ->dehydrated(fn (Get $get): bool => $get('tipe') === RepoDokFileType::Link->value)
                        ->columnSpanFull(),
                    Textarea::make('deskripsi')
                        ->label('Deskripsi')
                        ->rows(3)
                        ->columnSpanFull(),
                    TextInput::make('urutan')
                        ->label('Urutan Tampil')
                        ->numeric()
                        ->integer()
                        ->default(0),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nama')
            ->columns([
                TextColumn::make('nama')
                    ->label('Nama')
                    ->searchable()
                    ->description(fn (RepoDokFile $record): ?string => $record->deskripsi),
                TextColumn::make('tipe')
                    ->label('Jenis')
                    ->badge(),
                TextColumn::make('uploader.name')
                    ->label('Ditambahkan Oleh')
                    ->placeholder('—'),
                TextColumn::make('urutan')
                    ->label('Urutan')
                    ->sortable(),
            ])
            ->defaultSort('urutan')
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['uploaded_by'] = auth()->id();

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
