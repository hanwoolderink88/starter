<?php

declare(strict_types=1);

namespace App\Features\Auth\Listeners;

use App\Infrastructure\Activity\Data\ChangeContext;
use App\Infrastructure\Activity\Enums\ActivityEvent;
use App\Infrastructure\Activity\Services\ActivityRecorder;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Http\Request;
use Laravel\Fortify\Events\RecoveryCodeReplaced;
use Laravel\Fortify\Events\RecoveryCodesGenerated;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationEnabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationEvent;
use Laravel\Fortify\Events\TwoFactorAuthenticationFailed;
use Laravel\Fortify\Fortify;

/**
 * Records the authentication flows that Fortify and the framework own, so
 * there is no Action of ours to log them from: logging in and out, failed and
 * throttled attempts, and two-factor changes. Only the session guard counts;
 * an API token (MCP) does not log in.
 *
 * Switching users for an impersonation fires none of these events on purpose;
 * it is logged as starting or stopping the impersonation instead.
 */
class RecordAuthenticationActivity
{
    public function __construct(
        private readonly ActivityRecorder $activityRecorder,
        private readonly Request $request,
    ) {}

    /**
     * @return array<class-string, string>
     */
    public function subscribe(): array
    {
        return [
            Login::class => 'handleLogin',
            Logout::class => 'handleLogout',
            Failed::class => 'handleFailed',
            Lockout::class => 'handleLockout',
            TwoFactorAuthenticationEnabled::class => 'handleTwoFactorChange',
            TwoFactorAuthenticationConfirmed::class => 'handleTwoFactorChange',
            TwoFactorAuthenticationDisabled::class => 'handleTwoFactorChange',
            RecoveryCodesGenerated::class => 'handleTwoFactorChange',
            TwoFactorAuthenticationFailed::class => 'handleTwoFactorFailed',
            RecoveryCodeReplaced::class => 'handleRecoveryCodeUsed',
        ];
    }

    public function handleLogin(Login $event): void
    {
        if ($event->guard !== 'web' || ! $event->user instanceof User) {
            return;
        }

        $this->activityRecorder->record(
            ChangeContext::fromRequest($this->request, actor: $event->user),
            ActivityEvent::LoggedIn,
            $event->user,
            ['remember' => $event->remember],
        );
    }

    public function handleLogout(Logout $event): void
    {
        if ($event->guard !== 'web' || ! $event->user instanceof User) {
            return;
        }

        $this->activityRecorder->record(
            ChangeContext::fromRequest($this->request, actor: $event->user),
            ActivityEvent::LoggedOut,
            $event->user,
        );
    }

    /**
     * A wrong password names the account it was tried on; an unknown email
     * address has no subject. Nobody is authenticated yet, so the actor is a
     * guest. Only the email address is kept, never the password.
     */
    public function handleFailed(Failed $event): void
    {
        if ($event->guard !== 'web') {
            return;
        }

        $user = $event->user instanceof User ? $event->user : null;

        $this->activityRecorder->record(
            ChangeContext::fromRequest($this->request),
            ActivityEvent::LoginFailed,
            $user,
            ['email' => $this->attemptedEmail($event->credentials[Fortify::username()] ?? null)],
        );
    }

    public function handleLockout(Lockout $event): void
    {
        $email = $this->attemptedEmail($event->request->input(Fortify::username()));

        $this->activityRecorder->record(
            ChangeContext::fromRequest($event->request),
            ActivityEvent::LockedOut,
            $email === null ? null : User::query()->where('email', $email)->first(),
            ['email' => $email],
        );
    }

    public function handleTwoFactorChange(TwoFactorAuthenticationEvent|RecoveryCodesGenerated $event): void
    {
        $this->activityRecorder->record(
            ChangeContext::fromRequest($this->request),
            match (true) {
                $event instanceof TwoFactorAuthenticationEnabled => ActivityEvent::TwoFactorEnabled,
                $event instanceof TwoFactorAuthenticationConfirmed => ActivityEvent::TwoFactorConfirmed,
                $event instanceof TwoFactorAuthenticationDisabled => ActivityEvent::TwoFactorDisabled,
                default => ActivityEvent::RecoveryCodesGenerated,
            },
            $event->user,
        );
    }

    /**
     * The password was right but the code was not: the attempt names the
     * account, while nobody is authenticated yet.
     */
    public function handleTwoFactorFailed(TwoFactorAuthenticationFailed $event): void
    {
        $this->activityRecorder->record(
            ChangeContext::fromRequest($this->request),
            ActivityEvent::TwoFactorFailed,
            $event->user,
        );
    }

    /**
     * Logging in with a recovery code uses it up. The code itself is never
     * logged.
     */
    public function handleRecoveryCodeUsed(RecoveryCodeReplaced $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $this->activityRecorder->record(
            ChangeContext::fromRequest($this->request, actor: $event->user),
            ActivityEvent::RecoveryCodeUsed,
            $event->user,
        );
    }

    private function attemptedEmail(mixed $email): ?string
    {
        return is_string($email) && $email !== '' ? mb_substr(mb_strtolower($email), 0, 255) : null;
    }
}
