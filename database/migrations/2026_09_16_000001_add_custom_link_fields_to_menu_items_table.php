<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Field tambahan buat menu_items supaya bisa nyimpen "menu link kustom" --
 * baris yang BUKAN modul/Resource kode (lihat App\Filament\Resources\
 * MenuItemResource\Pages\CreateMenuItem & App\Providers\Filament\
 * AdminPanelProvider::customMenuNavigationItems()), tapi tautan biasa
 * (internal mis. /lacak-dus, atau situs luar) yang bisa ditambah/dihapus
 * langsung dari UI Manajemen Menu tanpa tulis kode.
 *
 * is_custom = false (default) buat baris LAMA yang sudah ada (semuanya
 * modul kode asli, lihat MenuPermissionSeeder) -- baris itu TETAP tidak
 * bisa diedit/dihapus lewat UI (lihat MenuItemResource::table()), url/
 * icon/open_in_new_tab-nya dibiarkan kosong karena Resource/Page kode
 * sudah punya ikonnya sendiri & URL-nya otomatis dari routing Filament.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->string('url')->nullable()->after('label');
            $table->string('icon')->nullable()->after('url');
            $table->boolean('open_in_new_tab')->default(false)->after('icon');
            $table->boolean('is_custom')->default(false)->after('open_in_new_tab');
        });
    }

    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropColumn(['url', 'icon', 'open_in_new_tab', 'is_custom']);
        });
    }
};
