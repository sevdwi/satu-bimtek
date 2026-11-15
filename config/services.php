<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // reCAPTCHA v3 buat form login publik (lihat UserController::login dan
    // resources/views/welcome.blade.php). site_key dipakai di sisi client
    // buat generate token, secret_key dipakai server buat verifikasi ke API
    // Google. Kalau site_key kosong, script reCAPTCHA gagal load dan form
    // otomatis fallback submit tanpa token (lihat komentar JS di welcome.blade.php).
    'recaptcha' => [
        'site_key' => env('RECAPTCHA_SITE_KEY'),
        'secret_key' => env('RECAPTCHA_SECRET_KEY'),
    ],

    // reCAPTCHA v2 ("saya bukan robot") khusus buat halaman login panel
    // Filament (App\Filament\Pages\Auth\Login) -- SENGAJA dipisah dari
    // 'recaptcha' di atas (v3, buat form login publik di welcome.blade.php)
    // karena tipe widget-nya beda (checkbox interaktif vs skor tak
    // terlihat) dan makanya butuh site key/secret pasangan yang berbeda
    // juga di Google reCAPTCHA Admin Console. Nama env NOCAPTCHA_SITEKEY /
    // NOCAPTCHA_SECRET ngikutin konvensi yang sudah dipakai user di .env.
    'no_captcha' => [
        'site_key' => env('NOCAPTCHA_SITEKEY'),
        'secret_key' => env('NOCAPTCHA_SECRET'),
    ],

];
