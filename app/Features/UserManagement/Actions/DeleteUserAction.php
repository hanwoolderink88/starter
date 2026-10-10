<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Actions;

use App\Features\Roles\Contracts\GuardsLastAdministrator;
use App\Features\Roles\Exceptions\LastAdministratorException;
use App\Features\UserManagement\Events\UserChanged;
use App\Features\UserManagement\Services\UserManagementService;
use App\Infrastructure\Activity\Data\ChangeContext;
use App\Infrastructure\Activity\Enums\ActivityEvent;
use App\Infrastructure\Activity\Services\ActivityRecorder;
use App\Infrastructure\Broadcasting\Data\ResourceChangedData;
use App\Infrastructure\Broadcasting\Enums\ResourceAction;
use App\Models\User;

class DeleteUserAction
{
    public function __construct(
        private readonly UserManagementService $userManagementService,
        private readonly ActivityRecorder $activityRecorder,
        private readonly GuardsLastAdministrator $lastAdministrator,
    ) {}

    /**
     * A soft delete: the user can no longer log in and can be restored until
     * `users:anonymize` anonymizes them (config/privacy.php). The entry
     * carries a snapshot of who they were; anonymizing scrubs it.
     *
     * @throws LastAdministratorException when the user is the last administrator
     */
    public function handle(User $user, ChangeContext $context): void
    {
        if ($this->lastAdministrator->isLastAdministrator($user)) {
            throw new LastAdministratorException;
        }

        $id = $user->id;
        $label = $user->name;

        $this->activityRecorder->record($context, ActivityEvent::Deleted, $user, ActivityRecorder::snapshot([
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->getRoleNames()->sort()->values()->all(),
        ]));

        $this->userManagementService->delete($user);

        UserChanged::dispatch(ResourceChangedData::fromContext($context, ResourceAction::Deleted, $id, $label));
    }
}
