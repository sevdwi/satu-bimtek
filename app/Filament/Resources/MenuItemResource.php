<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\MenuItemResource\Pages;
use App\Models\MenuItem;
use App\Models\MenuPermission;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Versi CRUD (tabel, bisa di-drag urut & toggle langsung per baris) dari
 * fitur "Manajemen Menu" -- MENGGANTIKAN App\Filament\Pages\ManajemenMenu
 * (form matriks 1 halaman panjang) di URL yang SAMA, '/admin/manajemen-menu'
 * (lihat $slug di bawah). Page lama TIDAK dihapus (cuma dipensiunkan --
 * slug-nya diganti & disembunyikan dari sidebar), jaga-jaga kalau perlu
 * rollback tanpa perlu tulis ulang.
 *
 * Baris di tabel ini ada DUA jenis (lihat App\Models\MenuItem untuk
 * penjelasan lengkap & kenapa dibedakan):
 * - Modul kode asli (is_custom = false) -- TIDAK bisa ditambah/diedit/
 *   dihapus dari sini (Create/Edit/Delete disembunyikan untuk baris ini).
 *   Create tidak masuk akal (baris baru tidak nempel ke Resource/Page
 *   manapun), dan Delete berbahaya (MenuPermission::isAllowed() fallback
 *   ke "boleh" kalau module_key hilang dari tabel ini -- hapus baris
 *   modul asli malah MEMBUKA akses, bukan menutupnya).
 * - Menu link kustom (is_custom = true) -- BOLEH ditambah lewat tombol
 *   "Tambah Menu (Link)" & dihapus bebas, karena baris itu SENDIRI
 *   satu-satunya sumber menunya (bukan gerbang izin ke modul lain) --
 *   lihat Pages\CreateMenuItem, Pages\EditMenuItem, dan pendaftarannya
 *   sebagai NavigationItem di App\Providers\Filament\AdminPanelProvider.
 *
 * Urutan (drag) & toggle role tetap berlaku buat KEDUA jenis baris,
 * lewat tabel index yang sama -- lihat Pages\ListMenuItems.
 */
class MenuItemResource extends Resource
{
    protected static ?string $model = MenuItem::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedBars3;

    protected static ?string $navigationLabel = 'Manajemen Menu';

    protected static ?string $modelLabel = 'Menu';

    protected static ?string $pluralModelLabel = 'Manajemen Menu';

    protected static ?string $slug = 'manajemen-menu';

    protected static ?int $navigationSort = 999;

    /**
     * Pilihan ikon yang aman & relevan buat menu link kustom -- daftar
     * pendek nama heroicon (format sama dengan yang dipakai Filament::
     * Support\Icons\Heroicon di seluruh app ini, mis. 'heroicon-o-link'),
     * BUKAN daftar bebas supaya admin tidak salah ketik nama ikon yang
     * tidak ada (bikin ikon menu jadi kosong/rusak).
     *
     * @return array<string, string>
     */
    public static function customIconOptions(): array
    {
        return [
            'heroicon-o-link' => 'Tautan (default)',
            'heroicon-o-globe-alt' => 'Situs luar',
            'heroicon-o-document-text' => 'Dokumen',
            'heroicon-o-qr-code' => 'QR Code',
            'heroicon-o-megaphone' => 'Pengumuman',
            'heroicon-o-information-circle' => 'Informasi',
            'heroicon-o-map-pin' => 'Lokasi',
            'heroicon-o-phone' => 'Kontak',
            'heroicon-o-book-open' => 'Panduan',
        ];
    }

    /**
     * SENGAJA cuma Superadmin -- sama seperti ManajemenMenu::canAccess()
     * yang digantikan, dan (sama alasannya) TIDAK pakai trait
     * HasMenuPermission supaya Superadmin tidak bisa mengunci diri
     * sendiri dari satu-satunya pintu buat membetulkan pengaturan menu.
     */
    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isSuperadmin();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return (bool) auth()->user()?->isSuperadmin();
    }

    /**
     * Dipakai Create & Edit -- KEDUANYA cuma untuk menu link kustom (lihat
     * catatan class di atas). 'visible_roles' BUKAN kolom di menu_items --
     * cuma checkbox bantu supaya begitu menu baru dibuat, admin sekalian
     * pilih role mana yang langsung boleh lihat, tanpa harus buka tabel
     * index dulu buat toggle satu-satu (lihat Pages\CreateMenuItem::
     * afterCreate()). Makanya field ini disembunyikan lagi di Edit --
     * peran yang sama sudah bisa diatur lewat toggle di tabel index.
     */
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Menu Link')
                ->description('Menu ini murni tautan (bukan modul/fitur baru dengan tabel sendiri) -- cocok mengarahkan ke halaman publik di aplikasi ini atau ke situs luar, tanpa perlu tulis kode.')
                ->columns(2)
                ->components([
                    TextInput::make('label')
                        ->label('Nama Menu')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    TextInput::make('url')
                        ->label('URL Tujuan')
                        ->required()
                        ->maxLength(255)
                        ->helperText('Alamat di dalam aplikasi ini (mis. /lacak-dus) atau alamat lengkap ke situs lain (mis. https://...).')
                        ->columnSpanFull(),
                    Select::make('icon')
                        ->label('Ikon')
                        ->options(static::customIconOptions())
                        ->default('heroicon-o-link')
                        ->native(false)
                        ->required(),
                    Toggle::make('open_in_new_tab')
                        ->label('Buka di tab baru')
                        ->default(false)
                        ->inline(false),
                    CheckboxList::make('visible_roles')
                        ->label('Bisa dilihat oleh role')
                        ->options(collect(UserRole::cases())->mapWithKeys(fn (UserRole $role): array => [$role->value => $role->getLabel()]))
                        ->default(collect(UserRole::cases())->pluck('value')->all())
                        ->columns(3)
                        ->columnSpanFull()
                        ->visible(fn (string $operation): bool => $operation === 'create')
                        ->dehydrated(fn (string $operation): bool => $operation === 'create')
                        ->helperText('Bisa diubah lagi kapan saja lewat toggle role di tabel Manajemen Menu.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('permissions'))
            ->columns([
                TextColumn::make('label')
                    ->label('Menu')
                    ->weight('bold')
                    ->description(fn (MenuItem $record): string => $record->is_custom
                        ? $record->url
                        : "Key modul: {$record->module_key}"),
                TextColumn::make('is_custom')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Link Kustom' : 'Modul Kode')
                    ->color(fn (bool $state): string => $state ? 'warning' : 'gray'),
                ...collect(UserRole::cases())->map(function (UserRole $role): ToggleColumn {
                    return ToggleColumn::make("role_{$role->value}")
                        ->label($role->getLabel())
                        ->alignCenter()
                        ->getStateUsing(fn (MenuItem $record): bool => (bool) $record->permissions
                            ->first(fn (MenuPermission $permission): bool => $permission->role === $role)
                            ?->is_allowed)
                        // Pengaman yang SAMA seperti ManajemenMenu::save() lama:
                        // modul 'user' utk role Superadmin tidak boleh bisa
                        // dinonaktifkan lewat toggle ini apa pun yang dikirim.
                        ->disabled(fn (MenuItem $record): bool => $record->module_key === 'user' && $role === UserRole::Superadmin)
                        ->updateStateUsing(function (MenuItem $record, bool $state) use ($role): bool {
                            if ($record->module_key === 'user' && $role === UserRole::Superadmin) {
                                $state = true;
                            }

                            MenuPermission::updateOrCreate(
                                ['menu_item_id' => $record->id, 'role' => $role->value],
                                ['is_allowed' => $state],
                            );

                            return $state;
                        });
                })->all(),
            ])
            ->recordActions([
                // Edit/Delete CUMA buat menu link kustom -- lihat catatan
                // class di atas soal kenapa modul kode asli tidak boleh
                // diedit/dihapus dari sini.
                EditAction::make()
                    ->visible(fn (MenuItem $record): bool => $record->is_custom),
                DeleteAction::make()
                    ->visible(fn (MenuItem $record): bool => $record->is_custom)
                    ->modalDescription('Menu link ini akan dihapus permanen dari sidebar SEMUA role yang sebelumnya bisa melihatnya. Tindakan ini tidak bisa dibatalkan.'),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->paginated(false);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMenuItems::route('/'),
            'create' => Pages\CreateMenuItem::route('/create'),
            'edit' => Pages\EditMenuItem::route('/{record}/edit'),
        ];
    }
}
