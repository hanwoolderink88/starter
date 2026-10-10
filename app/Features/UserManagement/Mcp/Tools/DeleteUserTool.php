<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Mcp\Tools;

use App\Features\Roles\Enums\Permission;
use App\Features\Roles\Exceptions\LastAdministratorException;
use App\Features\UserManagement\Actions\DeleteUserAction;
use App\Infrastructure\Activity\Data\ChangeContext;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\Gate;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Override;

#[Name('user_delete')]
#[Description('Delete a user: they can no longer log in and lose their sessions and tokens. They can be restored with user_restore for a limited time; after that their personal data is anonymized for good. Confirm with the person before deleting.')]
#[IsDestructive]
class DeleteUserTool extends Tool
{
    public function __construct(
        private readonly DeleteUserAction $deleteUserAction,
    ) {}

    public function shouldRegister(Request $request): bool
    {
        return $request->user()?->can(Permission::DeleteUsers->value) ?? false;
    }

    /**
     * @return array<string, Type>
     */
    #[Override]
    public function schema(JsonSchema $schema): array
    {
        return [
            'user_id' => $schema->integer()->description('The ID of the user to delete.')->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        $user = User::query()->find($request->get('user_id'));

        if (! $user instanceof User) {
            return Response::error('User not found.');
        }

        Gate::authorize('delete', $user);

        try {
            $this->deleteUserAction->handle($user, ChangeContext::fromMcpRequest($request));
        } catch (LastAdministratorException) {
            return Response::error(__('roles.last_administrator'));
        }

        return Response::json([
            'id' => $user->id,
            'restorable_for_days' => config('privacy.anonymize_deleted_users_after_days'),
            'message' => 'User deleted. They can be restored with user_restore until they are anonymized.',
        ]);
    }
}
