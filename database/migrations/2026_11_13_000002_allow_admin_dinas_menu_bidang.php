<?php

use App\Models\MenuItem;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Data migration (additive, tidak mengubah skema): buka menu "Bidang"
 * (module_key 'opd') untuk role Admin Dinas di matriks Manajemen Menu,
 * supaya Admin Dinas bisa menambah & mengelola bidang di dinasnya sendiri
 * (batasannya ada di App\Policies\OpdPolicy &
 * App\Filament\Resources\OpdResource). Kalau baris menu 'opd' belum ada
 * (MenuPermissionSeeder belum pernah dijalankan), tidak melakukan apa-apa.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->setAdminDinas(true);
    }

    public function down(): void
    {
        $this->setAdminDinas(false);
    }

    private function setAdminDinas(bool $allowed): void
    {
        if (! Schema::hasTable('menu_items') || ! Schema::hasTable('menu_permissions')) {
            return;
        }

        $menuItemId = DB::table('menu_items')->where('module_key', 'opd')->value('id');

        if (! $menuItemId) {
            return;
        }

        DB::table('menu_permissions')->updateOrInsert(
            ['menu_item_id' => $menuItemId, 'role' => 'admin_dinas'],
            ['is_allowed' => $allowed, 'updated_at' => now(), 'created_at' => now()],
        );

        MenuItem::flushCache();
    }
};
