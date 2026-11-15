<?php

namespace App\Http\Controllers;

use App\Enums\UserStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * Proses login dari halaman depan publik (resources/views/welcome.blade.php),
     * TERPISAH dari login panel Filament (App\Filament\Pages\Auth\Login) yang
     * tetap ada dan tidak diubah kalau user langsung buka /admin/login.
     *
     * Satu input "login" menerima EMAIL atau NIP — dideteksi dari
     * formatnya (mengandung "@" berarti email, selain itu dianggap NIP),
     * lalu Auth::attempt() dipanggil dengan kolom yang sesuai.
     */
    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $this->verifyRecaptcha($request);

        $field = str_contains($data['login'], '@') ? 'email' : 'nip';

        $credentials = [
            $field => $data['login'],
            'password' => $data['password'],
        ];

        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'login' => 'Email/NIP atau password salah.',
            ]);
        }

        $request->session()->regenerate();

        $user = Auth::user();

        // Kredensial cocok tapi akun belum diverifikasi/diblokir — tolak di
        // sini juga (bukan cuma di User::canAccessPanel()), biar user dapat
        // pesan yang jelas alih-alih nyasar ke halaman login Filament.
        if ($user->status !== UserStatus::Active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withInput($request->only('login'))->with(
                'login_error',
                $user->status === UserStatus::Banned
                    ? 'Akun Anda diblokir. Hubungi administrator.'
                    : 'Akun Anda belum diverifikasi. Hubungi administrator.',
            );
        }

        return redirect()->intended('/admin');
    }

    /**
     * Verifikasi token reCAPTCHA ke API Google -- dukung DUA tipe widget
     * sekaligus (lihat welcome.blade.php): reCAPTCHA v2 "saya bukan robot"
     * (NOCAPTCHA_SECRET) dan v3 skor tak terlihat (RECAPTCHA_SECRET_KEY).
     * Keduanya SENGAJA saling eksklusif di sisi widget (cuma satu yang
     * dirender, tergantung site key mana yang diisi di .env), tapi
     * endpoint verifikasi Google-nya sama persis buat dua-duanya, jadi
     * cukup satu method ini -- prioritas ke v2 kalau dua-duanya somehow
     * keisi.
     *
     * Cuma jalan kalau salah satu secret key sudah diisi di .env — kalau
     * kosong semua, verifikasi dilewati sepenuhnya. Ini SENGAJA, lihat
     * catatan di welcome.blade.php: di versi lama, form login pernah
     * terkunci total kalau script reCAPTCHA gagal dimuat di sisi client
     * (site key belum di-whitelist untuk domain, koneksi ke Google
     * diblokir, dll) — jangan sampai verifikasi server-side jadi cara lain
     * buat mengunci form yang sama.
     */
    protected function verifyRecaptcha(Request $request): void
    {
        // Diakses dari localhost/domain dev (lihat App\Support\Recaptcha)
        // -- lewati verifikasi TANPA perlu comment/uncomment kode manual.
        // Begitu aplikasi sudah di-hosting di domain publik asli, baris ini
        // otomatis tidak berlaku lagi dan verifikasi captcha aktif normal.
        if (\App\Support\Recaptcha::isLocalHost()) {
            return;
        }

        $secret = config('services.no_captcha.secret_key') ?: config('services.recaptcha.secret_key');

        if (! $secret) {
            return;
        }

        $token = $request->input('g-recaptcha-response');

        if (! $token) {
            // Token kosong berarti reCAPTCHA gagal di sisi CLIENT (lihat
            // catatan di welcome.blade.php) — penyebab paling umum: site
            // key salah tipe (v2 dipakai seolah v3), atau domain yang
            // diakses belum di-whitelist di Google reCAPTCHA Admin Console
            // untuk site key ini. Kalau testing di localhost: pastikan
            // buka lewat "http://localhost:...", BUKAN "http://127.0.0.1:...”
            // — Google cuma auto-izinkan "localhost", bukan 127.0.0.1.
            Log::warning('reCAPTCHA gagal: token dari client kosong (site key tidak valid / domain belum di-whitelist).', [
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
            ]);

            throw ValidationException::withMessages([
                'g-recaptcha-response' => 'Verifikasi reCAPTCHA gagal, silakan coba lagi.',
            ]);
        }

        // "recaptcha.net" (bukan "google.com") -- domain resmi alternatif
        // dari Google, dipakai supaya verifikasi tetap jalan di jaringan
        // yang memblokir google.com langsung (umum di jaringan kantor/pemda).
        try {
            $response = Http::asForm()->timeout(5)->post('https://www.recaptcha.net/recaptcha/api/siteverify', [
                'secret' => $secret,
                'response' => $token,
                'remoteip' => $request->ip(),
            ]);
        } catch (\Illuminate\Http\Client\ConnectionException $exception) {
            // Server tidak bisa sama sekali menghubungi Google/recaptcha.net
            // -- BUKAN salah usernya, jangan sampai dia terkunci total gara-
            // gara infrastruktur captcha bermasalah. Lolos-kan (fail-open),
            // sama seperti saat secret key kosong di atas.
            Log::warning('reCAPTCHA: server gagal menghubungi endpoint verifikasi, verifikasi dilewati.', [
                'error' => $exception->getMessage(),
            ]);

            return;
        }

        if (! $response->ok() || ! ($response->json('success') ?? false)) {
            // Token ada tapi ditolak Google — errornya di sini biasanya
            // pasti ('error-codes' dari Google, mis. "invalid-input-secret"
            // kalau secret key salah/beda pasangan sama site key).
            Log::warning('reCAPTCHA gagal diverifikasi ke Google.', [
                'http_status' => $response->status(),
                'response' => $response->json(),
            ]);

            throw ValidationException::withMessages([
                'g-recaptcha-response' => 'Verifikasi reCAPTCHA gagal, silakan coba lagi.',
            ]);
        }
    }
}
