<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Data;

use App\Features\Roles\Data\AssignableRoleData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class UserFormPageData extends Data
{
    /**
     * @param  list<AssignableRoleData>  $roles  every role, inactive ones included
     * @param  list<int>  $roleIds  the roles of the edited user, inactive assignments included; [] when creating
     * @param  bool  $canAssignRoles  whether the viewer may change roles (`manage roles`); without it the form posts no `role_ids`
     * @param  bool  $isLastAdministrator  whether the edited user is the last administrator, who can neither lose the role nor be deleted
     */
    public function __construct(
        public ?UserManagementData $user,
        public array $roles,
        public array $roleIds,
        public bool $canAssignRoles,
        public bool $isLastAdministrator = false,
    ) {}
}
