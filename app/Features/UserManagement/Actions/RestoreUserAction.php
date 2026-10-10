<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Actions;

use App\Features\UserManagement\Events\UserChanged;
use App\Features\UserManagement\Services\UserManagementService;
use App\Infrastructure\Activity\Data\ChangeContext;
use App\Infrastructure\Activity\Enums\ActivityEvent;
use App\Infrastructure\Activity\Services\ActivityRecorder;
use App\Infrastructure\Broadcasting\Data\ResourceChangedData;
use App\Infrastructure\Broadcasting\Enums\ResourceAction;
use App\Models\User;

/**
 * Bring back a deleted user who is not anonymized yet, with their roles. Their
 * sessions and API tokens stay revoked: they log in again.
 */
class RestoreUserAction
{
    public function __construct(
        private readonly UserManagementService $userManagementService,
        private readonly ActivityRecorder $activityRecorder,
    ) {}

    public function handle(User $user, ChangeContext $context): User
    {
        $user = $this->userManagementService->restore($user);

        $this->activityRecorder->record($context, ActivityEvent::Restored, $user);

        UserChanged::dispatch(ResourceChangedData::fromContext($context, ResourceAction::Created, $user->id, $user->name));

        return $user;
    }
}
