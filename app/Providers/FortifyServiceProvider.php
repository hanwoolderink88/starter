<?php

declare(strict_types=1);

namespace App\Providers;

use App\Features\Auth\Actions\CreateNewUser;
use App\Features\Auth\Actions\ResetUserPassword;
use App\Features\Auth\Data\ForgotPasswordPageData;
use App\Features\Auth\Data\LoginPageData;
use App\Features\Auth\Data\ResetPasswordPageData;
use App\Features\Auth\Data\VerifyEmailPageData;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn (Request $request) => Inertia::render('auth/login', new LoginPageData(
            canResetPassword: Features::enabled(Features::resetPasswords()),
            canRegister: Features::enabled(Features::registration()),
            status: $request->session()->get('status'),
        )));

        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('auth/reset-password', new ResetPasswordPageData(
            email: $request->email,
            token: $request->route('token'),
        )));

        Fortify::requestPasswordResetLinkView(fn (Request $request) => Inertia::render('auth/forgot-password', new ForgotPasswordPageData(
            status: $request->session()->get('status'),
        )));

        Fortify::verifyEmailView(fn (Request $request) => Inertia::render('auth/verify-email', new VerifyEmailPageData(
            status: $request->session()->get('status'),
        )));

        Fortify::registerView(fn () => Inertia::render('auth/register'));

        Fortify::twoFactorChallengeView(fn () => Inertia::render('auth/two-factor-challenge'));

        Fortify::confirmPasswordView(fn () => Inertia::render('auth/confirm-password'));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', fn (Request $request) => Limit::perMinute(5)->by($request->session()->get('login.id')));

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}
