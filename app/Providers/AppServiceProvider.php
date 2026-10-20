<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Cognito\CognitoExtendSocialite;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());

        // The live site is HTTPS only; hosts that terminate TLS in a proxy would otherwise get http:// asset links.
        URL::forceHttps($this->app->isProduction());

        Event::listen(SocialiteWasCalled::class, [CognitoExtendSocialite::class, 'handle']);

        // Admin-only areas outside rescues: the /mcp server and its user tools.
        Gate::define('admin', fn (User $user) => $user->is_admin);
    }
}
