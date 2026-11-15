<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Dashboard;
use Filament\Schemas\Schema;

/**
 * Halaman Profil (menu user di pojok kanan atas -> "Profil"). Di sini tiap
 * user bisa mengisi/mengubah Alias User-nya sendiri -- dipakai sebagai
 * awalan Nomor Sementara arsip ("{alias_user}-{urut}", lihat
 * App\Models\Arsip::isiNomorSementara()).
 *
 * Sengaja cuma alias yang bisa diubah: nama/email/password tetap dikelola
 * Superadmin lewat menu Pengguna (UserResource).
 */
class EditProfile extends BaseEditProfile
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama')
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('alias_user')
                    ->label('Alias User')
                    ->maxLength(30)
                    ->regex('/^[A-Za-z0-9_.]+$/')
                    ->unique(table: 'users', column: 'alias_user', ignoreRecord: true)
                    ->helperText('Dipakai sebagai awalan Nomor Sementara arsip, mis. "UMI-1". Huruf, angka, titik, atau garis bawah (tanpa spasi). Kosongkan untuk memakai ID user.')
                    ->validationMessages([
                        'regex' => 'Alias hanya boleh berisi huruf, angka, titik, atau garis bawah (tanpa spasi).',
                        'unique' => 'Alias ini sudah dipakai user lain.',
                    ]),
            ]);
    }

    /**
     * Setelah alias berhasil disimpan, langsung kembali ke Dashboard utama
     * (bawaan Filament: tetap diam di halaman Profil, karena induknya
     * mengembalikan null di sini). Notifikasi "Tersimpan" tetap muncul.
     */
    protected function getRedirectUrl(): ?string
    {
        return Dashboard::getUrl();
    }

    /**
     * Alias kosong disimpan sebagai NULL (bukan string kosong) biar tidak
     * bentrok dengan unique index antar user yang sama-sama kosong.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['alias_user'] = filled($data['alias_user'] ?? null) ? trim($data['alias_user']) : null;

        return $data;
    }
}
