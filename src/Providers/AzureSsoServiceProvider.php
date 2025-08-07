<?php

namespace Broichdigital\AzureSso\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Routing\Router;

class AzureSsoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Config mergen
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/azure-sso.php',
            'azure-sso'
        );
    }

    public function boot(): void
    {
        // 1) Config publizieren
        $this->publishes([
            __DIR__ . '/../Config/azure-sso.php' => config_path('azure-sso.php'),
        ], 'config');

        // 2) Routen laden
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');

        // 3) Middleware-Alias registrieren
        $this->app->make(Router::class)
            ->aliasMiddleware(
                'azure.tenant',
                \Broichdigital\AzureSso\Middleware\ResolveAzureTenant::class
            );

        // 4) Migrationen laden (falls vorhanden)
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // 5) Socialite-Provider registrieren
        Socialite::extend('azure-sso', function ($app) {
            $cfg    = config('azure-sso');
            $tenant = $cfg['tenant'] ?? $cfg['tenant_id'] ?? 'common';

            return Socialite::buildProvider(
                \SocialiteProviders\Microsoft\Provider::class,
                [
                    'client_id'     => $cfg['client_id'],
                    'client_secret' => $cfg['client_secret'],
                    'redirect'      => $cfg['redirect'],
                    'tenant'        => $tenant,
                    // optional: 'guzzle' => $cfg['guzzle'] ?? [],
                ]
            );
        });
    }
}
