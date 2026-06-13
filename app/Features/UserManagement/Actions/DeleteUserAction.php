<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Actions;

use App\Broadcasting\Data\ResourceChangedData;
use App\Broadcasting\Enums\ResourceAction;
use App\Features\UserManagement\Events\UserChanged;
use App\Features\UserManagement\Services\UserManagementService;
use App\Models\User;

class DeleteUserAction
{
    public function __construct(
        private readonly UserManagementService $userManagementService,
    ) {}

    public function handle(User $user, User $actor, ?string $origin = null): void
    {
        $id = $user->id;
        $label = $user->name;

        $this->userManagementService->delete($user);

        UserChanged::dispatch(new ResourceChangedData(
            action: ResourceAction::Deleted,
            id: $id,
            label: $label,
            actorId: $actor->id,
            actorName: $actor->name,
            origin: $origin,
        ));
    }
}
