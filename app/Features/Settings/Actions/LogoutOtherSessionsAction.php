<?php

declare(strict_types=1);

namespace App\Features\Settings\Actions;

use App\Features\Settings\Services\SessionService;
use App\Infrastructure\Activity\Data\ChangeContext;
use App\Infrastructure\Activity\Enums\ActivityEvent;
use App\Infrastructure\Activity\Services\ActivityRecorder;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Log the user out everywhere but here. Laravel rehashes the password, which
 * makes `AuthenticateSession` end every other session and invalidates the
 * "remember me" cookies on other devices; the session rows go too, so the
 * list is right at once.
 */
class LogoutOtherSessionsAction
{
    public function __construct(
        private readonly SessionService $sessionService,
        private readonly ActivityRecorder $activityRecorder,
    ) {}

    /**
     * @param  string  $password  the user's current password, already validated
     */
    public function handle(User $user, string $password, string $currentSessionId, ChangeContext $context): int
    {
        $guard = Auth::guard('web');

        $guard->logoutOtherDevices($password);

        $removed = $this->sessionService->deleteOthers($user, $currentSessionId);

        $this->activityRecorder->record($context, ActivityEvent::OtherSessionsLoggedOut, $user, ['sessions' => $removed]);

        return $removed;
    }
}
