<?php

namespace App\Providers;

use App\Models\User;
use App\Observers\UserObserver;
use App\Services\Seo\SeoMetadataService;
use App\Services\Support\AuditService;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // O pagamento atual usa Pix estático e não depende de gateway externo.
    }

    public function boot(): void
    {
        if (config('app.force_https')) {
            URL::forceScheme('https');
        }

        View::composer('app', function ($view): void {
            $view->with('seo', app(SeoMetadataService::class)->forRequest(request()));
        });

        User::observe(UserObserver::class);

        Event::listen(Login::class, function (Login $event): void {
            if ($event->user instanceof User) {
                $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
                AuditService::record('auth.login', $event->user, actorId: $event->user->getKey());
            }
        });

        Event::listen(Logout::class, function (Logout $event): void {
            if ($event->user instanceof User) {
                AuditService::record('auth.logout', $event->user, actorId: $event->user->getKey());
            }
        });
    }
}
