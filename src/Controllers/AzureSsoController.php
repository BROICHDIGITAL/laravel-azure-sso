<?php

namespace Broichdigital\AzureSso\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class AzureSsoController extends Controller
{
    /**
     * Leitet den Benutzer zum Microsoft-Login um
     * (tenant-spezifisch, response_mode=query).
     */
    public function redirectToProvider()
    {
        // Provider-Instanz aufbauen
        $provider = Socialite::driver('azure-tenant')
            ->stateless()
            ->with(['response_mode' => 'query']);

        // Debug-Log: Welche Klasse & welche URL?
        \Log::debug('SSO Provider', [
            'class' => get_class($provider),
            'url'   => $provider->redirect()->getTargetUrl(),
        ]);

        // Redirect ausführen
        return $provider->redirect();
    }

    /**
     * Callback-Handler: User-Daten abholen, DB-Sync und Login.
     */
    public function handleProviderCallback(Request $request)
    {
        // Azure-Fehler auffangen
        if ($request->has('error')) {
            return redirect()->route('azure-sso.login')
                ->with('error', $request->input('error_description', 'Azure SSO error'));
        }

        // Tenant-spezifischen Treiber verwenden
        $azureUser = Socialite::driver('azure-tenant')
            ->stateless()
            ->user();

        // Nutzer synchronisieren oder anlegen
        $user = User::updateOrCreate(
            ['azure_id' => $azureUser->getId()],
            [
                'name'         => $azureUser->getName(),
                'email'        => $azureUser->getEmail(),
                'avatar'       => $azureUser->getAvatar(),
                'access_token' => $azureUser->token,
            ]
        );

        // Einloggen (inkl. remember-Cookie)
        Auth::login($user, true);

        // Weiterleitung nach Login
        return redirect()->intended(config('azure-sso.post_login_redirect', '/'));
    }

    /**
     * Logout: lokale Session beenden und optional Azure-Logout.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $url = config('azure-sso.logout_url');

        return $url
            ? redirect()->away($url)                               // Azure Single Logout
            : redirect(config('azure-sso.post_logout_redirect', '/'));
    }
}
