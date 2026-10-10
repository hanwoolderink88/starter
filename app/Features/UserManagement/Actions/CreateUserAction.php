<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Actions;

use App\Features\Roles\Actions\SyncUserRolesAction;
use App\Features\UserManagement\Events\UserChanged;
use App\Features\UserManagement\Notifications\InvitationNotification;
use App\Features\UserManagement\Services\UserManagementService;
use App\Infrastructure\Activity\Data\ChangeContext;
use App\Infrastructure\Activity\Enums\ActivityEvent;
use App\Infrastructure\Activity\Services\ActivityRecorder;
use App\Infrastructure\Broadcasting\Data\ResourceChangedData;
use App\Infrastructure\Broadcasting\Enums\ResourceAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateUserAction
{
    public function __construct(
        private readonly UserManagementService $userManagementService,
        private readonly SyncUserRolesAction $syncUserRolesAction,
        private readonly ActivityRecorder $activityRecorder,
    ) {}

    /**
     * The new user is logged as `created`, the roles they get as their own
     * `roles_changed` entry.
     *
     * @param  list<int>  $roleIds  validated ids of active roles
     */
    public function handle(string $name, string $email, array $roleIds, ChangeContext $context): User
    {
        $user = DB::transaction(function () use ($name, $email, $roleIds, $context): User {
            $user = $this->userManagementService->store($name, $email);

            $this->activityRecorder->record($context, ActivityEvent::Created, $user, ActivityRecorder::diff([], [
                'name' => $user->name,
                'email' => $user->email,
            ]));

            if ($roleIds !== []) {
                $this->syncUserRolesAction->handle($user, $roleIds, $context);
            }

            return $user;
        });

        $user->notify(new InvitationNotification($user));

        UserChanged::dispatch(ResourceChangedData::fromContext($context, ResourceAction::Created, $user->id, $user->name));

        return $user;
    }
}
