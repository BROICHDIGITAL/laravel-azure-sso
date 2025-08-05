<?php

namespace broichdigital\AzureSso\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class ResolveAzureTenant
{
    public function handle(Request $request, Closure $next)
    {
        // Beispiel: Tenant per Query-Parameter:
        $tenantKey = $request->query('tenant', null);

        // Lade alle Tenant-Profile aus config (multi-tenant-ready):
        $profiles = config('azure-sso.tenants', []);

        if ($tenantKey && isset($profiles[$tenantKey])) {
            $cfg = $profiles[$tenantKey];
        } else {
            // Fallback auf Single-Tenant
            $cfg = config('azure-sso');
        }

        // Runtime-Override der Socialite-Konfiguration:
        Config::set('services.microsoft.client_id',     $cfg['client_id']);
        Config::set('services.microsoft.client_secret', $cfg['client_secret']);
        Config::set('services.microsoft.redirect',      $cfg['redirect']);
        Config::set('services.microsoft.tenant',        $cfg['tenant_id']);

        return $next($request);
    }
}
