<?php

namespace App\Console\Commands;

use App\Models\Arsip;
use App\Models\Periode;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Susun ulang Nomor Sementara ("{alias_user}-{urut}") semua arsip:
 * per periode, urut 1, 2, 3, ... berdasarkan urutan input (id arsip),
 * dan kembali ke 1 di periode berikutnya.
 *
 * Dipakai buat:
 * - mengisi data LAMA setelah migration urut_sementara dijalankan;
 * - memperbarui awalan nomor setelah user mengisi/mengubah alias_user
 *   (nomor sementara TIDAK otomatis berubah saat alias diganti).
 *
 * Jalankan:
 *   php artisan arsip:susun-nomor-sementara
 *   php artisan arsip:susun-nomor-sementara --periode=5   (satu periode saja)
 */
class SusunNomorSementaraArsip extends Command
{
    protected $signature = 'arsip:susun-nomor-sementara
        {--periode= : ID periode tertentu saja (kosong = semua periode)}';

    protected $description = 'Susun ulang nomor_sementara arsip jadi "{alias_user}-{urut per periode}"';

    public function handle(): int
    {
        $periodeIds = Periode::query()
            ->when($this->option('periode'), fn ($q, $id) => $q->whereKey($id))
            ->pluck('id');

        if ($periodeIds->isEmpty()) {
            $this->warn('Tidak ada periode yang diproses.');

            return self::SUCCESS;
        }

        $total = 0;

        foreach ($periodeIds as $periodeId) {
            DB::transaction(function () use ($periodeId, &$total): void {
                $urut = 1;

                Arsip::query()
                    ->where('periode_id', $periodeId)
                    ->orderBy('id')
                    ->get()
                    ->each(function (Arsip $arsip) use (&$urut, &$total): void {
                        $arsip->forceFill([
                            'urut_sementara' => $urut,
                            'nomor_sementara' => Arsip::prefixNomorSementara($arsip->created_by) . '-' . $urut,
                        ])->saveQuietly();

                        $urut++;
                        $total++;
                    });
            });
        }

        $this->info("Selesai: {$total} arsip di {$periodeIds->count()} periode disusun ulang nomor sementaranya.");

        return self::SUCCESS;
    }
}
