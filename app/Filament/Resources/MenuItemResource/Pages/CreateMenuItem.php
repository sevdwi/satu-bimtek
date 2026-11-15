<?php

namespace App\Filament\Resources\MenuItemResource\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\MenuItemResource;
use App\Models\MenuItem;
use App\Models\MenuPermission;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

/**
 * SATU-SATUNYA cara bikin baris menu_items baru dari UI -- dan hasilnya
 * SELALU menu link kustom (is_custom = true, lihat catatan lengkap di
 * App\Models\MenuItem & App\Filament\Resources\MenuItemResource). Tidak
 * ada opsi bikin baris buat modul kode di form ini SAMA SEKALI, karena
 * itu cuma bisa muncul lewat MenuPermissionSeeder begitu Resource/Page-nya
 * ditulis di kode.
 */
class CreateMenuItem extends CreateRecord
{
    protected static string $resource = MenuItemResource::class;

    /**
     * Role yang dicentang di field bantu "visible_roles" (BUKAN kolom di
     * menu_items, lihat MenuItemResource::form()) -- ditampung di sini
     * waktu mutateFormDataBeforeCreate(), lalu dipindah jadi baris
     * MenuPermission di afterCreate() (butuh $this->record->id yang baru
     * ada setelah insert).
     *
     * @var array<string>
     */
    protected array $visibleRoles = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->visibleRoles = $data['visible_roles'] ?? [];
        unset($data['visible_roles']);

        $data['is_custom'] = true;
        $data['module_key'] = $this->generateModuleKey($data['label']);
        $data['sort_order'] = ((int) MenuItem::query()->max('sort_order')) + 1;

        return $data;
    }

    /**
     * module_key WAJIB unik (lihat migration menu_items) tapi tidak
     * pernah diketik manual oleh admin di sini -- diturunkan dari label,
     * diberi prefix "custom-" biar gampang dibedakan dari module_key
     * modul kode asli (mis. "dus_arsip", "arsip") kalau suatu saat perlu
     * di-debug langsung dari database. Tambahan angka urut kalau slug-nya
     * kebetulan sudah dipakai menu kustom lain.
     */
    protected function generateModuleKey(string $label): string
    {
        $base = 'custom-' . Str::slug($label);
        $key = $base !== 'custom-' ? $base : 'custom-menu';
        $suffix = 1;

        while (MenuItem::query()->where('module_key', $key)->exists()) {
            $suffix++;
            $key = "{$base}-{$suffix}";
        }

        return $key;
    }

    protected function afterCreate(): void
    {
        foreach (UserRole::cases() as $role) {
            MenuPermission::updateOrCreate(
                ['menu_item_id' => $this->record->id, 'role' => $role->value],
                ['is_allowed' => in_array($role->value, $this->visibleRoles, true)],
            );
        }

        MenuItem::flushCache();
    }
}
