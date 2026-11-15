<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\MenuItem;
use App\Models\MenuPermission;
use App\Models\RepoDokFolder;
use Illuminate\Database\Seeder;

/**
 * Isi AWAL struktur folder "Repositori Dokumentasi" (/repo_dok), persis
 * struktur yang diminta user. SENGAJA pakai firstOrCreate (bukan
 * updateOrCreate) supaya idempoten TANPA menimpa balik nama/urutan folder
 * yang sudah diubah Superadmin sendiri lewat UI (lihat
 * App\Filament\Resources\RepoDokFolderResource) -- struktur ini cuma
 * titik awal, bukan sumber kebenaran permanen (lihat catatan di migration
 * create_repo_dok_folders_table soal "dikelola dinamis").
 *
 * Juga menambahkan SATU baris "menu link kustom" di sidebar Filament
 * (lihat App\Models\MenuItem) yang mengarah ke /repo_dok, terlihat oleh
 * SEMUA role -- supaya user tidak perlu tahu/mengetik URL-nya sendiri.
 * Baris ini SAMA seperti kalau Superadmin membuatnya sendiri lewat
 * "Manajemen Menu" -> "Tambah Menu (Link)" -- boleh diedit/dihapus bebas
 * dari sana kalau suatu saat tidak diperlukan lagi (lihat
 * App\Filament\Resources\MenuItemResource).
 */
class RepoDokFolderSeeder extends Seeder
{
    /**
     * @var array<string, array{deskripsi: ?string, anak: array<string>}>
     */
    protected const STRUKTUR = [
        '01. Dokumen Aplikasi' => [
            'deskripsi' => 'SK Pengembangan, KAK, Arsitektur Aplikasi, Manual Pengguna.',
            'anak' => ['SK Pengembangan', 'KAK', 'Arsitektur Aplikasi', 'Manual Pengguna'],
        ],
        '02. Source Code' => [
            'deskripsi' => null,
            'anak' => ['Backend', 'Frontend', 'Database'],
        ],
        '03. Konfigurasi' => [
            'deskripsi' => 'Dokumentasi konfigurasi server.',
            'anak' => [],
        ],
        '04. Database' => [
            'deskripsi' => 'Backup/Data hasil ekspor.',
            'anak' => [],
        ],
        '05. Pengembangan & Perubahan' => [
            'deskripsi' => null,
            'anak' => ['Versi 1.0', 'Versi 1.1', 'Versi 2.0'],
        ],
        '06. Terminasi' => [
            'deskripsi' => null,
            'anak' => ['SK Terminasi', 'Berita Acara Terminasi', 'Backup Terakhir', 'Bukti Penonaktifan'],
        ],
    ];

    public function run(): void
    {
        $urutanIndukKe = 0;

        foreach (self::STRUKTUR as $nama => $config) {
            $urutanIndukKe += 10;

            $induk = RepoDokFolder::query()->firstOrCreate(
                ['nama' => $nama, 'parent_id' => null],
                ['deskripsi' => $config['deskripsi'], 'urutan' => $urutanIndukKe],
            );

            $urutanAnakKe = 0;

            foreach ($config['anak'] as $namaAnak) {
                $urutanAnakKe += 10;

                RepoDokFolder::query()->firstOrCreate(
                    ['nama' => $namaAnak, 'parent_id' => $induk->id],
                    ['urutan' => $urutanAnakKe],
                );
            }
        }

        $this->seedMenuLink();
    }

    protected function seedMenuLink(): void
    {
        $menuItem = MenuItem::query()->firstOrCreate(
            ['module_key' => 'custom-repo-dok'],
            [
                'label' => 'Repositori Dokumentasi',
                'url' => '/repo_dok',
                'icon' => 'heroicon-o-book-open',
                'open_in_new_tab' => false,
                'is_custom' => true,
                'sort_order' => ((int) MenuItem::query()->max('sort_order')) + 1,
            ],
        );

        foreach (UserRole::cases() as $role) {
            MenuPermission::query()->firstOrCreate(
                ['menu_item_id' => $menuItem->id, 'role' => $role->value],
                ['is_allowed' => true],
            );
        }

        MenuItem::flushCache();
    }
}
