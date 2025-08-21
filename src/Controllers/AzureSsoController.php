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
        $avatar  = $azureUser->getAvatar();

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
        if ($avatar) {
            $user->avatar = $avatar;
        }

        $user->save();

        // Einloggen (mit remember)
        Auth::login($user, true);

        return redirect()->intended(config('azure-sso.post_login_redirect', '/'));
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