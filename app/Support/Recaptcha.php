<?php

namespace App\Support;

/**
 * Helper terpusat buat fitur captcha (dipakai App\Filament\Pages\Auth\Login,
 * App\Http\Controllers\UserController, & view widget captcha-nya) -- SATU
 * sumber kebenaran buat deteksi "lagi diakses dari localhost atau tidak",
 * dipakai baik buat SEMBUNYIKAN widget-nya di view maupun LEWATIN
 * verifikasi ke Google di server, supaya dua-duanya konsisten.
 *
 * Tujuannya: pas development di localhost/XAMPP (jaringan yang sering
 * memblokir akses ke Google/recaptcha.net, atau site key captcha belum
 * di-whitelist buat domain lokal), captcha otomatis dilewati tanpa perlu
 * comment/uncomment kode manual -- begitu aplikasi diakses lewat domain
 * publik asli (sudah di-hosting), captcha OTOMATIS aktif lagi karena
 * host-nya bukan salah satu dari daftar di bawah.
 */
class Recaptcha
{
    /**
     * Host yang dianggap "lokal" -- dicek APA ADANYA (bukan pola/regex)
     * supaya gampang ditambah manual kalau ada nama host dev lain yang
     * dipakai tim (mis. IP LAN kantor buat testing dari HP).
     *
     * @var array<int, string>
     */
    public const LOCAL_HOSTS = [
        'localhost',
        '127.0.0.1',
        '::1',
    ];

    /**
     * Akhiran host yang juga dianggap lokal -- ".test" & ".localhost" umum
     * dipakai sebagai domain dev (Laravel Valet, Docker, dll).
     *
     * @var array<int, string>
     */
    public const LOCAL_HOST_SUFFIXES = [
        '.test',
        '.localhost',
    ];

    public static function isLocalHost(): bool
    {
        $host = request()->getHost();

        if (in_array($host, self::LOCAL_HOSTS, true)) {
            return true;
        }

        foreach (self::LOCAL_HOST_SUFFIXES as $suffix) {
            if (str_ends_with($host, $suffix)) {
                return true;
            }
        }

        return false;
    }
}
