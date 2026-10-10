<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Mcp\Tools;

use App\Features\Roles\Contracts\ListsAssignableRoles;
use App\Features\Roles\Enums\Permission;
use App\Features\Roles\Exceptions\LastAdministratorException;
use App\Features\UserManagement\Actions\UpdateUserAction;
use App\Features\UserManagement\Data\UserManagementData;
use App\Features\UserManagement\Mcp\Concerns\ResolvesRoleNames;
use App\Infrastructure\Activity\Data\ChangeContext;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Override;

#[Name('user_update')]
#[Description('Change a user\'s name, email and, with the manage roles permission, roles. Pass the version from user_detail or search_user; if someone saved the user in the meantime the update is refused with the current version, so fetch the user again and retry.')]
class UpdateUserTool extends Tool
{
    use ResolvesRoleNames;

    public function __construct(
        private readonly UpdateUserAction $updateUserAction,
        private readonly ListsAssignableRoles $assignableRoles,
    ) {}

    public function shouldRegister(Request $request): bool
    {
        return $request->user()?->can(Permission::UpdateUsers->value) ?? false;
    }

    /**
     * @return array<string, Type>
     */
    #[Override]
    public function schema(JsonSchema $schema): array
    {
        return [
            'user_id' => $schema->integer()->description('The ID of the user.')->required(),
            'version' => $schema->integer()->description('The version of the user this change is based on.')->required(),
            'name' => $schema->string()->description('The full name.')->required(),
            'email' => $schema->string()->description('The email address. Must be unique.')->required(),
            'roles' => $schema->array()
                ->description('The names of the user\'s active roles after the change. Leave out to keep the roles as they are.')
                ->items($schema->string()->enum($this->activeRoleNames($this->assignableRoles))),
        ];
    }

    public function handle(Request $request): Response
    {
        $user = User::query()->find($request->get('user_id'));

        if (! $user instanceof User) {
            return Response::error('User not found.');
        }

        Gate::authorize('update', $user);

        $validated = $request->validate([
            'version' => ['required', 'integer', 'min:1'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->withoutTrashed()->ignore($user->id)],
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['distinct', Rule::in($this->activeRoleNames($this->assignableRoles))],
        ]);

        $roleIds = null;

        if (array_key_exists('roles', $validated)) {
            /** @var list<string> $names */
            $names = $validated['roles'];
            $roleIds = $this->roleIdsFor($this->assignableRoles, $names);

            Gate::authorize('assignRoles', [$user, $roleIds]);
        }

        try {
            $user = $this->updateUserAction->handle(
                $user,
                (int) $validated['version'],
                $validated['name'],
                $validated['email'],
                $roleIds,
                ChangeContext::fromMcpRequest($request),
            );
        } catch (LastAdministratorException) {
            return Response::error(__('roles.last_administrator'));
        }

        return Response::json(UserManagementData::fromUser($user->load('roles'))->toArray());
    }
}
