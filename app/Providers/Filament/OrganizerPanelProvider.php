<?php

namespace App\Providers\Filament;

use App\Filament\Organizer\Pages\Dashboard;
use App\Filament\Organizer\Resources\Raffles\RaffleResource;
use App\Models\Organizer;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

class OrganizerPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('organizer')
            ->path('organizador')
            ->brandName('RifaOnline | Organizador')
            ->login()
            ->profile(isSimple: false)
            ->tenant(Organizer::class, slugAttribute: 'slug')
            // Explicit allowlist: only a read-only, tenant-scoped campaign list.
            // Customers, orders, payments and admin resources remain unavailable.
            ->resources([
                RaffleResource::class,
            ])
            ->pages([
                Dashboard::class,
            ])
            ->widgets([])
            ->multiFactorAuthentication([
                AppAuthentication::make()
                    ->brandName('RifaOnline')
                    ->recoverable()
                    ->recoveryCodeCount(10)
                    ->codeWindow(4),
            ], isRequired: true)
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                ValidateCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
