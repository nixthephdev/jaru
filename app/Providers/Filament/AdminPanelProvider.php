<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\MenuStats;
use App\Filament\Widgets\RecentChanges;
use App\Models\Setting;
use App\Support\Photos;
use Filament\Actions\Action;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    /** Hand-tuned so toggles and links stay a deep olive (a generated palette comes out too bright). */
    private const OLIVE = [
        50 => '#f6f6ee', 100 => '#e9e8d6', 200 => '#d4d3b0', 300 => '#b9b985', 400 => '#949763',
        500 => '#767b45', 600 => '#5d6234', 700 => '#4b4f2a', 800 => '#3c3f23', 900 => '#31341e', 950 => '#1b1d10',
    ];

    /** Brick red, as on the website's "Sold out" tag (a generated palette comes out salmon). */
    private const BRICK = [
        50 => '#fbf1ee', 100 => '#f6e4dd', 200 => '#ecc5b9', 300 => '#dd9c88', 400 => '#c86b52',
        500 => '#b04a31', 600 => '#9a2f1c', 700 => '#7f2617', 800 => '#672015', 900 => '#541c13', 950 => '#2e0d08',
    ];

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->profile()
            ->brandName(fn () => Setting::get('cafe_name') ?: 'Jaru Café')
            ->brandLogo(fn () => Setting::get('logo') ? Photos::url(Setting::get('logo'), small: true) : null)
            ->brandLogoHeight('2.75rem')
            ->favicon(fn () => Setting::get('logo') ? Photos::url(Setting::get('logo'), small: true) : null)
            ->font('Manrope Variable', provider: LocalFontProvider::class)
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->colors([
                // The logo's olive (600), with warm stone greys to sit on ivory paper (main buttons are espresso ink, see theme.css).
                'primary' => self::OLIVE,
                'success' => self::OLIVE,
                'gray' => Color::Stone,
                'warning' => Color::hex('#a2671f'),
                'danger' => self::BRICK,
            ])
            // One look, matching the website.
            ->darkMode(false)
            ->renderHook(PanelsRenderHook::SIMPLE_LAYOUT_START, fn () => view('filament.auth-photo'))
            ->userMenuItems([
                Action::make('site')->label('View website')->icon('heroicon-o-arrow-top-right-on-square')->url(fn () => url('/'))->openUrlInNewTab(),
            ])
            ->navigationItems([
                NavigationItem::make('View website')->icon('heroicon-o-globe-alt')->url(fn () => url('/'), shouldOpenInNewTab: true)->sort(10),
            ])
            ->sidebarCollapsibleOnDesktop()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                MenuStats::class,
                RecentChanges::class,
            ])
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
}
