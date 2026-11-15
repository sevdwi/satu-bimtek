<?php

namespace App\Console\Commands;

use App\Models\DusArsip;
use Illuminate\Console\Command;

/**
 * Isi kolom `qrcode` & `kode_qrcode` buat Dus Arsip yang belum punya --
 * biasanya data LAMA yang dibuat sebelum fitur QR code ini ada, atau data
 * yang masuk ke database lewat cara selain form Create/Edit di panel admin
 * (seeder, tinker, import manual, dll) -- karena kedua kolom itu cuma
 * terisi otomatis lewat App\Filament\Resources\DusArsipResource\Pages\
 * CreateDusArsip::afterCreate() / EditDusArsip::afterSave(), yang HANYA
 * jalan kalau record-nya disimpan lewat form panel Filament.
 *
 * Jalankan dengan:
 *   php artisan dus-arsip:generate-kode-qrcode
 *
 * Tambahkan --force buat menimpa ULANG qrcode & kode_qrcode yang sudah ada
 * (jarang perlu -- kode_qrcode sengaja dibuat sekali & tidak pernah
 * berubah lagi karena sudah tercetak fisik di dus, lihat
 * App\Models\DusArsip::generateKodeQrcode()).
 */
class GenerateKodeQrcodeDusArsip extends Command
{
    protected $signature = 'dus-arsip:generate-kode-qrcode
        {--force : Timpa ulang qrcode & kode_qrcode yang sudah pernah terisi}';

    protected $description = 'Isi kolom qrcode & kode_qrcode buat Dus Arsip yang belum punya (data lama / dibuat lewat seeder-tinker)';

    public function handle(): int
    {
        $force = (bool) $this->option('force');

        $query = DusArsip::query();

        if (! $force) {
            $query->where(function ($q) {
                $q->whereNull('qrcode')
                    ->orWhere('qrcode', '')
                    ->orWhereNull('kode_qrcode')
                    ->orWhere('kode_qrcode', '');
            });
        }

        $dusArsips = $query->get();

        if ($dusArsips->isEmpty()) {
            $this->info('Semua Dus Arsip sudah punya qrcode & kode_qrcode. Tidak ada yang diproses.');

            return self::SUCCESS;
        }

        $this->info("Memproses {$dusArsips->count()} Dus Arsip...");
        $bar = $this->output->createProgressBar($dusArsips->count());
        $bar->start();

        foreach ($dusArsips as $dusArsip) {
            $dusArsip->qrcode = route('dus-arsip.rangkuman', $dusArsip);

            if ($force || blank($dusArsip->kode_qrcode)) {
                // Pakai created_at dus itu sendiri (bukan waktu command ini
                // dijalankan) supaya kode barunya tetap mencerminkan tanggal
                // dus SEBENARNYA dibuat -- lihat catatan di
                // DusArsip::generateKodeQrcode().
                $dusArsip->kode_qrcode = DusArsip::generateKodeQrcode(
                    $dusArsip->opd_induk_id,
                    $dusArsip->rak_arsip_id,
                    $dusArsip->created_at,
                );
            }

            $dusArsip->saveQuietly();
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Selesai -- {$dusArsips->count()} Dus Arsip diperbarui.");
        $this->comment('Kalau ini dijalankan lewat "php artisan serve" atau CLI, pastikan APP_URL di .env sudah sesuai domain yang dipakai user (kolom qrcode berisi URL lengkap ke halaman rangkuman).');

        return self::SUCCESS;
    }
}
