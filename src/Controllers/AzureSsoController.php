<?php

namespace Broichdigital\AzureSso\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class AzureSsoController extends Controller
{
    /**
     * Socialite-Provider mit minimalen Scopes für reines SSO.
     */
    protected function provider()
    {
        return Socialite::driver('azure-tenant')
            ->stateless()
            ->scopes(['openid', 'profile', 'email'])
            ->with(['response_mode' => 'query']);
    }

    /**
     * Startet den Azure-Login-Flow.
     */
    public function redirectToProvider()
    {
        \Log::debug('Azure SSO redirect', [
            'tenant'   => config('azure-sso.tenant') ?? config('azure-sso.tenant_id'),
            'redirect' => config('azure-sso.redirect'),
        ]);

        return $this->provider()->redirect();
    }

    /**
     * Callback: Azure-User abholen, lokalen User syncen, einloggen.
     */
    public function handleProviderCallback(Request $request)
    {
        if ($request->has('error')) {
            \Log::warning('Azure SSO error on callback', $request->only('error', 'error_description'));
            return redirect()->route('azure-sso.login')
                ->with('error', $request->input('error_description', 'Azure SSO error'));
        }

        try {
            $azureUser = $this->provider()->user();
        } catch (\Throwable $e) {
            \Log::error('Azure SSO token exchange failed', [
                'message' => $e->getMessage(),
                'class'   => get_class($e),
            ]);
            return redirect()->route('azure-sso.login')
                ->with('error', 'Azure SSO konnte nicht abgeschlossen werden (Token-Exchange).');
        }

        // Minimal nötige Profilinfos
        $azureId = $azureUser->getId(); // i.d.R. "sub"
        $name    = $azureUser->getName() ?: ($azureUser->user['name'] ?? null);
        $email   = $azureUser->getEmail()
                   ?: ($azureUser->user['preferred_username'] ?? $azureUser->user['upn'] ?? null);
        // $avatar  = $azureUser->getAvatar();

        // Whitelist-Prüfung (entweder Domain- oder Tenant-ID-Filterung)
        $authorizationCheck = $this->checkAuthorization($azureUser, $email);
        if ($authorizationCheck !== null) {
            return $authorizationCheck;
        }

        // User-Model aus Config (Standard: App\Models\User)
        $userModelClass = config('azure-sso.user_model', 'App\\Models\\User');

        // Bestehenden Benutzer finden (zuerst per azure_id, fallback per email)
        /** @var \Illuminate\Database\Eloquent\Model|\Illuminate\Contracts\Auth\Authenticatable $user */
        $user = $userModelClass::query()
            ->when($azureId, fn($q) => $q->where('azure_id', $azureId))
            ->when(!$azureId && $email, fn($q) => $q->orWhere('email', $email))
            ->first();

        if (! $user) {
            $user = new $userModelClass();
        }

        // KEIN Mass-Assignment: gezielt setzen (keine fillable-Anpassung nötig)
        $user->azure_id = $azureId;
        if ($name || !$user->name) {
            $user->name = $name ?: ($email ?? 'Azure User');
        }
        if ($email) {
            $user->email = $email;
        }
        // if ($avatar) {
        //    $user->avatar = $avatar;
        // }

        $user->save();

        // Einloggen (mit remember)
        Auth::login($user, true);

        return redirect()->intended(config('azure-sso.post_login_redirect', '/'));
    }

    /**
     * Prüft, ob der Benutzer über die Whitelist autorisiert ist.
     * 
     * @param mixed $azureUser Der Azure-Benutzer von Socialite
     * @param string|null $email Die Email-Adresse des Benutzers
     * @return \Illuminate\Http\RedirectResponse|null Redirect bei nicht autorisiertem Zugriff, null bei Erfolg
     */
    protected function checkAuthorization($azureUser, ?string $email)
    {
        $allowedDomains = config('azure-sso.allowed_domains', []);
        $allowedTenants = config('azure-sso.allowed_tenants', []);
        $unauthorizedMessage = config('azure-sso.unauthorized_message', 'Ihre Organisation ist nicht autorisiert.');

        // DEBUG: Ausführliches Logging
        \Log::info('Azure SSO Authorization Check', [
            'email' => $email,
            'allowed_domains' => $allowedDomains,
            'allowed_domains_count' => count($allowedDomains),
            'allowed_domains_is_empty' => empty($allowedDomains),
            'allowed_tenants' => $allowedTenants,
            'allowed_tenants_count' => count($allowedTenants),
            'allowed_tenants_is_empty' => empty($allowedTenants),
            'env_raw' => env('AZURE_SSO_ALLOWED_DOMAINS'),
            'config_azure_sso_allowed_domains' => config('azure-sso.allowed_domains'),
        ]);

        // Wenn beide leer sind, keine Prüfung (alle erlaubt)
        if (empty($allowedDomains) && empty($allowedTenants)) {
            \Log::warning('Azure SSO: Keine Whitelist konfiguriert - alle erlaubt');
            return null;
        }

        // XOR-Logik: Nur eine der beiden Whitelists darf gesetzt sein
        if (!empty($allowedDomains) && !empty($allowedTenants)) {
            \Log::error('Azure SSO: Beide Whitelists (allowed_domains und allowed_tenants) sind gesetzt. Nur eine darf konfiguriert sein.');
            return redirect()->route('azure-sso.login')
                ->with('error', 'Konfigurationsfehler: Es darf nur eine Whitelist-Option gesetzt sein.');
        }

        // Email-Domain-Prüfung
        if (!empty($allowedDomains)) {
            if (!$email) {
                \Log::warning('Azure SSO: Email fehlt für Domain-Prüfung');
                return redirect()->route('azure-sso.login')
                    ->with('error', $unauthorizedMessage);
            }

            $emailDomain = substr(strrchr($email, "@"), 1);
            
            // DEBUG: Domain-Extraktion loggen
            \Log::info('Azure SSO Domain Check', [
                'email' => $email,
                'extracted_domain' => $emailDomain,
                'allowed_domains' => $allowedDomains,
                'in_array_result' => in_array($emailDomain, $allowedDomains),
                'strict_compare' => in_array($emailDomain, $allowedDomains, true),
            ]);
            
            if (!in_array($emailDomain, $allowedDomains)) {
                \Log::warning('Azure SSO: Unauthorized email domain', [
                    'email' => $email,
                    'domain' => $emailDomain,
                    'allowed_domains' => $allowedDomains,
                ]);
                return redirect()->route('azure-sso.login')
                    ->with('error', $unauthorizedMessage);
            }
            
            \Log::info('Azure SSO: Domain authorized', [
                'email' => $email,
                'domain' => $emailDomain,
            ]);
        }

        // Tenant-ID-Prüfung
        if (!empty($allowedTenants)) {
            $tenantId = $azureUser->user['tid'] ?? $azureUser->user['tenant_id'] ?? null;
            
            if (!$tenantId) {
                \Log::warning('Azure SSO: Tenant-ID fehlt im Token');
                return redirect()->route('azure-sso.login')
                    ->with('error', $unauthorizedMessage);
            }

            if (!in_array($tenantId, $allowedTenants)) {
                \Log::warning('Azure SSO: Unauthorized tenant', [
                    'tenant_id' => $tenantId,
                    'allowed_tenants' => $allowedTenants,
                    'email' => $email,
                ]);
                return redirect()->route('azure-sso.login')
                    ->with('error', $unauthorizedMessage);
            }
        }

        return null; // Autorisiert
    }

    /**
     * Logout (lokal) + optional Azure-Logout (falls konfiguriert).
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $url = config('azure-sso.logout_url');

        return $url
            ? redirect()->away($url)
            : redirect(config('azure-sso.post_logout_redirect', '/'));
    }
}