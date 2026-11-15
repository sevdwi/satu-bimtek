<?php

namespace App\Providers;

use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Tautan "Lacak Dus Arsip" (halaman publik TANPA LOGIN, lihat
        // routes/web.php & DusArsipLacakController) ditempel di BAWAH form
        // login panel Filament (admin/login) lewat render hook resmi
        // Filament -- SENGAJA render hook, bukan nambah komponen ke
        // App\Filament\Pages\Auth\Login::form(), karena tautan ini bukan
        // bagian dari form login itu sendiri (tidak perlu ikut logic
        // validasi/captcha-nya). Sudah ada juga di navbar welcome.blade.php
        // (halaman depan publik '/' & '/login'), ini pasangannya buat yang
        // langsung buka admin/login.
        FilamentView::registerRenderHook(
            PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
            fn (): string => view('filament.auth.lacak-dus-link')->render(),
        );
    }
}
