<?php

declare(strict_types=1);

namespace App\Providers;

use App\Features\UserManagement\Enums\Role;
use App\Features\UserManagement\Policies\UserPolicy;
use App\Http\Inertia\SailSsrGateway;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
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
        $this->configureSuperAdmin();
        $this->configurePolicies();
        $this->configurePassport();
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
    }

    /**
     * Grant all permissions to the super-admin role via Gate::before.
     */
    protected function configureSuperAdmin(): void
    {
        Gate::before(fn ($user, $ability) => $user->hasRole(Role::SuperAdmin) ? true : null);
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
