<?php

namespace Broichdigital\AzureSso\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Routing\Router;

class AzureSsoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Nur Config mergen
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/azure-sso.php',
            'azure-sso'
        );
    }

    public function boot(): void
    {
        // 1) Config publishen
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

        // 4) Migrationen laden
        $this->loadMigrationsFrom(__DIR__ . '/../../src/Database/migrations');

        // 5) Socialite-Provider registrieren
        Socialite::extend('azure-sso', function ($app) {
            $cfg       = config('azure-sso');
            $authority = $cfg['authority'];

            return Socialite::buildProvider(
                \SocialiteProviders\Microsoft\Provider::class,
                [
                    'client_id'     => $cfg['client_id'],
                    'client_secret' => $cfg['client_secret'],
                    'redirect'      => $cfg['redirect'],
                    'tenant'        => $authority,
                ]
            );
        });
    }
}