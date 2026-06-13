<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Actions;

use App\Broadcasting\Data\ResourceChangedData;
use App\Broadcasting\Enums\ResourceAction;
use App\Features\UserManagement\Enums\Role;
use App\Features\UserManagement\Events\UserChanged;
use App\Features\UserManagement\Services\UserManagementService;
use App\Models\User;

class UpdateUserAction
{
    public function __construct(
        private readonly UserManagementService $userManagementService,
    ) {}

    public function handle(User $user, string $name, string $email, Role $role, User $actor, ?string $origin = null): User
    {
        $user = $this->userManagementService->update($user, $name, $email, $role);

        UserChanged::dispatch(new ResourceChangedData(
            action: ResourceAction::Updated,
            id: $user->id,
            label: $user->name,
            actorId: $actor->id,
            actorName: $actor->name,
            origin: $origin,
        ));

        return $user;
    }
}
