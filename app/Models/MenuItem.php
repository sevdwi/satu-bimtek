<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Master menu sidebar -- DUA jenis baris:
 * 1. Modul kode asli (is_custom = false, mayoritas baris, di-seed lewat
 *    MenuPermissionSeeder): 1 baris = 1 Resource/Page Filament beneran.
 *    url/icon/open_in_new_tab kosong -- Resource/Page-nya sendiri yang
 *    tentukan itu, baris ini cuma atur urutan & izin per role.
 * 2. Menu link kustom (is_custom = true, dibuat Superadmin lewat UI
 *    "Manajemen Menu" -> tombol "Tambah Menu (Link)"): TIDAK ada
 *    Resource/Page di baliknya -- baris inilah SATU-SATUNYA sumber menu
 *    itu, jadi didaftarkan manual sebagai Filament\Navigation\NavigationItem
 *    di App\Providers\Filament\AdminPanelProvider::customMenuNavigationItems().
 *    Boleh diedit & DIHAPUS bebas lewat UI (beda dari jenis 1) --
 *    dihapus dari sini = menunya beneran hilang, karena tidak ada modul
 *    lain yang "dijaga".
 *
 * Dipasang ke tiap Resource lewat App\Filament\Concerns\HasMenuPermission
 * (override canViewAny() & getNavigationSort()) -- itu SATU sumber
 * kebenaran, dipakai Filament baik buat visibilitas sidebar MAUPUN akses
 * ke halaman index-nya langsung lewat URL (lihat
 * Filament\Resources\Resource\Concerns\HasNavigation::registerNavigationItems(),
 * yang cek static::canAccess() sebelum daftarin item sidebar).
 */
#[Fillable(['module_key', 'label', 'sort_order', 'url', 'icon', 'open_in_new_tab', 'is_custom'])]
class MenuItem extends Model
{
    use HasFactory;

    public const CACHE_KEY = 'menu-items.all';

    protected function casts(): array
    {
        return [
            'open_in_new_tab' => 'boolean',
            'is_custom' => 'boolean',
        ];
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(MenuPermission::class);
    }

    /**
     * Urutan tampil modul ini di sidebar (dipakai HasMenuPermission::
     * getNavigationSort()). Null kalau modul belum terdaftar di tabel ini
     * -- Resource jatuh balik ke urutan default Filament, BUKAN
     * disembunyikan (lihat MenuPermission::isAllowed() soal alasan yang
     * sama: modul baru yang belum sempat di-seed tidak boleh mendadak
     * hilang/kacau urutannya buat semua orang).
     */
    public static function sortOrder(string $moduleKey): ?int
    {
        return static::allCached()->get($moduleKey)['sort_order'] ?? null;
    }

    /**
     * Semua menu item + matriks izinnya, di-cache selama request berikutnya
     * sampai ada perubahan (lihat flushCache(), dipanggil dari
     * ManajemenMenu::save() & booted() model ini/MenuPermission) -- helper
     * ini dipanggil SANGAT sering (tiap render sidebar, untuk SETIAP
     * Resource yang pakai HasMenuPermission, DAN tiap request buat bangun
     * daftar menu link kustom -- lihat AdminPanelProvider::
     * customMenuNavigationItems()), jadi query DB berulang wajib dihindari.
     *
     * SENGAJA di-cache sebagai ARRAY POLOS (bukan Model/enum Eloquent) --
     * meng-cache object Eloquent/enum lewat driver cache 'file' (default)
     * rawan gagal di-unserialize balik jadi __PHP_Incomplete_Class kalau
     * ada perubahan class map di antara nulis & baca cache (mis. abis
     * composer dump-autoload / deploy). Array polos aman dari masalah itu.
     *
     * @return Collection<string, array{sort_order: int, label: string, url: ?string, icon: ?string, open_in_new_tab: bool, is_custom: bool, permissions: array<string, bool>}>
     */
    public static function allCached(): Collection
    {
        // SENGAJA tidak pakai Cache::rememberForever() -- itu langsung
        // ngembaliin APAPUN yang tersimpan di cache tanpa dicek tipenya,
        // jadi entri lama yang korup (mis. sisa versi kode sebelum ini,
        // yang nyimpen object Eloquent & gagal di-unserialize balik jadi
        // __PHP_Incomplete_Class) akan TERUS kepakai walau sudah di-fix
        // di sini -- bahkan php artisan cache:clear kadang tidak
        // langsung "kena" store/hash file yang sama di sebagian setup
        // Windows. Di sini dicek dulu instance-nya: kalau bukan
        // Collection yang valid (rusak/kosong), query ulang & timpa
        // cache-nya -- self-healing, tidak bergantung user berhasil clear
        // cache secara manual.
        $cached = Cache::get(static::CACHE_KEY);

        if ($cached instanceof Collection) {
            return $cached;
        }

        if (! \Illuminate\Support\Facades\Schema::hasTable('menu_items')) {
            return collect();
        }

        $fresh = static::query()
            ->with('permissions')
            ->orderBy('sort_order')
            ->get()
            ->keyBy('module_key')
            ->map(fn (self $item): array => [
                'sort_order' => $item->sort_order,
                'label' => $item->label,
                'url' => $item->url,
                'icon' => $item->icon,
                'open_in_new_tab' => (bool) $item->open_in_new_tab,
                'is_custom' => (bool) $item->is_custom,
                'permissions' => $item->permissions
                    ->mapWithKeys(fn (MenuPermission $permission): array => [
                        $permission->role->value => (bool) $permission->is_allowed,
                    ])
                    ->all(),
            ]);

        Cache::forever(static::CACHE_KEY, $fresh);

        return $fresh;
    }

    public static function flushCache(): void
    {
        Cache::forget(static::CACHE_KEY);
    }

    /**
     * Auto-flush cache tiap kali menu_items berubah lewat cara APA PUN
     * (bukan cuma lewat ManajemenMenu::save(), yang juga manggil ini
     * manual buat kepastian ekstra) -- termasuk seeder & tinker.
     */
    protected static function booted(): void
    {
        static::saved(fn () => static::flushCache());
        static::deleted(fn () => static::flushCache());
    }
}
