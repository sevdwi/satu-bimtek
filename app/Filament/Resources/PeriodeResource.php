<?php

namespace App\Filament\Resources;

use App\Enums\PeriodeStatus;
use App\Filament\Concerns\HasMenuPermission;
use App\Filament\Resources\PeriodeResource\Pages;
use App\Models\Opd;
use App\Models\Periode;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;

class PeriodeResource extends Resource
{
    use HasMenuPermission;

    /** Key modul buat fitur "Manajemen Menu" -- lihat DusArsipResource. */
    protected static string $menuKey = 'periode';

    protected static ?string $model = Periode::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Periode';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Periode';

    protected static ?string $pluralModelLabel = 'Periode';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Data Periode')
                ->columns(2)
                ->components([
                    // Info Dinas -- cuma tampilan (tidak disimpan), diambil
                    // dari dinas user yang login (create) atau dari dinas
                    // milik bidang periode ini (edit).
                    TextInput::make('dinas_info')
                        ->label('Dinas')
                        ->disabled()
                        ->dehydrated(false)
                        ->formatStateUsing(fn (?Periode $record): ?string => $record?->opd?->opdInduk?->instansi
                            ?? auth()->user()?->opdInduk?->instansi)
                        ->columnSpanFull(),
                    Select::make('opd_id')
                        ->label('Bidang')
                        ->relationship(
                            name: 'opd',
                            titleAttribute: 'unit_kerja',
                            modifyQueryUsing: fn (Builder $query) => static::scopeOpdOptions($query),
                        )
                        ->getOptionLabelFromRecordUsing(fn (Opd $record): string => "{$record->unit_kerja} — {$record->opdInduk?->instansi}")
                        ->searchable(['unit_kerja'])
                        ->preload()
                        // Pengolah cuma boleh punya periode buat bidangnya sendiri — field ini
                        // disembunyikan buat dia, nilainya dipaksa dari data user login di
                        // CreatePeriode/EditPeriode (bukan cuma disembunyikan, tapi memang tidak
                        // dipercaya dari input form buat role ini).
                        ->visible(fn (): bool => ! (auth()->user()?->isPengolah()))
                        ->required(fn (): bool => ! (auth()->user()?->isPengolah()))
                        // Default ke bidang user yang login; kalau Admin Dinas ganti
                        // bidang, tahap dihitung ulang otomatis.
                        ->default(fn (): ?int => auth()->user()?->opd_id)
                        ->live()
                        ->afterStateUpdated(fn (Get $get, Set $set, string $operation) => static::isiTahapOtomatis($get, $set, $operation)),
                    TextInput::make('tahun')
                        ->label('Tahun')
                        ->numeric()
                        ->required()
                        ->minValue(2000)
                        ->maxValue(2100)
                        ->default(now()->year)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Get $get, Set $set, string $operation) => static::isiTahapOtomatis($get, $set, $operation)),
                    Select::make('tahap')
                        ->label('Tahap')
                        ->options([
                            1 => 'Tahap 1',
                            2 => 'Tahap 2',
                            3 => 'Tahap 3',
                            4 => 'Tahap 4',
                            5 => 'Tahap 5',
                            6 => 'Tahap 6',
                            7 => 'Tahap 7',
                            8 => 'Tahap 8',
                            9 => 'Tahap 9',
                            10 => 'Tahap 10',
                        ]) // selaras dengan MAKS_TAHAP
                        ->required()
                        // Saat tambah: otomatis = tahap terakhir bidang ini di tahun
                        // yang sama + 1 (belum ada periode di tahun itu -> Tahap 1).
                        // Dikunci di form & dihitung ulang di server
                        // (CreatePeriode::mutateFormDataBeforeCreate) biar tidak
                        // bisa diakali. Saat edit tetap bisa diubah manual.
                        ->default(fn (): int => static::tahapBerikutnya(auth()->user()?->opd_id, now()->year))
                        ->disabled(fn (string $operation): bool => $operation === 'create')
                        ->dehydrated()
                        ->helperText(fn (string $operation): ?string => $operation === 'create'
                            ? 'Terisi otomatis dari tahap terakhir bidang ini di tahun yang sama.'
                            : null)
                        ->unique(
                            table: 'periodes',
                            ignoreRecord: true,
                            modifyRuleUsing: fn (Unique $rule, Get $get) => $rule
                                ->where('opd_id', $get('opd_id'))
                                ->where('tahun', $get('tahun')),
                        )
                        ->validationMessages([
                            'unique' => 'Bidang ini sudah punya periode untuk tahun & tahap yang sama.',
                        ]),
                    Select::make('status')
                        ->label('Status')
                        ->options(PeriodeStatus::class)
                        ->default(PeriodeStatus::Buka)
                        ->required(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('opd.opdInduk.instansi')
                    ->label('Dinas')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('opd.unit_kerja')
                    ->label('Bidang')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tahun')
                    ->label('Tahun')
                    ->sortable(),
                TextColumn::make('tahap')
                    ->label('Tahap')
                    ->formatStateUsing(fn ($state): string => "Tahap {$state}")
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('manufer_at')
                    ->label('Manufer')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? 'Terkunci' : 'Belum')
                    ->color(fn (?string $state): string => $state ? 'danger' : 'gray')
                    ->tooltip(fn (Periode $record): ?string => $record->isManufer()
                        ? 'Dikunci ' . $record->manufer_at->translatedFormat('d M Y H:i') . ' oleh ' . ($record->manuferBy?->name ?? '-')
                        : null)
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options(PeriodeStatus::class),
                SelectFilter::make('opd_induk_id')
                    ->label('Dinas')
                    ->relationship('opd.opdInduk', 'instansi'),
                SelectFilter::make('opd_id')
                    ->label('Bidang')
                    ->relationship('opd', 'unit_kerja', fn (Builder $query) => static::scopeOpdOptions($query)),
            ])
            ->recordActions([
                // Cuma Superadmin yang boleh buka kunci tahap yang sudah
                // dimanufer -- ini satu-satunya jalan buat mengizinkan arsip
                // di tahap itu diedit/dihapus/dimanufer ulang lagi (lihat
                // ArsipPolicy & ListInaktif::runManufer()).
                Action::make('bukaKembali')
                    ->label('Buka Kembali')
                    ->icon(Heroicon::OutlinedLockOpen)
                    ->color('danger')
                    ->visible(fn (Periode $record): bool => auth()->user()?->isSuperadmin() && $record->isManufer())
                    ->requiresConfirmation()
                    ->modalHeading('Buka Kembali Tahap Ini?')
                    ->modalDescription('Tahap ini akan dibuka lagi -- arsip di dalamnya bisa diedit/dihapus, dan tahapnya bisa dimanufer ulang. Nomor urut definitif yang sudah ditulis sebelumnya TIDAK ikut dihapus.')
                    ->action(function (Periode $record): void {
                        $record->forceFill([
                            'manufer_at' => null,
                            'manufer_by' => null,
                        ])->save();

                        Notification::make()
                            ->title("Tahap \"{$record->label()}\" sudah dibuka kembali.")
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('tahun', 'desc');
    }

    /** Batas atas pilihan tahap di form. */
    public const MAKS_TAHAP = 10;

    /**
     * Tahap berikutnya buat satu bidang di satu tahun: tahap terbesar yang
     * sudah ada + 1, atau 1 kalau bidang itu belum punya periode di tahun
     * tersebut. Contoh: Bidang Informatika tahun 2026 sudah sampai Tahap 2
     * -> hasilnya 3.
     */
    public static function tahapBerikutnya(?int $opdId, ?int $tahun): int
    {
        if (! $opdId || ! $tahun) {
            return 1;
        }

        $terakhir = Periode::query()
            ->where('opd_id', $opdId)
            ->where('tahun', $tahun)
            ->max('tahap');

        return ((int) $terakhir) + 1;
    }

    /**
     * Dipanggil tiap kali Bidang/Tahun di form tambah berubah -- isi ulang
     * field tahap. Pengolah tidak punya field opd_id (disembunyikan), jadi
     * bidangnya diambil dari user login.
     */
    protected static function isiTahapOtomatis(Get $get, Set $set, string $operation): void
    {
        if ($operation !== 'create') {
            return;
        }

        $user = auth()->user();
        $opdId = $user?->isPengolah() ? $user->opd_id : $get('opd_id');

        $set('tahap', static::tahapBerikutnya($opdId ? (int) $opdId : null, (int) $get('tahun')));
    }

    /**
     * Batasi daftar rekord Periode sesuai role user yang login:
     * - Superadmin: semua periode.
     * - Admin Dinas: periode milik bidang-bidang di bawah dinasnya sendiri.
     * - Pengolah: cuma periode milik bidangnya sendiri (buat dasar input arsip).
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (! $user || $user->isSuperadmin()) {
            return $query;
        }

        if ($user->isAdminDinas()) {
            return $query->whereHas(
                'opd',
                fn (Builder $q) => $q->where('opd_induk_id', $user->opd_induk_id),
            );
        }

        if ($user->isPengolah()) {
            return $query->where('opd_id', $user->opd_id);
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * Batasi opsi pilihan Bidang (di form & filter) sesuai role: Admin Dinas
     * & Pengolah cuma boleh lihat/pilih bidang SE-DINAS sendiri (bukan cuma
     * bidangnya sendiri, biar filter tabel tetap berguna buat Pengolah).
     * Superadmin bebas pilih semua dinas.
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
            'index' => Pages\ListPeriodes::route('/'),
            'create' => Pages\CreatePeriode::route('/create'),
            'edit' => Pages\EditPeriode::route('/{record}/edit'),
        ];
    }
}
