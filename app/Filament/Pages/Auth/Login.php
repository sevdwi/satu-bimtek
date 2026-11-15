<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    /**
     * Ganti field 'email' bawaan Filament dengan 'login' yang menerima
     * email ATAU NIP. Nama field-nya sengaja diganti (bukan tetap 'email')
     * supaya validasi ->email() bawaan tidak nolak input NIP.
     */
    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('login')
            ->label('Email atau NIP')
            ->required()
            ->autocomplete()
            ->autofocus();
    }

    /**
     * Tambahkan widget reCAPTCHA v2 di bawah field bawaan (login, password,
     * remember) -- lihat resources/views/filament/auth/recaptcha.blade.php
     * soal kenapa widgetnya lewat Filament\Schemas\Components\View (bukan
     * field form biasa): submit form login Filament lewat Livewire (AJAX),
     * jadi token captcha-nya dikirim manual ke $wire->set('data.captcha')
     * dari callback JS widget, dibaca lagi di verifyCaptcha() di bawah.
     */
    public function form(Schema $schema): Schema
    {
        $schema = parent::form($schema);

        return $schema->components([
            ...$schema->getComponents(),
            View::make('filament.auth.recaptcha'),
        ]);
    }

    /**
     * Kalau inputnya berformat email, cocokkan ke kolom `email`.
     * Kalau bukan, anggap itu NIP dan cocokkan ke kolom `nip`.
     * Laravel's EloquentUserProvider otomatis WHERE ke kolom apa pun yang
     * ada di array kredensial ini (selain 'password'), jadi tidak perlu
     * custom UserProvider.
     */
    protected function getCredentialsFromFormData(array $data): array
    {
        $login = $data['login'];

        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'nip';

        return [
            $field => $login,
            'password' => $data['password'],
        ];
    }

    protected function throwFailureValidationException(): never
    {
        throw \Illuminate\Validation\ValidationException::withMessages([
            'data.login' => __('filament-panels::auth/pages/login.messages.failed'),
        ]);
    }

    /**
     * Verifikasi captcha DULU sebelum proses autentikasi bawaan Filament
     * jalan (rate limit, cek kredensial, dst.) -- biar percobaan brute
     * force tanpa nyelesaikan captcha ditolak paling awal, bukan malah
     * ikut kena hitungan rate limit punya Filament.
     */
    public function authenticate(): ?LoginResponse
    {
        $this->verifyCaptcha();

        return parent::authenticate();
    }

    /**
     * Sama persis pola & pesannya dengan App\Http\Controllers\UserController
     * ::verifyRecaptcha() (buat form login publik) -- SENGAJA dilewati
     * total kalau NOCAPTCHA_SECRET belum diisi di .env, supaya halaman
     * login admin tidak ikut terkunci gara-gara captcha belum dikonfigurasi
     * atau situs Google lagi tidak bisa diakses dari server.
     */
    protected function verifyCaptcha(): void
    {
        // Diakses dari localhost/domain dev (lihat App\Support\Recaptcha)
        // -- lewati verifikasi TANPA perlu comment/uncomment kode manual.
        // Begitu aplikasi sudah di-hosting di domain publik asli, baris ini
        // otomatis tidak berlaku lagi dan verifikasi captcha aktif normal.
        if (\App\Support\Recaptcha::isLocalHost()) {
            return;
        }

        $secret = config('services.no_captcha.secret_key');

        if (! $secret) {
            return;
        }

        $token = $this->data['captcha'] ?? null;

        if (! $token) {
            throw ValidationException::withMessages([
                'data.captcha' => 'Silakan centang captcha "Saya bukan robot" terlebih dahulu.',
            ]);
        }

        // "recaptcha.net" (bukan "google.com") -- domain resmi alternatif
        // dari Google, dipakai supaya verifikasi tetap jalan di jaringan
        // yang memblokir google.com langsung (lihat catatan yang sama di
        // resources/views/filament/auth/recaptcha.blade.php).
        try {
            $response = Http::asForm()->timeout(5)->post('https://www.recaptcha.net/recaptcha/api/siteverify', [
                'secret' => $secret,
                'response' => $token,
                'remoteip' => request()->ip(),
            ]);
        } catch (\Illuminate\Http\Client\ConnectionException $exception) {
            // Server tidak bisa sama sekali menghubungi Google/recaptcha.net
            // (mis. firewall jaringan kantor) -- BUKAN salah usernya, jangan
            // sampai dia terkunci total gara-gara infrastruktur captcha yang
            // bermasalah. Lolos-kan verifikasi (fail-open), sama seperti
            // saat NOCAPTCHA_SECRET kosong di atas.
            Log::warning('reCAPTCHA login admin: server gagal menghubungi endpoint verifikasi, verifikasi dilewati.', [
                'error' => $exception->getMessage(),
            ]);

            $this->data['captcha'] = null;

            return;
        }

        if (! $response->ok() || ! ($response->json('success') ?? false)) {
            Log::warning('reCAPTCHA login admin gagal diverifikasi ke Google.', [
                'http_status' => $response->status(),
                'response' => $response->json(),
            ]);

            // Reset token supaya widget captcha ke-render ulang (Google
            // menolak token yang sama dipakai dua kali) -- tanpa ini user
            // bisa terjebak nge-submit ulang pakai token yang sudah invalid.
            $this->data['captcha'] = null;

            throw ValidationException::withMessages([
                'data.captcha' => 'Verifikasi captcha gagal, silakan coba lagi.',
            ]);
        }

        // Token reCAPTCHA cuma boleh dipakai sekali -- bersihkan supaya
        // percobaan authenticate() berikutnya (mis. lolos captcha tapi
        // password salah) wajib centang captcha baru lagi.
        $this->data['captcha'] = null;
    }
}
