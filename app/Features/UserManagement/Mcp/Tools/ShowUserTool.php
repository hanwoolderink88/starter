<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Mcp\Tools;

use App\Features\UserManagement\Data\UserManagementData;
use App\Features\UserManagement\Enums\Permission;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\Gate;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Override;

#[Name('user_detail')]
#[Description('Retrieve detailed information about a single user by their ID.')]
class ShowUserTool extends Tool
{
    public function shouldRegister(Request $request): bool
    {
        return $request->user()?->can(Permission::ViewUsers->value) ?? false;
    }

    /**
     * @return array<string, Type>
     */
    #[Override]
    public function schema(JsonSchema $schema): array
    {
        return [
            'user_id' => $schema->integer()
                ->description('The ID of the user to retrieve.')
                ->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer'],
        ]);

        $user = User::query()->with('roles')->whereKey($validated['user_id'])->first();

        if ($user === null) {
            return Response::error('User not found.');
        }

        Gate::authorize('view', $user);

        return Response::json(UserManagementData::fromUser($user)->toArray());
    }
}
