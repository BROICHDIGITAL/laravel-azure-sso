<?php

namespace Broichdigital\AzureSso\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class ResolveAzureTenant
{
    public function handle(Request $request, Closure $next)
    {
        // Beispiel: Tenant-Profil per Query-Parameter wählen
        $tenantKey = $request->query('tenant');

        $profiles = config('azure-sso.tenants', []);

        $cfg = ($tenantKey && isset($profiles[$tenantKey]))
            ? $profiles[$tenantKey]          // Multi-Tenant-Profil
            : config('azure-sso');           // Default-Profil

        /* ----------------------------------------------------------
         | Laufende Socialite-Konfiguration überschreiben
         |----------------------------------------------------------*/
        Config::set('services.microsoft.client_id',     $cfg['client_id']);
        Config::set('services.microsoft.client_secret', $cfg['client_secret']);
        Config::set('services.microsoft.redirect',      $cfg['redirect']);
        Config::set('services.microsoft.tenant',        $cfg['tenant']);   // ← Korrektur

        return $next($request);
    }
}
