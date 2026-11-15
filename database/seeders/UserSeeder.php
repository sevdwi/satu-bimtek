<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * 3 user starter sesuai role baru. opd_id/opd_induk_id sengaja belum
     * diisi (nullable) — belum ada OpdSeeder/OpdIndukSeeder. NIP di sini
     * cuma contoh format (18 digit, format NIP PNS) — ganti sesuai data
     * pegawai beneran nanti lewat panel Filament.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Superadmin',
            'email' => 'superadmin@satu.test',
            'nip' => null, // akun teknis, bukan pegawai — tidak punya NIP
            'phone_number' => '085747131691',
            'password' => 'superadmin2026',
            'role' => UserRole::Superadmin,
            'status' => UserStatus::Active,
        ]);

        User::create([
            'name' => 'Admin Dinas Contoh',
            'email' => 'admindinas@satu.test',
            'nip' => '198501012015031001',
            'phone_number' => '081234567890',
            'password' => 'admindinas',
            'role' => UserRole::AdminDinas,
            'status' => UserStatus::Active,
        ]);

        User::create([
            'name' => 'Pengolah Contoh',
            'email' => 'pengolah@satu.test',
            'nip' => '199203102019032002',
            'phone_number' => '081987654321',
            'password' => 'pengolah',
            'role' => UserRole::Pengolah,
            'status' => UserStatus::Active,
        ]);
    }
}
