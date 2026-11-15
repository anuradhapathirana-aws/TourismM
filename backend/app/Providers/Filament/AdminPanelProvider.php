<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Support\AdminUi;
use App\Http\Middleware\NoIndex;
use App\Http\Middleware\SecurityHeaders;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Private admin panel. Served ONLY at /{ADMIN_PATH} (config/admin.php), never linked from the website.
 * Login is rate-limited by Filament (5 attempts / minute). No registration or password reset routes.
 */
final class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path(config('admin.path'))
            ->login()
            ->brandName('Lean Global Admin')
            ->colors([
                // Brand gold from the LG logo (#c8a36b)
                'primary' => Color::hex('#c8a36b'),
                'gray' => Color::Stone,
            ])
            // SPA mode: page changes swap content via wire:navigate instead of a full reload.
            ->spa()
            ->darkMode(true)
            ->defaultThemeMode(ThemeMode::Dark)
            // Compact layout: full-width content, narrow sidebar that collapses to icons.
            ->maxContentWidth(MaxWidth::Full)
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('15rem')
            ->collapsedSidebarWidth('4.25rem')
            ->renderHook(PanelsRenderHook::HEAD_END, fn (): string => self::compactStylesheet())
            // Instant form validation in the browser (the server still validates everything on submit).
            ->renderHook(PanelsRenderHook::BODY_END, fn (): string => '<script src="'.self::asset('js/admin-validation.js').'" data-navigate-once defer></script>')
            ->navigationGroups([
                NavigationGroup::make('Tours'),
                NavigationGroup::make('Locations'),
                NavigationGroup::make('Settings'),
                NavigationGroup::make('Access'),
            ])
            ->databaseNotifications(false)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                SecurityHeaders::class,
                NoIndex::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    public function boot(): void
    {
        // Registered on every request (incl. Livewire updates) so the ->span() / ->tip() macros always exist.
        AdminUi::configure();
    }

    /** public/css/admin.css: smaller headings, tighter spacing, shorter table rows (see CLAUDE.md → Admin UI). */
    private static function compactStylesheet(): string
    {
        return '<link rel="stylesheet" href="'.self::asset('css/admin.css').'">';
    }

    /** Escaped URL of a file in public/, versioned by its modified time so browsers refetch after a change. */
    private static function asset(string $path): string
    {
        $file = public_path($path);

        return e(asset($path).'?v='.(is_file($file) ? filemtime($file) : 0));
    }
}
