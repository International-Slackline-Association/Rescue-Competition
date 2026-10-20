<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use SocialiteProviders\Manager\OAuth2\User as CognitoUser;
use Tests\TestCase;

class IsaLoginTest extends TestCase
{
    use RefreshDatabase;

    private function fakeCognito(string $username, string $email): void
    {
        $cognito = (new CognitoUser)
            ->setRaw(['sub' => 'sub-123', 'username' => $username, 'email' => $email, 'email_verified' => 'true'])
            ->map(['id' => 'sub-123', 'email' => $email])
            ->setAccessTokenResponseBody(['id_token' => 'id-token']);

        Socialite::shouldReceive('driver->user')->andReturn($cognito);

        Http::fake(['account-api.slacklineinternational.org/*' => Http::response([
            'name' => 'Lia',
            'surname' => 'Highline',
            'profilePictureUrl' => 'https://images.slacklineinternational.org/lia.jpg',
        ])]);
    }

    public function test_login_sends_absolute_callback_url_and_only_allowed_scopes(): void
    {
        config(['services.cognito.client_id' => 'client-id', 'services.cognito.client_secret' => 'secret']);

        $location = $this->get(route('login'))->assertRedirect()->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        $this->assertSame(url('/auth/callback'), $query['redirect_uri']);
        $this->assertSame('openid email', $query['scope']);
    }

    public function test_first_login_creates_user_from_isa_account(): void
    {
        $this->fakeCognito('lia-username', 'lia@example.org');

        $this->get('/auth/callback')->assertRedirect();

        $user = User::sole();
        $this->assertSame(User::isaIdFromUsername('lia-username'), $user->isa_id);
        $this->assertSame('Lia Highline', $user->name);
        $this->assertAuthenticatedAs($user);
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer id-token'));
    }

    public function test_login_claims_placeholder_created_by_isa_id(): void
    {
        $placeholder = User::factory()->placeholder()->create(['isa_id' => User::isaIdFromUsername('lia-username')]);
        $this->fakeCognito('lia-username', 'lia@example.org');

        $this->get('/auth/callback')->assertRedirect();

        $this->assertSame(1, User::count());
        $this->assertSame('sub-123', $placeholder->fresh()->cognito_sub);
    }

    public function test_isa_id_matches_isa_users_derivation(): void
    {
        // sha256("test") = 9f86d081...
        $this->assertSame('ISA_9F86D081', User::isaIdFromUsername('test'));
    }
}
