<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Infrastructure\Activity\Data\ChangeContext;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Keeps an impersonation to what it is for: seeing and doing what the user
 * sees and does. The account's own credentials and security settings stay the
 * user's, whoever impersonates them.
 */
class RestrictImpersonation
{
    /**
     * Routes that change the impersonated user's profile, language, password,
     * sessions or two-factor authentication, or show its secrets or where
     * the user is logged in.
     *
     * @var list<string>
     */
    public const array BLOCKED_ROUTES = [
        'profile.update',
        'profile.destroy',
        'locale.update',
        'sessions.index',
        'sessions.destroy',
        'user-password.update',
        'user-profile-information.update',
        'two-factor.show',
        'two-factor.enable',
        'two-factor.confirm',
        'two-factor.disable',
        'two-factor.qr-code',
        'two-factor.secret-key',
        'two-factor.recovery-codes',
        'two-factor.regenerate-recovery-codes',
    ];

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->impersonatorWasDeleted($request)) {
            Auth::guard('web')->forgetUser();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login');
        }

        if ($request->routeIs(...self::BLOCKED_ROUTES) && ChangeContext::impersonatorFor($request) instanceof User) {
            throw new AccessDeniedHttpException;
        }

        return $next($request);
    }

    /**
     * Whether this session impersonates its user on behalf of someone who no
     * longer exists. Without the impersonator nothing done in the session
     * could be traced back to them, so the session ends instead. No logout():
     * that would cycle the impersonated user's remember token.
     */
    private function impersonatorWasDeleted(Request $request): bool
    {
        /** @var array{impersonator_id?: int, impersonated_id?: int}|null $impersonation */
        $impersonation = $request->session()->get(ChangeContext::IMPERSONATION_SESSION_KEY);

        return is_array($impersonation)
            && ($impersonation['impersonated_id'] ?? null) === $request->user()?->getAuthIdentifier()
            && ! ChangeContext::impersonatorFor($request) instanceof User;
    }
}
