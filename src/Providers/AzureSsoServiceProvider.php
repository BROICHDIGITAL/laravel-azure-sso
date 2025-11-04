<?php

namespace Broichdigital\AzureSso\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use Laravel\Socialite\Facades\Socialite;
use Broichdigital\AzureSso\Providers\TenantAwareMicrosoftProvider;

class AzureSsoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Package-Config mergen
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/azure-sso.php',
            'azure-sso'
        );
    }

    public function boot(): void
    {
        /* --------------------------------------------------------------
         | 1) Config publizieren (optional fürs Projekt)
         |--------------------------------------------------------------*/
        $this->publishes([
            __DIR__ . '/../Config/azure-sso.php' => config_path('azure-sso.php'),
        ], 'config');

        /* --------------------------------------------------------------
         | 1.5) Whitelist-Validierung: XOR-Logik prüfen
         |--------------------------------------------------------------*/
        $allowedDomains = config('azure-sso.allowed_domains', []);
        $allowedTenants = config('azure-sso.allowed_tenants', []);
        
        if (!empty($allowedDomains) && !empty($allowedTenants)) {
            \Log::warning('Azure SSO Config: Beide Whitelists (allowed_domains und allowed_tenants) sind gesetzt. Nur eine darf konfiguriert sein.');
        }

        /* --------------------------------------------------------------
         | 2) Routen laden
         |--------------------------------------------------------------*/
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');

        /* --------------------------------------------------------------
         | 3) Middleware-Alias registrieren
         |--------------------------------------------------------------*/
        $this->app->make(Router::class)->aliasMiddleware(
            'azure.tenant',
            \Broichdigital\AzureSso\Middleware\ResolveAzureTenant::class
        );

        /* --------------------------------------------------------------
         | 4) Migrationen laden (falls vorhanden)
         |--------------------------------------------------------------*/
        $this->loadMigrationsFrom(__DIR__ . '/../Database/migrations');

        /* --------------------------------------------------------------
         | 5) Eigenen Socialite-Treiber registrieren  (azure-tenant)
         |--------------------------------------------------------------*/
        $cfg = config('azure-sso');

        Socialite::extend('azure-tenant', function () use ($cfg) {
            return Socialite::buildProvider(
                TenantAwareMicrosoftProvider::class,
                [
                    'client_id'     => $cfg['client_id'],
                    'client_secret' => $cfg['client_secret'],
                    'redirect'      => $cfg['redirect'],
                    'tenant'        => $cfg['tenant'] ?? $cfg['tenant_id'] ?? 'common',
                    // optional: 'guzzle' => $cfg['guzzle'] ?? [],
                ]
            );
        });
    }


}
