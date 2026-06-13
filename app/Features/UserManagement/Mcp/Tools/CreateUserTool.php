<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Mcp\Tools;

use App\Features\UserManagement\Actions\CreateUserAction;
use App\Features\UserManagement\Enums\Permission;
use App\Features\UserManagement\Enums\Role;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Enum;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Override;

#[Name('user_create')]
#[Description('Create a new user with a name, email, and role. The user is sent an invitation email to set their password.')]
class CreateUserTool extends Tool
{
    public function __construct(
        private readonly CreateUserAction $createUserAction,
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
            'role' => $schema->string()
                ->description('The role to assign to the user.')
                ->enum(array_column(Role::cases(), 'value'))
                ->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        Gate::authorize('create', User::class);

        $actor = $request->user();
        assert($actor instanceof User);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', new Enum(Role::class)],
        ]);

        $user = $this->createUserAction->handle(
            $validated['name'],
            $validated['email'],
            Role::from($validated['role']),
            $actor,
        );

        return Response::json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $validated['role'],
            'message' => 'User created. An invitation email has been sent to set their password.',
        ]);
    }
}
