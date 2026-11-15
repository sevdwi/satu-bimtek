<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MasterKodeSeeder extends Seeder
{
    /**
     * Import data kode klasifikasi arsip dari database SATU V1
     * (satu10sep_db1.sql, tabel master_kodes, 2.952 baris).
     *
     * Perbaikan yang dilakukan saat konversi (lihat juga AUDIT-KODE-SATU.md):
     * - `parent_id = 0` (penanda "tidak ada induk" di skema lama) diubah jadi NULL,
     *   biar bisa dipasangi foreign key beneran ke tabel ini sendiri.
     * - `level` dihitung ULANG dari rantai parent_id yang sebenarnya (BFS dari 8
     *   baris root) — bukan dipakai apa adanya, karena di data sumber 92% baris
     *   punya level=0 alias tidak pernah diisi dengan benar.
     * - `keterangan` (nasib akhir: Musnah/Permanen, ada yang ditulis "permanen"
     *   huruf kecil) dinormalisasi ke lowercase supaya cocok sama backed enum
     *   App\Enums\NasibAkhir.
     * - 10 baris (root "0" + 9 anak langsungnya "0.1".."0.9") segmen pertama
     *   kode-nya cuma 1 digit, beda sama cabang lain yang konsisten 3 digit
     *   ("100", "300", dst). Dinormalisasi jadi "000" dan "000.1".."000.9".
     * - `sort_key`: tiap segmen kode di-zero-pad 4 digit buat ORDER BY yang
     *   benar secara hierarki (lihat migration add_sort_key_to_master_kodes_table).
     * - Kolom `is_parent` dari skema lama DIBUANG (nilainya di data sumber malah
     *   ngaco, ada yang isinya 2-8 padahal harusnya boolean) — di skema baru ini
     *   dihitung dari relasi children(), bukan kolom tersimpan.
     * - Ada 2 kode yang BENTROK persis sama di data sumber (bukan salah baca,
     *   memang data aslinya begitu): kode "400.1" dipakai 2x ("PEMBANGUNAN DAERAH
     *   TERTINGGAL" & "PEMBERDAYAAN MASYARAKAT DESA") dan "500.1" dipakai 2x
     *   ("KETAHANAN PANGAN" & "ENERGI DAN SUMBER DAYA MINERAL"). Supaya nggak
     *   melanggar constraint unique(kode) di skema baru, baris kedua masing-masing
     *   ditandai ulang jadi "400.1-2" dan "500.1-2" — perlu dicek manual ke Dinas
     *   Arpus kode mana yang benar kalau mau dirapikan lagi.
     *
     * id baris SENGAJA dipertahankan sama seperti data sumber (bukan
     * auto-increment dari 1) supaya parent_id di dalam file JSON tetap valid
     * tanpa perlu remapping. Idempotent: pakai upsert, aman dijalankan ulang.
     */
    public function run(): void
    {
        $path = database_path('seeders/data/master_kodes.json');

        if (! is_file($path)) {
            $this->command?->warn("File data master_kodes tidak ketemu di: {$path}. Skip seeding.");

            return;
        }

        $rows = json_decode(file_get_contents($path), associative: true, flags: JSON_THROW_ON_ERROR);

        $now = Carbon::now();

        collect($rows)
            ->map(fn (array $row): array => [
                'id' => $row['id'],
                'kode' => $row['kode'],
                'sort_key' => $row['sort_key'],
                'parent_id' => $row['parent_id'],
                'level' => $row['level'],
                'nama' => $row['nama'],
                'aktif' => $row['aktif'],
                'inaktif' => $row['inaktif'],
                'keterangan' => $row['keterangan'],
                'created_at' => $now,
                'updated_at' => $now,
            ])
            // urutan ascending id penting: root (parent_id NULL) selalu ke-insert
            // duluan sebelum baris yang mereferensikannya lewat FK parent_id.
            ->sortBy('id')
            ->chunk(500)
            ->each(function ($chunk): void {
                DB::table('master_kodes')->upsert(
                    $chunk->values()->all(),
                    ['id'],
                    ['kode', 'sort_key', 'parent_id', 'level', 'nama', 'aktif', 'inaktif', 'keterangan', 'updated_at'],
                );
            });

        $this->command?->info('MasterKodeSeeder: '.count($rows).' baris kode klasifikasi ter-upsert.');
    }
}
