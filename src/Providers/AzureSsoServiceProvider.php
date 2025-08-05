<?php

namespace broichdigital\AzureSso\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Facades\Socialite;

class AzureSsoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Nur Config mergen – kein SocialiteContract hier
        $this->mergeConfigFrom(
            // Eine Ebene hoch (Providers → src), dann in Config
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

        // 2) Routes laden
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');

        // 3) Middleware-Alias registrieren
        $this->app->make(\Illuminate\Routing\Router::class)
              ->aliasMiddleware('azure.tenant', \broichdigital\AzureSso\Middleware\ResolveAzureTenant::class);

        // MIgrations laden
        $this->loadMigrationsFrom(__DIR__ . '/../../src/Database/migrations');

        // 4) Socialite-Provider registrieren
        \Laravel\Socialite\Facades\Socialite::extend('azure-sso', function ($app) {
            $cfg = config('azure-sso');
            return \Laravel\Socialite\Facades\Socialite::buildProvider(
                \SocialiteProviders\Microsoft\Provider::class,
                [
                    'client_id'     => $cfg['client_id'],
                    'client_secret' => $cfg['client_secret'],
                    'redirect'      => $cfg['redirect'],
                    'tenant'        => $cfg['tenant_id'],
                ]
            );
        });
    }
}
