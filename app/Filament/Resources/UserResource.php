<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Concerns\HasMenuPermission;
use App\Filament\Resources\UserResource\Pages;
use App\Models\MenuPermission;
use App\Models\Opd;
use App\Models\User;
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
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Password;
use STS\FilamentImpersonate\Actions\Impersonate;

class UserResource extends Resource
{
    use HasMenuPermission;

    /** Key modul buat fitur "Manajemen Menu" -- lihat DusArsipResource. */
    protected static string $menuKey = 'user';

    protected static ?string $model = User::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Pengguna';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'Pengguna';

    protected static ?string $pluralModelLabel = 'Pengguna';

    /**
     * Gerbang menu + akses halaman index. Trait HasMenuPermission defaultnya
     * CUMA ngecek matriks Manajemen Menu -- khusus modul Pengguna, policy
     * viewAny() ikut diwajibkan juga, supaya Pengolah TIDAK PERNAH bisa
     * membuka manajemen akun walaupun menu 'user' kebetulan diizinkan /
     * belum terdaftar di tabel menu_items (fallback "boleh" di
     * MenuPermission::isAllowed()). Superadmin & Admin Dinas tetap diatur
     * lewat Manajemen Menu seperti biasa.
     */
    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return MenuPermission::isAllowed(static::$menuKey, $user?->role)
            && (bool) $user?->can('viewAny', User::class);
    }

    /**
     * Admin Dinas cuma lihat akun Pengolah di dinasnya sendiri. Superadmin
     * tetap lihat semua akun.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user?->isAdminDinas()) {
            return $query
                ->where('role', UserRole::Pengolah->value)
                ->where('opd_induk_id', $user->opd_induk_id);
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

    /**
     * Pengaman server-side buat Create/EditUser (jangan percaya nilai dari
     * form, field yang disabled tetap bisa diutak-atik lewat request):
     * kalau yang menyimpan Admin Dinas, role DIPAKSA Pengolah, dinas DIPAKSA
     * dinas si Admin Dinas, dan bidang WAJIB salah satu bidang di dinas itu.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function enforceAdminDinasScope(array $data): array
    {
        $user = auth()->user();

        if (! $user?->isAdminDinas()) {
            return $data;
        }

        $data['role'] = UserRole::Pengolah->value;
        $data['opd_induk_id'] = $user->opd_induk_id;

        $bidangValid = filled($data['opd_id'] ?? null)
            && Opd::query()
                ->whereKey($data['opd_id'])
                ->where('opd_induk_id', $user->opd_induk_id)
                ->exists();

        if (! $bidangValid) {
            Notification::make()
                ->title('Bidang tidak valid')
                ->body('Pilih bidang yang berada di bawah dinas Anda.')
                ->danger()
                ->send();

            throw new Halt();
        }

        return $data;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Data Akun')
                ->columns(2)
                ->components([
                    TextInput::make('name')
                        ->label('Nama')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('alias_user')
                        ->label('Alias User')
                        ->helperText('Awalan Nomor Sementara arsip. User juga bisa mengubahnya sendiri lewat halaman Profil.')
                        ->maxLength(30)
                        ->regex('/^[A-Za-z0-9_.]+$/')
                        ->unique(ignoreRecord: true)
                        ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? trim($state) : null),
                    TextInput::make('nip')
                        ->label('NIP')
                        ->helperText('Boleh dikosongkan untuk akun teknis (mis. superadmin).')
                        ->unique(ignoreRecord: true)
                        ->maxLength(30),
                    TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),
                    TextInput::make('phone_number')
                        ->label('No. HP')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(15),
                    TextInput::make('password')
                        ->label('Password Baru')
                        ->password()
                        ->revealable()
                        ->required(fn (string $operation): bool => $operation === 'create')
                        ->dehydrated(fn (?string $state): bool => filled($state))
                        // 'nullable' HARUS di rule pertama -- begitu state-nya
                        // kosong (saat edit, tidak mau ganti password), Laravel
                        // langsung skip semua rule SESUDAHNYA (kompleksitas
                        // password & 'confirmed') tanpa perlu dicek manual di
                        // sini. Cuma berlaku kalau operasinya 'edit'; di
                        // 'create' field ini sudah dipaksa required() di atas
                        // jadi tidak akan pernah lolos kosong.
                        ->rule('nullable')
                        ->rule(Password::min(8)->mixedCase()->numbers()->symbols())
                        // Wajib sama dengan field 'password_confirmation' di
                        // bawah -- verifikasi input ulang password baru.
                        ->confirmed()
                        ->helperText('Minimal 8 karakter, kombinasi huruf kapital, angka, dan simbol. Saat edit, kosongkan kedua kolom password kalau tidak mau menggantinya.'),
                    TextInput::make('password_confirmation')
                        ->label('Konfirmasi Password Baru')
                        ->password()
                        ->revealable()
                        // Field ini CUMA buat verifikasi input ulang (dicek
                        // lewat ->confirmed() di field 'password' di atas) --
                        // nilainya sendiri tidak pernah disimpan ke database.
                        ->dehydrated(false)
                        ->requiredWith('password')
                        ->helperText('Ulangi persis sama dengan Password Baru di atas.'),
                ]),

            Section::make('Role & Status')
                ->columns(2)
                ->components([
                    Select::make('role')
                        ->label('Role')
                        // Admin Dinas cuma boleh bikin/ubah akun Pengolah --
                        // field dikunci & nilainya dipaksa lagi di server
                        // (enforceAdminDinasScope()).
                        ->options(fn (): array => collect(static::isAdminDinas() ? [UserRole::Pengolah] : UserRole::cases())
                            ->mapWithKeys(fn (UserRole $role): array => [$role->value => $role->getLabel()])
                            ->all())
                        ->default(fn (): ?string => static::isAdminDinas() ? UserRole::Pengolah->value : null)
                        ->disabled(fn (): bool => static::isAdminDinas())
                        ->dehydrated()
                        ->selectablePlaceholder(fn (): bool => ! static::isAdminDinas())
                        ->helperText(fn (): ?string => static::isAdminDinas()
                            ? 'Admin Dinas hanya dapat mengelola akun Pengolah di dinasnya sendiri.'
                            : null)
                        ->required(),
                    Select::make('status')
                        ->label('Status')
                        ->options(UserStatus::class)
                        ->required(),
                ]),

            Section::make('Instansi')
                ->columns(2)
                ->components([
                    Select::make('opd_induk_id')
                        ->label('Dinas (OPD Induk)')
                        ->relationship(
                            name: 'opdInduk',
                            titleAttribute: 'instansi',
                            modifyQueryUsing: fn (Builder $query) => $query->when(
                                static::isAdminDinas(),
                                fn (Builder $query) => $query->whereKey(auth()->user()->opd_induk_id),
                            ),
                        )
                        // Admin Dinas: otomatis dinasnya sendiri & dikunci.
                        ->default(fn (): ?int => static::isAdminDinas() ? auth()->user()->opd_induk_id : null)
                        ->disabled(fn (): bool => static::isAdminDinas())
                        ->dehydrated()
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(fn ($set) => $set('opd_id', null)),
                    Select::make('opd_id')
                        ->label('Bidang (OPD)')
                        ->relationship(
                            name: 'opd',
                            titleAttribute: 'unit_kerja',
                            modifyQueryUsing: fn (Builder $query, Get $get) => $query->when(
                                static::isAdminDinas() ? auth()->user()->opd_induk_id : $get('opd_induk_id'),
                                fn (Builder $query, $opdIndukId) => $query->where('opd_induk_id', $opdIndukId),
                            ),
                        )
                        // Akun Pengolah wajib punya bidang -- dipaksa buat
                        // Admin Dinas (yang selalu bikin Pengolah).
                        ->required(fn (): bool => static::isAdminDinas())
                        ->searchable()
                        ->preload(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('alias_user')
                    ->label('Alias')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('nip')
                    ->label('NIP')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone_number')
                    ->label('No. HP')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('role')
                    ->badge()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('opdInduk.instansi')
                    ->label('Dinas')
                    ->toggleable(),
                TextColumn::make('opd.unit_kerja')
                    ->label('Bidang')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Filter Role & Dinas tidak relevan buat Admin Dinas (isi
                // tabelnya pasti Pengolah di dinasnya sendiri).
                SelectFilter::make('role')
                    ->options(UserRole::class)
                    ->hidden(fn (): bool => static::isAdminDinas()),
                SelectFilter::make('status')->options(UserStatus::class),
                SelectFilter::make('opd_induk_id')
                    ->label('Dinas')
                    ->relationship('opdInduk', 'instansi')
                    ->hidden(fn (): bool => static::isAdminDinas()),
                SelectFilter::make('opd_id')
                    ->label('Bidang')
                    ->relationship(
                        'opd',
                        'unit_kerja',
                        fn (Builder $query) => $query->when(
                            static::isAdminDinas(),
                            fn (Builder $query) => $query->where('opd_induk_id', auth()->user()->opd_induk_id),
                        ),
                    ),
            ])
            ->recordActions([
                Impersonate::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
