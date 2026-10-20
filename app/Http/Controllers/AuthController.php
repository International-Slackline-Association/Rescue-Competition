<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;
use SocialiteProviders\Manager\OAuth2\User as CognitoUser;

class AuthController extends Controller
{
    public function redirect(): RedirectResponse|View
    {
        // Local development without an ISA Cognito client: pick a seeded account instead.
        if (self::devLoginEnabled()) {
            return view('auth.dev-login', [
                // Seeded test accounts (DatabaseSeeder) first, then everyone else.
                'users' => User::orderByDesc('is_admin')
                    ->orderByRaw('email = ? desc', ['member@example.org'])
                    ->orderBy('name')
                    ->get(),
            ]);
        }

        return Socialite::driver('cognito')->setScopes(['openid', 'email'])->redirect();
    }

    public function callback(): RedirectResponse
    {
        /** @var CognitoUser $cognito */
        $cognito = Socialite::driver('cognito')->user();

        $user = $this->findOrClaim($cognito);
        $details = $this->isaDetails($cognito->accessTokenResponseBody['id_token'] ?? null);

        $user->fill([
            'cognito_sub' => $cognito->getId(),
            'isa_id' => User::isaIdFromUsername($cognito->getRaw()['username']),
            'email' => $cognito->getEmail(),
            'name' => trim(($details['name'] ?? '').' '.($details['surname'] ?? '')) ?: ($user->name ?? strstr($cognito->getEmail(), '@', true)),
            'avatar_url' => $details['profilePictureUrl'] ?? $user->avatar_url,
        ])->save();

        Auth::login($user, remember: true);

        return redirect()->intended(route('people.show', $user));
    }

    /**
     * Local only, without an ISA client: log in as any user.
     */
    public function devLogin(Request $request, User $user): RedirectResponse
    {
        abort_unless(self::devLoginEnabled(), 404);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('people.show', $user));
    }

    /**
     * Only on a local machine without an ISA client: a server with APP_ENV=local by mistake still has Cognito set up.
     */
    private static function devLoginEnabled(): bool
    {
        return app()->isLocal() && ! config('services.cognito.client_id');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return config('services.cognito.client_id')
            ? redirect()->away(Socialite::driver('cognito')->logoutCognitoUser())
            : redirect()->route('home');
    }

    /**
     * Existing account, or a placeholder someone created when adding this person to a rescue.
     */
    private function findOrClaim(CognitoUser $cognito): User
    {
        $isaId = User::isaIdFromUsername($cognito->getRaw()['username']);
        $emailVerified = filter_var($cognito->getRaw()['email_verified'] ?? false, FILTER_VALIDATE_BOOL);

        return User::where('cognito_sub', $cognito->getId())->first()
            ?? User::whereNull('cognito_sub')->where('isa_id', $isaId)->first()
            ?? ($emailVerified ? User::whereNull('cognito_sub')->where('email', $cognito->getEmail())->first() : null)
            ?? new User;
    }

    /**
     * Name and profile picture live in the ISA account API, not in Cognito.
     */
    private function isaDetails(?string $idToken): array
    {
        if (! $idToken) {
            return [];
        }

        $response = Http::withToken($idToken)
            ->timeout(5)
            ->get(config('services.isa.api_url').'/basic/userDetails');

        return $response->successful() ? $response->json() : [];
    }
}
