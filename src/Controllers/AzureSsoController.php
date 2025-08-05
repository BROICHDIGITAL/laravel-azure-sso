<?php

namespace broichdigital\AzureSso\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class AzureSsoController extends Controller
{
    /**
     * Redirect zum Microsoft-Login.
     */
    public function redirectToProvider(Request $request)
    {
        return Socialite::driver('azure-sso')
                        ->stateless()
                        ->redirect();
    }

    /**
     * Callback-Handler: User-Daten abholen, DB-Sync und Login.
     */
    public function handleProviderCallback(Request $request)
    {
        $azureUser = Socialite::driver('azure-sso')
                              ->stateless()
                              ->user();

        // Synchronisieren oder anlegen:
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
        // Laravel-Session beenden
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Falls in Config gesetzt, redirect zu Azure Single Logout
        if ($url = config('azure-sso.logout_url')) {
            return redirect()->away($url);
        }

        // Andernfalls lokal zurück
        $redirect = config('azure-sso.post_logout_redirect', '/');
        return redirect($redirect);
    }
}
