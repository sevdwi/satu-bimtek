<?php

namespace App\Providers\Filament;

use App\Models\MenuItem;
use App\Models\MenuPermission;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use App\Filament\Pages\Auth\EditProfile;
use App\Filament\Pages\Auth\Login;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\Support\HtmlString;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandLogo(asset('images/new-satu.png'))
            ->brandLogoHeight('3rem')
            ->font('Poppins')
            ->favicon(asset('new-satu.ico'))
            ->login(Login::class)
            // Halaman Profil -- tiap user atur Alias User-nya sendiri (awalan
            // Nomor Sementara arsip), lihat App\Filament\Pages\Auth\EditProfile.
            ->profile(EditProfile::class, isSimple: false)
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_BEFORE,
                fn (): string => view('filament.partials.dinas-bidang')->render(),
            )
            ->colors([
                'primary' => Color::Blue,
            ])
            ->navigationGroups([
                // Grup menu "Daftar Usul Musnah" & "Daftar Usul Permanen"
                // (lihat UsulMusnahResource/UsulPermanenResource). Sengaja
                // TANPA ->icon() di sini -- Filament nggak izinkan group
                // punya ikon SEKALIGUS item di dalamnya juga punya ikon
                // masing-masing (document-minus/document-check), dan ikon
                // per-item lebih informatif buat bedain Musnah vs Permanen.
                NavigationGroup::make('Arsip M/P'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            // Menu "link kustom" (dibuat Superadmin lewat Manajemen Menu ->
            // "Tambah Menu (Link)", lihat App\Filament\Resources\
            // MenuItemResource\Pages\CreateMenuItem) -- baris2 ini TIDAK
            // punya Resource/Page sendiri, jadi harus didaftarkan manual di
            // sini sebagai NavigationItem biasa (beda dari Resource/Page di
            // atas yang navigasinya otomatis lewat discoverResources()/
            // discoverPages()).
            ->navigationItems(static::customMenuNavigationItems())
            // Widget bawaan "Welcome"/AccountWidget & branding "filament"/
            // FilamentInfoWidget sengaja DIHAPUS dari Dashboard (permintaan
            // user) -- widget custom (stats & chart Arsip) tetap otomatis
            // kepasang lewat discoverWidgets() di atas.
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    /**
     * Bangun daftar NavigationItem buat setiap baris menu_items yang
     * is_custom = true (lihat App\Models\MenuItem::allCached(), sumber
     * datanya sudah di-cache di sana). Array-nya sendiri dibangun SEKALI
     * tiap panel di-boot per request (batasan navigationItems() bawaan
     * Filament -- terima array biasa, bukan Closure), tapi visible() tiap
     * item TETAP Closure supaya selalu ngecek role user yang login SAAT
     * request itu, bukan role siapa pun yang kebetulan login waktu array
     * ini dibangun.
     *
     * @return array<NavigationItem>
     */
    protected static function customMenuNavigationItems(): array
    {
        return MenuItem::allCached()
            ->filter(fn (array $item): bool => $item['is_custom'])
            ->map(fn (array $item, string $moduleKey): NavigationItem => NavigationItem::make($item['label'])
                ->icon($item['icon'] ?: 'heroicon-o-link')
                ->url($item['url'], shouldOpenInNewTab: $item['open_in_new_tab'])
                ->sort($item['sort_order'])
                ->visible(fn (): bool => MenuPermission::isAllowed($moduleKey, auth()->user()?->role)))
            ->values()
            ->all();
    }
}
