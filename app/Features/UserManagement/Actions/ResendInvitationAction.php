<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Actions;

use App\Features\UserManagement\Notifications\InvitationNotification;
use App\Infrastructure\Activity\Data\ChangeContext;
use App\Infrastructure\Activity\Enums\ActivityEvent;
use App\Infrastructure\Activity\Services\ActivityRecorder;
use App\Models\User;

class ResendInvitationAction
{
    public function __construct(
        private readonly ActivityRecorder $activityRecorder,
    ) {}

    public function handle(User $user, ChangeContext $context): void
    {
        $user->notify(new InvitationNotification($user));

        $this->activityRecorder->record($context, ActivityEvent::InvitationResent, $user);
    }
}
