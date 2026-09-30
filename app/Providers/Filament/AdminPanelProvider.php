<?php

namespace App\Providers\Filament;

use App\Filament\Pages\BrandingSettingsPage;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\CommercialSettingsPage;
use App\Filament\Pages\OperationsSettingsPage;
use App\Filament\Pages\SalesReportPage;
use App\Filament\Pages\EmailNotificationSettingsPage;
use App\Filament\Pages\InterPixSettingsPage;
use App\Filament\Pages\SeoSettingsPage;
use App\Models\SystemSetting;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Throwable;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $brandName = (string) $this->setting('general', 'system_name', 'A5 Rifas');
        $primaryColor = (string) $this->setting('branding', 'primary_color', '#22c55e');
        $logo = $this->setting('branding', 'logo');
        $favicon = $this->setting('branding', 'favicon');
        $logoSize = max(28, min(120, (int) $this->setting('branding', 'logo_size', 44)));
        $adminTwoFactorEnabled = filter_var(
            $this->setting('security', 'admin_2fa_enabled', '1'),
            FILTER_VALIDATE_BOOL,
        );

        $pages = [
            Dashboard::class,
            BrandingSettingsPage::class,
        ];

        foreach ([
            CommercialSettingsPage::class,
            OperationsSettingsPage::class,
            SalesReportPage::class,
            SeoSettingsPage::class,
            EmailNotificationSettingsPage::class,
            InterPixSettingsPage::class,
        ] as $optionalPage) {
            if (class_exists($optionalPage)) {
                $pages[] = $optionalPage;
            }
        }

        $panel = $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->passwordReset()
            ->profile(isSimple: false)
            ->brandName($brandName)
            ->colors(['primary' => $primaryColor])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->pages($pages)
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
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
            ->authMiddleware([Authenticate::class]);

        if ($adminTwoFactorEnabled) {
            $panel->multiFactorAuthentication([
                AppAuthentication::make()
                    ->brandName($brandName)
                    ->recoverable()
                    ->recoveryCodeCount(10)
                    ->codeWindow(4),
            ], isRequired: true);
        }

        if (is_string($logo) && $logo !== '') {
            $panel->brandLogo(Storage::disk('public')->url($logo));

            if (method_exists($panel, 'brandLogoHeight')) {
                $panel->brandLogoHeight($logoSize.'px');
            }
        }

        if (is_string($favicon) && $favicon !== '' && method_exists($panel, 'favicon')) {
            $panel->favicon(Storage::disk('public')->url($favicon));
        }

        return $panel;
    }

    private function setting(string $group, string $key, mixed $default = null): mixed
    {
        try {
            if (! Schema::hasTable('system_settings')) {
                return $default;
            }

            return SystemSetting::value($group, $key, $default);
        } catch (Throwable) {
            return $default;
        }
    }
}
