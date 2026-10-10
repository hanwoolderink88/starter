<?php

declare(strict_types=1);

namespace App\Providers;

use App\Features\Activity\Policies\ActivityPolicy;
use App\Features\Auth\Listeners\RecordAuthenticationActivity;
use App\Features\Roles\Models\Role;
use App\Features\Roles\Policies\RolePolicy;
use App\Features\UserManagement\Policies\UserPolicy;
use App\Http\Inertia\SailSsrGateway;
use App\Infrastructure\Activity\Models\Activity;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Inertia\Ssr\Gateway;
use Laravel\Passport\Passport;
use Override;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[Override]
    public function register(): void
    {
        $this->app->bind(Gateway::class, SailSsrGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configurePolicies();
        $this->configurePassport();
        $this->configureMorphMap();
        $this->configureActivityLog();
        $this->configureRateLimiting();
    }

    /**
     * An MCP client is a program and can call tools in a tight loop; this caps
     * what one account can do per minute, so a runaway agent cannot flood
     * the application or the activity log.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('mcp', fn (Request $request): Limit => Limit::perMinute((int) config('services.mcp.per_minute', 120))
            ->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip())));
    }

    /**
     * Polymorphic columns, such as an activity's subject, store a short alias
     * instead of a class name. Enforced, so logging a model without an alias
     * fails loudly.
     */
    protected function configureMorphMap(): void
    {
        Relation::enforceMorphMap([
            'user' => User::class,
            'role' => Role::class,
        ]);
    }

    /**
     * Authentication flows are owned by Fortify and the framework, so they are
     * recorded by listening to their events.
     */
    protected function configureActivityLog(): void
    {
        Event::subscribe(RecordAuthenticationActivity::class);
    }

    /**
     * Use the MCP-provided consent screen for OAuth authorization requests.
     */
    protected function configurePassport(): void
    {
        Passport::authorizationView(fn (array $parameters) => response()->view('mcp.authorize', $parameters));
    }

    protected function configurePolicies(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Activity::class, ActivityPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }
}
