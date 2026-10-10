<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Actions;

use App\Features\UserManagement\Services\UserManagementService;
use App\Infrastructure\Activity\Data\ChangeContext;
use App\Infrastructure\Activity\Enums\ActivityEvent;
use App\Infrastructure\Activity\Services\ActivityRecorder;
use App\Models\User;

class AcceptInvitationAction
{
    public function __construct(
        private readonly UserManagementService $userManagementService,
        private readonly ActivityRecorder $activityRecorder,
    ) {}

    /**
     * The invited user acts as a guest here, so the context names them as the
     * actor explicitly. Logging them in is the controller's job.
     */
    public function handle(User $user, string $password, ChangeContext $context): void
    {
        $this->userManagementService->acceptInvitation($user, $password);

        $this->activityRecorder->record($context, ActivityEvent::InvitationAccepted, $user);
    }
}
