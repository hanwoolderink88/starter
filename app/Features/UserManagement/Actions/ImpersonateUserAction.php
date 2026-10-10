<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Actions;

use App\Infrastructure\Activity\Data\ChangeContext;
use App\Infrastructure\Activity\Enums\ActivityEvent;
use App\Infrastructure\Activity\Services\ActivityRecorder;
use App\Models\User;

class ImpersonateUserAction
{
    public function __construct(
        private readonly ActivityRecorder $activityRecorder,
    ) {}

    /**
     * The context's actor is the user who starts impersonating; the subject is
     * the one impersonated. The session itself is switched by the controller.
     */
    public function handle(User $target, ChangeContext $context): void
    {
        $this->activityRecorder->record($context, ActivityEvent::ImpersonationStarted, $target);
    }
}
