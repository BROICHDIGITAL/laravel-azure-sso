<?php

namespace Broichdigital\AzureSso\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;

class AzureSsoController extends Controller
{
    protected function provider()
    {
        return Socialite::driver('azure-sso')
            ->stateless()
            ->scopes(['openid', 'profile', 'email', 'offline_access'])
            ->with(['response_mode' => 'query']);
    }

    public function redirectToProvider()
    {
        \Log::debug('Azure SSO redirect', [
            'authority' => config('azure-sso.authority'),
            'redirect'  => config('azure-sso.redirect'),
        ]);

        return $this->provider()->redirect();
    }

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

        $azureId   = $azureUser->getId(); // "sub"
        $name      = $azureUser->getName() ?: ($azureUser->user['name'] ?? null);
        $email     = $azureUser->getEmail()
                      ?: ($azureUser->user['preferred_username'] ?? $azureUser->user['upn'] ?? null);
        $avatar    = $azureUser->getAvatar();
        $token     = $azureUser->token ?? null;
        $refresh   = $azureUser->refreshToken ?? null;
        $expiresIn = $azureUser->expiresIn   ?? null;

        // User-Model dynamisch aus der Config
        $userModelClass = config('azure-sso.user_model', 'App\\Models\\User');

        // Bestehenden Benutzer ermitteln (zuerst per azure_id, dann per email), sonst neuen bauen
        /** @var \Illuminate\Database\Eloquent\Model|\Illuminate\Contracts\Auth\Authenticatable $user */
        $user = $userModelClass::query()
            ->when($azureId, fn($q) => $q->where('azure_id', $azureId))
            ->when(!$azureId && $email, fn($q) => $q->orWhere('email', $email))
            ->first();

        if (! $user) {
            $user = new $userModelClass();
        }

        // KEIN Mass-Assignment: einzelne Properties setzen
        $user->azure_id  = $azureId;
        $user->name      = $name ?: ($user->name ?? $email ?? 'Azure User');
        if ($email) {
            $user->email = $email;
        }
        $user->avatar    = $avatar;
        $user->access_token  = $token;
        $user->refresh_token = $refresh;
        $user->access_token_expires_at = $expiresIn ? now()->addSeconds((int) $expiresIn) : null;

        DB::transaction(function () use ($user) {
            $user->save();
        });

        Auth::login($user, true);

        return redirect()->intended(config('azure-sso.post_login_redirect', '/'));
    }

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