<?php

declare(strict_types=1);

namespace App\Features\Roles\Actions;

use App\Features\Roles\Services\RoleService;
use App\Infrastructure\Activity\Data\ChangeContext;
use App\Infrastructure\Activity\Enums\ActivityEvent;
use App\Infrastructure\Activity\Services\ActivityRecorder;

/**
 * Create the default roles that do not exist yet: the protected Administrator
 * (through SyncPermissionsAction) and a User role without permissions, as a
 * starting point. Existing roles, active or not, are left untouched.
 * Idempotent, and only real changes are logged.
 */
class SeedDefaultRolesAction
{
    public const string USER_NAME = 'User';

    public const string USER_DESCRIPTION = 'A regular user of the application.';

    public function __construct(
        private readonly SyncPermissionsAction $syncPermissionsAction,
        private readonly RoleService $roleService,
        private readonly ActivityRecorder $activityRecorder,
    ) {}

    public function handle(ChangeContext $context): void
    {
        $this->syncPermissionsAction->handle($context);

        $user = $this->roleService->firstOrCreateByName(self::USER_NAME, self::USER_DESCRIPTION);

        if ($user->wasRecentlyCreated) {
            $this->activityRecorder->record($context, ActivityEvent::Created, $user, ActivityRecorder::diff([], $this->roleService->activityAttributes($user)));
        }
    }
}
