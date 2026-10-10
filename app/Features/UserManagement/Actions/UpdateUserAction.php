<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Actions;

use App\Features\Roles\Actions\SyncUserRolesAction;
use App\Features\Roles\Exceptions\LastAdministratorException;
use App\Features\UserManagement\Events\UserChanged;
use App\Features\UserManagement\Services\UserManagementService;
use App\Infrastructure\Activity\Data\ChangeContext;
use App\Infrastructure\Activity\Enums\ActivityEvent;
use App\Infrastructure\Activity\Exceptions\StaleVersionException;
use App\Infrastructure\Activity\Services\ActivityRecorder;
use App\Infrastructure\Broadcasting\Data\ResourceChangedData;
use App\Infrastructure\Broadcasting\Enums\ResourceAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateUserAction
{
    public function __construct(
        private readonly UserManagementService $userManagementService,
        private readonly SyncUserRolesAction $syncUserRolesAction,
        private readonly ActivityRecorder $activityRecorder,
    ) {}

    /**
     * Name and email are logged as `updated`, a changed role set as its own
     * `roles_changed` entry. One transaction, so a refused role change leaves
     * the name untouched too.
     *
     * @param  int  $version  the version the change is based on
     * @param  list<int>|null  $roleIds  validated ids of active roles; null leaves the roles unchanged
     *
     * @throws StaleVersionException when someone saved the user in the meantime
     * @throws LastAdministratorException when the last administrator would lose the role
     */
    public function handle(User $user, int $version, string $name, string $email, ?array $roleIds, ChangeContext $context): User
    {
        $user = DB::transaction(function () use ($user, $version, $name, $email, $roleIds, $context): User {
            $before = ['name' => $user->name, 'email' => $user->email];

            $user = $this->userManagementService->update($user, $version, $name, $email);

            $changes = ActivityRecorder::diff($before, ['name' => $user->name, 'email' => $user->email]);

            if ($changes !== []) {
                $this->activityRecorder->record($context, ActivityEvent::Updated, $user, $changes);
            }

            if ($roleIds !== null) {
                $this->syncUserRolesAction->handle($user, $roleIds, $context);
            }

            return $user;
        });

        UserChanged::dispatch(ResourceChangedData::fromContext($context, ResourceAction::Updated, $user->id, $user->name));

        return $user;
    }
}
