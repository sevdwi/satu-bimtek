<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\MenuItem;
use App\Models\MenuPermission;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Halaman "Manajemen Menu" -- SATU sumber kebenaran urutan tampil menu
 * sidebar & boleh/tidaknya tiap role membuka tiap modul (lihat
 * App\Models\MenuItem, App\Models\MenuPermission,
 * App\Filament\Concerns\HasMenuPermission yang dipasang di tiap Resource).
 *
 * Sengaja custom Page (bukan Resource) karena isinya BUKAN CRUD banyak
 * record, tapi 1 form matriks modul x role + kolom urutan, ditampung 1
 * kali submit.
 *
 * SENGAJA cuma bisa diakses Superadmin (lihat canAccess() &
 * shouldRegisterNavigation() di bawah) -- dan halaman ini SENDIRI TIDAK
 * tunduk ke tabel menu_permissions (tidak pakai HasMenuPermission),
 * supaya Superadmin tidak bisa "mengunci diri sendiri" dari satu-satunya
 * pintu buat membetulkan pengaturan menu.
 *
 * PENSIUN -- digantikan App\Filament\Resources\MenuItemResource (versi
 * "CRUD": tabel, drag buat urutan, toggle langsung per role, tanpa
 * tombol "Simpan" terpisah) yang sekarang mengambil alih URL & item
 * sidebar '/admin/manajemen-menu'. File ini SENGAJA TIDAK dihapus (jaga2
 * kalau perlu rollback cepat) -- slug-nya diganti & disembunyikan dari
 * sidebar (lihat $slug, shouldRegisterNavigation() di bawah) supaya
 * tidak bentrok/dobel sama Resource yang baru.
 */
class ManajemenMenu extends Page
{
    use InteractsWithFormActions;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedBars3;

    protected static ?string $navigationLabel = 'Manajemen Menu (lama)';

    protected static ?string $title = 'Manajemen Menu Sidebar (lama)';

    protected static ?int $navigationSort = 999;

    // Dulu '/admin/manajemen-menu' -- dipindah ke sini supaya URL itu
    // bebas dipakai MenuItemResource (lihat catatan class di atas).
    protected static ?string $slug = 'manajemen-menu-lama';

    protected string $view = 'filament.pages.manajemen-menu';

    /**
     * @var array<string, mixed>
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isSuperadmin();
    }

    /**
     * Sengaja SELALU false -- halaman ini dipensiunkan, digantikan
     * MenuItemResource. Tetap bisa dibuka langsung lewat URL
     * '/admin/manajemen-menu-lama' oleh Superadmin (lihat canAccess())
     * kalau perlu rollback, tapi tidak lagi muncul di sidebar.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function mount(): void
    {
        $this->form->fill($this->loadFormState());
    }

    /**
     * @return array<string, mixed>
     */
    protected function loadFormState(): array
    {
        $state = [];

        foreach (MenuItem::query()->with('permissions')->orderBy('sort_order')->get() as $menuItem) {
            $row = ['sort_order' => $menuItem->sort_order];

            foreach (UserRole::cases() as $role) {
                $row[$role->value] = (bool) $menuItem->permissions
                    ->first(fn (MenuPermission $permission): bool => $permission->role === $role)
                    ?->is_allowed;
            }

            $state[$menuItem->module_key] = $row;
        }

        return ['items' => $state];
    }

    public function form(Schema $schema): Schema
    {
        $menuItems = MenuItem::query()->orderBy('sort_order')->get();

        $sections = $menuItems->map(function (MenuItem $menuItem): Section {
            $isUserSuperadminRow = $menuItem->module_key === 'user';

            $roleToggles = collect(UserRole::cases())->map(function (UserRole $role) use ($menuItem, $isUserSuperadminRow) {
                $isLocked = $isUserSuperadminRow && $role === UserRole::Superadmin;

                return Toggle::make("items.{$menuItem->module_key}.{$role->value}")
                    ->label($role->getLabel())
                    ->default($isLocked ? true : null)
                    ->disabled($isLocked)
                    // Field disabled defaultnya TIDAK ikut ter-submit oleh
                    // Filament -- wajib dehydrated() di sini supaya nilai
                    // "true" yang dikunci tetap ikut dikirim & disimpan.
                    // Pengaman KEDUA (tidak cuma andalkan ini) ada di
                    // save() di bawah, jaga-jaga kalau ada yang utak-atik
                    // request-nya langsung.
                    ->dehydrated();
            })->all();

            return Section::make($menuItem->label)
                ->description("Key modul: {$menuItem->module_key}")
                ->columns(4)
                ->components([
                    TextInput::make("items.{$menuItem->module_key}.sort_order")
                        ->label('Urutan')
                        ->numeric()
                        ->required()
                        ->helperText('Makin kecil, makin atas.'),
                    ...$roleToggles,
                ]);
        })->all();

        return $schema
            ->components($sections)
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make($this->getFormActions())->key('form-actions'),
                ]),
        ]);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Simpan')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $items = $this->form->getState()['items'] ?? [];

        foreach ($items as $moduleKey => $row) {
            $menuItem = MenuItem::where('module_key', $moduleKey)->first();

            if (! $menuItem) {
                continue;
            }

            $menuItem->update(['sort_order' => (int) ($row['sort_order'] ?? $menuItem->sort_order)]);

            foreach (UserRole::cases() as $role) {
                $isAllowed = (bool) ($row[$role->value] ?? false);

                // Pengaman WAJIB (lihat dokumen rencana fitur ini): modul
                // 'user' untuk role Superadmin tidak boleh bisa
                // dinonaktifkan lewat form ini apa pun yang terkirim --
                // supaya tidak ada skenario Superadmin sendiri terkunci
                // dari panel & tidak ada yang bisa buka menu User lagi
                // buat memperbaikinya.
                if ($moduleKey === 'user' && $role === UserRole::Superadmin) {
                    $isAllowed = true;
                }

                MenuPermission::updateOrCreate(
                    ['menu_item_id' => $menuItem->id, 'role' => $role->value],
                    ['is_allowed' => $isAllowed],
                );
            }
        }

        MenuItem::flushCache();

        Notification::make()
            ->title('Pengaturan menu berhasil disimpan')
            ->success()
            ->send();
    }
}
