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
     * Redirect zum Microsoft-Login (Tenant-spezifisch & response_mode=query).
     */
    public function redirectToProvider(Request $request)
    {
        $tenant = config('azure-sso.tenant');

        return Socialite::driver('azure-sso')
            ->stateless()
            ->tenant($tenant)              // ← wichtig!
            ->with(['response_mode' => 'query'])
            ->redirect();
    }

    /**
     * Callback-Handler: User-Daten abholen, DB-Sync und Login.
     */
    public function handleProviderCallback(Request $request)
    {
        $tenant = config('azure-sso.tenant');

        $azureUser = Socialite::driver('azure-sso')
            ->stateless()
            ->tenant($tenant)              // ← auch hier
            ->user();

        // Synchronisieren oder neu anlegen:
        $user = User::updateOrCreate(
            ['azure_id' => $azureUser->getId()],
            [
                'name'         => $azureUser->getName(),
                'email'        => $azureUser->getEmail(),
                'avatar'       => $azureUser->getAvatar(),
                'access_token' => $azureUser->token,
            ]
        );

        // Einloggen (remember-Flag optional):
        Auth::login($user, true);

        // Weiterleitung nach Login:
        $redirect = config('azure-sso.post_login_redirect', '/');
        return redirect()->intended($redirect);
    }

    /**
     * Logout: lokale Session beenden und optional Azure-Logout.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($url = config('azure-sso.logout_url')) {
            return redirect()->away($url);
        }

        return redirect(config('azure-sso.post_logout_redirect', '/'));
    }
}
