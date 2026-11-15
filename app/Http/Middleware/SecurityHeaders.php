<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Header keamanan dasar untuk SEMUA response (termasuk panel Filament) --
 * ditambahkan dari hasil kajian keamanan (Sept 2026): sebelumnya tidak ada
 * X-Frame-Options, X-Content-Type-Options, maupun Referrer-Policy sama
 * sekali, dikonfirmasi lewat `curl -I` ke server dev.
 *
 * Content-Security-Policy SENGAJA belum ditambahkan di sini -- Livewire
 * (dipakai Filament) butuh setup nonce/hash khusus dulu supaya inline
 * script-nya tidak ikut keblokir. Itu pekerjaan terpisah yang perlu
 * pengujian menyeluruh dulu di panel admin sebelum diaktifkan, jangan
 * sampai malah bikin panel rusak.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        // Header lawas, browser modern sudah tidak pakai fitur ini -- '0'
        // eksplisit mematikannya (nilai lama '1; mode=block' malah bisa
        // dieksploitasi jadi XSS lewat browser tua tertentu).
        $response->headers->set('X-XSS-Protection', '0');

        return $response;
    }
}
