<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\MenuItem;
use App\Models\MenuPermission;
use Illuminate\Database\Seeder;

/**
 * Seed awal fitur "Manajemen Menu" (lihat App\Models\MenuItem &
 * App\Models\MenuPermission) -- isinya SENGAJA dibuat persis sama dengan
 * aturan viewAny() yang di-hardcode di tiap Policy SEBELUM fitur ini ada,
 * supaya begitu di-deploy TIDAK ADA perilaku yang berubah sampai
 * Superadmin sengaja mengubahnya sendiri lewat halaman "Manajemen Menu".
 *
 * `sort_order` juga diseed sesuai urutan alur kerja yang wajar (bukan
 * urutan lama, karena Resource-Resource ini sebelumnya tidak set
 * navigationSort sama sekali / urutan default Filament) -- Superadmin
 * bebas mengubahnya lagi lewat UI.
 */
class MenuPermissionSeeder extends Seeder
{
    /**
     * @var array<string, array{label: string, sort_order: int, roles: array<value-of<UserRole>>}>
     */
    protected const MODULES = [
        'rak_arsip' => [
            'label' => 'Rak Arsip',
            'sort_order' => 10,
            'roles' => ['superadmin', 'admin_dinas', 'pengolah'],
        ],
        'dus_arsip' => [
            'label' => 'Dus Arsip',
            'sort_order' => 20,
            'roles' => ['superadmin', 'admin_dinas', 'pengolah'],
        ],
        'arsip' => [
            'label' => 'Arsip',
            'sort_order' => 30,
            'roles' => ['superadmin', 'admin_dinas', 'pengolah'],
        ],
        'usul_musnah' => [
            'label' => 'Daftar Usul Musnah',
            'sort_order' => 40,
            'roles' => ['superadmin', 'admin_dinas', 'pengolah'],
        ],
        'usul_permanen' => [
            'label' => 'Daftar Usul Permanen',
            'sort_order' => 50,
            'roles' => ['superadmin', 'admin_dinas', 'pengolah'],
        ],
        'periode' => [
            'label' => 'Periode',
            'sort_order' => 60,
            'roles' => ['superadmin', 'admin_dinas', 'pengolah'],
        ],
        'master_kode' => [
            'label' => 'Master Kode',
            'sort_order' => 70,
            'roles' => ['superadmin'],
        ],
        'opd_induk' => [
            'label' => 'OPD Induk (Dinas)',
            'sort_order' => 80,
            'roles' => ['superadmin'],
        ],
        'opd' => [
            'label' => 'OPD (Bidang)',
            'sort_order' => 90,
            // Admin Dinas boleh kelola bidang di dinasnya sendiri (dibatasi
            // OpdPolicy & OpdResource).
            'roles' => ['superadmin', 'admin_dinas'],
        ],
        'user' => [
            'label' => 'User',
            'sort_order' => 100,
            // Pengaman: superadmin utk modul 'user' juga dikunci selalu
            // true di App\Filament\Pages\ManajemenMenu -- di sini cuma
            // nyoceng nilai awalnya biar konsisten.
            // Admin Dinas ikut diizinkan: boleh kelola akun Pengolah di
            // dinasnya sendiri (dibatasi UserPolicy & UserResource).
            'roles' => ['superadmin', 'admin_dinas'],
        ],
    ];

    public function run(): void
    {
        foreach (self::MODULES as $moduleKey => $config) {
            $menuItem = MenuItem::updateOrCreate(
                ['module_key' => $moduleKey],
                ['label' => $config['label'], 'sort_order' => $config['sort_order']],
            );

            foreach (UserRole::cases() as $role) {
                MenuPermission::updateOrCreate(
                    ['menu_item_id' => $menuItem->id, 'role' => $role->value],
                    ['is_allowed' => in_array($role->value, $config['roles'], true)],
                );
            }
        }

        MenuItem::flushCache();
    }
}
