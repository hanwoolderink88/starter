<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Mcp\Tools;

use App\Features\Roles\Contracts\ListsAssignableRoles;
use App\Features\Roles\Enums\Permission;
use App\Features\UserManagement\Actions\CreateUserAction;
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

#[Name('user_create')]
#[Description('Create a new user with a name, email and optionally roles. The user is sent an invitation email to set their password. Assigning roles requires the manage roles permission.')]
class CreateUserTool extends Tool
{
    use ResolvesRoleNames;

    public function __construct(
        private readonly CreateUserAction $createUserAction,
        private readonly ListsAssignableRoles $assignableRoles,
    ) {}

    public function shouldRegister(Request $request): bool
    {
        return $request->user()?->can(Permission::CreateUsers->value) ?? false;
    }

    /**
     * @return array<string, Type>
     */
    #[Override]
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()
                ->description('The full name of the user.')
                ->required(),
            'email' => $schema->string()
                ->description('The email address of the user. Must be unique.')
                ->required(),
            'roles' => $schema->array()
                ->description('The names of the active roles to give the user. Leave out for none.')
                ->items($schema->string()->enum($this->activeRoleNames($this->assignableRoles))),
        ];
    }

    public function handle(Request $request): Response
    {
        Gate::authorize('create', User::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->withoutTrashed()],
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['distinct', Rule::in($this->activeRoleNames($this->assignableRoles))],
        ]);

        /** @var list<string> $roleNames */
        $roleNames = $validated['roles'] ?? [];

        if ($roleNames !== []) {
            Gate::authorize('assignRoles', User::class);
        }

        $roleIds = $this->roleIdsFor($this->assignableRoles, $roleNames);

        $user = $this->createUserAction->handle(
            $validated['name'],
            $validated['email'],
            $roleIds,
            ChangeContext::fromMcpRequest($request),
        );

        return Response::json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $roleNames,
            'message' => 'User created. An invitation email has been sent to set their password.',
        ]);
    }
}
