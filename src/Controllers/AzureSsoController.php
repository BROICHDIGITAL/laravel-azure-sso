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
     * Redirect zum Microsoft-Login (tenant-spezifisch & response_mode=query).
     */
    public function redirectToProvider()
    {
        return Socialite::driver('azure-tenant')      // neuer Treiber-Key
            ->stateless()
            ->with(['response_mode' => 'query'])
            ->redirect();
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

        $azureUser = Socialite::driver('azure-tenant')  // neuer Treiber-Key
            ->stateless()
            ->user();

        // Synchronisieren oder neu anlegen
        $user = User::updateOrCreate(
            ['azure_id' => $azureUser->getId()],
            [
                'name'         => $azureUser->getName(),
                'email'        => $azureUser->getEmail(),
                'avatar'       => $azureUser->getAvatar(),
                'access_token' => $azureUser->token,
            ]
        );

        // Einloggen (optional remember)
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

        return $url = config('azure-sso.logout_url')
            ? redirect()->away($url)
            : redirect(config('azure-sso.post_logout_redirect', '/'));
    }
}
