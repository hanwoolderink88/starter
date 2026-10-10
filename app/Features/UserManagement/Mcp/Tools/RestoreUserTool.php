<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Mcp\Tools;

use App\Features\Roles\Enums\Permission;
use App\Features\UserManagement\Actions\RestoreUserAction;
use App\Features\UserManagement\Data\UserManagementData;
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
use Override;

#[Name('user_restore')]
#[Description('Bring back a deleted user who is not anonymized yet (search_user with status "deleted" lists them). They log in again; their old sessions stay revoked.')]
class RestoreUserTool extends Tool
{
    public function __construct(
        private readonly RestoreUserAction $restoreUserAction,
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
            'user_id' => $schema->integer()->description('The ID of the deleted user.')->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        $user = User::onlyTrashed()->find($request->get('user_id'));

        if (! $user instanceof User) {
            return Response::error('Deleted user not found.');
        }

        Gate::authorize('restore', $user);

        $user = $this->restoreUserAction->handle($user, ChangeContext::fromMcpRequest($request));

        return Response::json(UserManagementData::fromUser($user->load('roles'))->toArray());
    }
}
