<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasMenuPermission;
use App\Filament\Resources\OpdResource\Pages;
use App\Models\MenuPermission;
use App\Models\Opd;
use App\Models\OpdInduk;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Modul CRUD Bidang (opds). Superadmin kelola semua bidang; Admin Dinas
 * cuma bidang di dinasnya sendiri (dinas otomatis & dikunci di form, lihat
 * OpdPolicy & enforceDinasScope()). Pengolah tidak punya akses.
 *
 * Ini langkah KEDUA dari alur "input dinas dulu, baru bidang": field
 * opd_induk_id WAJIB diisi dan opsinya kosong kalau belum ada data Dinas
 * sama sekali (lihat OpdIndukResource) -- jadi Superadmin dipaksa bikin
 * Dinas dulu sebelum bisa menambah Bidang, sesuai alur yang diminta.
 */
class OpdResource extends Resource
{
    use HasMenuPermission;

    /** Key modul buat fitur "Manajemen Menu" -- lihat DusArsipResource. */
    protected static string $menuKey = 'opd';

    protected static ?string $model = Opd::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?string $navigationLabel = 'Bidang';

    protected static ?int $navigationSort = 7;

    protected static ?string $modelLabel = 'Bidang';

    protected static ?string $pluralModelLabel = 'Bidang';

    /**
     * Gerbang menu + akses index: matriks Manajemen Menu DAN policy
     * viewAny() -- supaya Pengolah tidak pernah bisa membuka menu ini walau
     * menu 'opd' belum terdaftar di menu_items (fallback "boleh" di
     * MenuPermission::isAllowed()). Pola sama dengan UserResource.
     */
    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return MenuPermission::isAllowed(static::$menuKey, $user?->role)
            && (bool) $user?->can('viewAny', Opd::class);
    }

    /**
     * Admin Dinas cuma lihat bidang di dinasnya sendiri; Superadmin semua.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user?->isAdminDinas()) {
            return $query->where('opd_induk_id', $user->opd_induk_id);
        }

        if (! $user?->isSuperadmin()) {
            return $query->whereRaw('1 = 0');
        }

        return $query;
    }

    protected static function isAdminDinas(): bool
    {
        return (bool) auth()->user()?->isAdminDinas();
    }

    /** Dinas milik Admin Dinas yang login (null buat role lain). */
    protected static function dinasAdmin(): ?OpdInduk
    {
        return static::isAdminDinas() ? auth()->user()->opdInduk : null;
    }

    /**
     * Pengaman server-side buat Create/EditOpd (field disabled tetap bisa
     * diutak-atik lewat request):
     * - Admin Dinas: opd_induk_id DIPAKSA dinasnya sendiri.
     * - Semua role: kode_instansi & instansi SELALU diambil ulang dari Dinas
     *   induk yang tersimpan (bukan dipercaya dari form).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function enforceDinasScope(array $data): array
    {
        $user = auth()->user();

        if ($user?->isAdminDinas()) {
            $data['opd_induk_id'] = $user->opd_induk_id;
        }

        $opdInduk = filled($data['opd_induk_id'] ?? null) ? OpdInduk::find($data['opd_induk_id']) : null;

        if (! $opdInduk) {
            Notification::make()
                ->title('Dinas tidak valid')
                ->body($user?->isAdminDinas()
                    ? 'Akun Anda belum terhubung ke Dinas mana pun. Hubungi Superadmin.'
                    : 'Pilih Dinas induk untuk bidang ini.')
                ->danger()
                ->send();

            throw new Halt();
        }

        $data['kode_instansi'] = $opdInduk->kode_instansi;
        $data['instansi'] = $opdInduk->instansi;

        return $data;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Data Bidang')
                ->columns(2)
                ->components([
                    Select::make('opd_induk_id')
                        ->label('Dinas')
                        ->relationship(
                            name: 'opdInduk',
                            titleAttribute: 'instansi',
                            modifyQueryUsing: fn (Builder $query) => $query->when(
                                static::isAdminDinas(),
                                fn (Builder $query) => $query->whereKey(auth()->user()->opd_induk_id),
                            ),
                        )
                        // Admin Dinas: otomatis dinasnya sendiri & dikunci
                        // (dipaksa lagi di server, lihat enforceDinasScope()).
                        ->default(fn (): ?int => static::dinasAdmin()?->id)
                        ->disabled(fn (): bool => static::isAdminDinas())
                        ->dehydrated()
                        ->searchable()
                        ->preload()
                        ->live()
                        ->required()
                        ->columnSpanFull()
                        ->helperText(fn (): string => OpdInduk::query()->exists()
                            ? 'Bidang ini akan tercatat di bawah Dinas yang dipilih.'
                            : 'Belum ada data Dinas -- buat dulu di menu Dinas sebelum bisa menambah Bidang.')
                        // kode_instansi & instansi Bidang selalu ikut Dinas induknya
                        // (lihat 2 field di bawah, keduanya dikunci -> disabled), jadi
                        // begitu Dinas dipilih/diganti, dua field itu ikut disegarkan.
                        ->afterStateUpdated(function (Set $set, ?string $state): void {
                            $opdInduk = $state ? OpdInduk::find($state) : null;

                            $set('kode_instansi', $opdInduk?->kode_instansi);
                            $set('instansi', $opdInduk?->instansi);
                        }),
                    TextInput::make('unit_kerja')
                        ->label('Nama Bidang / Unit Kerja')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('singkatan_uk')
                        ->label('Singkatan Bidang / Unit Kerja')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('kode_instansi')
                        ->label('Kode Instansi')
                        ->helperText('Terisi otomatis mengikuti Dinas yang dipilih -- bukan input manual.')
                        // dikunci: nilainya murni turunan dari opd_induks.kode_instansi
                        // (lihat afterStateUpdated di opd_induk_id). ->dehydrated() wajib
                        // biar field yang disabled tetap ikut disimpan ke database --
                        // defaultnya Filament TIDAK menyimpan field yang disabled.
                        ->default(fn (): ?string => static::dinasAdmin()?->kode_instansi)
                        ->disabled()
                        ->dehydrated()
                        ->live(),
                    // Disembunyikan dari tampilan (bukan cuma dikunci) -- nilainya
                    // tetap ikut terinput/tersimpan ke database, cuma tidak
                    // ditampilkan sebagai field di form karena selalu sama persis
                    // dengan opd_induks.instansi milik Dinas yang dipilih (lihat
                    // afterStateUpdated di opd_induk_id).
                    Hidden::make('instansi')
                        ->default(fn (): ?string => static::dinasAdmin()?->instansi),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('unit_kerja')
                    ->label('Nama Bidang')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('opdInduk.instansi')
                    ->label('Dinas')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('kode_instansi')
                    ->label('Kode Instansi')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('singkatan_uk')
                    ->label('Singkatan')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('users_count')
                    ->label('Jumlah Pengguna')
                    ->counts('users')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Tidak relevan buat Admin Dinas (isinya pasti dinasnya sendiri).
                SelectFilter::make('opd_induk_id')
                    ->label('Dinas')
                    ->relationship('opdInduk', 'instansi')
                    ->hidden(fn (): bool => static::isAdminDinas()),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    // Semua FK opd_id di tabel lain (users, periodes, rak_arsips,
                    // dus_arsips, arsips) bersifat nullOnDelete, jadi DB TIDAK akan
                    // menolak penghapusan -- tapi kalau dibiarkan, data-data itu jadi
                    // "yatim" (opd_id null) dan merusak scoping per-bidang (mis.
                    // Pengolah kehilangan acuan bidangnya). Dicegah manual di sini.
                    ->disabled(fn (Opd $record): bool => $record->punyaDataTerkait())
                    ->tooltip(fn (Opd $record): ?string => match (true) {
                        $record->users()->exists() => 'Bidang ini masih punya pengguna terdaftar — pindahkan dulu penggunanya.',
                        $record->periodes()->exists() => 'Bidang ini masih punya data Periode — hapus/pindahkan dulu.',
                        $record->rakArsips()->exists() => 'Bidang ini masih punya data Rak Arsip — hapus/pindahkan dulu.',
                        $record->dusArsips()->exists() => 'Bidang ini masih punya data Dus Arsip — hapus/pindahkan dulu.',
                        $record->arsips()->exists() => 'Bidang ini masih punya data Arsip — hapus/pindahkan dulu.',
                        default => null,
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // Dicek per baris: harus lolos OpdPolicy::delete() DAN
                    // tidak punya data terkait -- selain itu otomatis dilewati.
                    DeleteBulkAction::make()
                        ->authorizeIndividualRecords(fn (Opd $record): bool => (bool) auth()->user()?->can('delete', $record)
                            && ! $record->punyaDataTerkait()),
                ]),
            ])
            ->defaultSort('unit_kerja');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOpds::route('/'),
            'create' => Pages\CreateOpd::route('/create'),
            'edit' => Pages\EditOpd::route('/{record}/edit'),
        ];
    }
}
