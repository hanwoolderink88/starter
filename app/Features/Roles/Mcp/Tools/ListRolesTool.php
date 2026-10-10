<?php

declare(strict_types=1);

namespace App\Features\Roles\Mcp\Tools;

use App\Features\Roles\Enums\Permission;
use App\Features\Roles\Models\Role;
use App\Features\Roles\Services\RoleService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Override;

/**
 * The roles that can be given to users, for an agent that creates or updates
 * one. Whoever may manage roles also sees what each role may do. Changing
 * roles themselves has no tool: it is sensitive configuration that rarely
 * changes, and a mistake there affects everyone's rights.
 */
#[Name('list_roles')]
#[Description('List the roles that can be given to users (by name, for user_create and user_update). With the manage roles permission, also each role\'s permissions and how many users have it.')]
#[IsReadOnly]
class ListRolesTool extends Tool
{
    public function __construct(
        private readonly RoleService $roleService,
    ) {}

    public function shouldRegister(Request $request): bool
    {
        return $request->user()?->canAny([
            Permission::ManageRoles->value,
            Permission::CreateUsers->value,
            Permission::UpdateUsers->value,
        ]) ?? false;
    }

    /**
     * @return array<string, Type>
     */
    #[Override]
    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): Response
    {
        $canManage = $request->user()?->can(Permission::ManageRoles->value) ?? false;

        return Response::json([
            'roles' => $this->roleService->all()->map(fn (Role $role): array => [
                'name' => $role->name,
                'description' => $role->description,
                'is_active' => $role->is_active,
                'is_protected' => $role->is_protected,
                ...($canManage ? [
                    'permissions' => $this->roleService->permissionNames($role),
                    'user_count' => (int) $role->getAttribute('users_count'),
                ] : []),
            ])->all(),
        ]);
    }
}
