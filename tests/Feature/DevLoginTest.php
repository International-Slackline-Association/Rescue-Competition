<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DevLoginTest extends TestCase
{
    use RefreshDatabase;

    private function runLocallyWithoutCognito(): void
    {
        $this->app['env'] = 'local';
        config(['services.cognito.client_id' => null]);
    }

    public function test_local_login_page_offers_the_seeded_accounts(): void
    {
        $this->runLocallyWithoutCognito();
        User::factory()->create(['name' => 'Test Member']);
        User::factory()->admin()->create(['name' => 'Test Admin']);

        $this->get(route('login'))->assertOk()->assertSeeInOrder(['Test Admin', 'Admin', 'Test Member']);
    }

    public function test_local_login_as_a_seeded_account(): void
    {
        $this->runLocallyWithoutCognito();
        $admin = User::factory()->admin()->create();

        // CSRF is only skipped automatically in the testing environment.
        $this->withoutMiddleware(PreventRequestForgery::class)
            ->post(route('dev.login', $admin))->assertRedirect(route('people.show', $admin));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_login_goes_to_isa_when_a_cognito_client_is_configured(): void
    {
        $this->app['env'] = 'local';
        config(['services.cognito.client_id' => 'client-id', 'services.cognito.client_secret' => 'secret']);

        $this->get(route('login'))->assertRedirectContains('auth.slacklineinternational.org');
        $this->withoutMiddleware(PreventRequestForgery::class)
            ->post(route('dev.login', User::factory()->admin()->create()))->assertNotFound();
        $this->assertGuest();
    }

    public function test_dev_login_is_disabled_outside_local(): void
    {
        $this->post(route('dev.login', User::factory()->admin()->create()))->assertNotFound();

        $this->assertGuest();
    }
}
