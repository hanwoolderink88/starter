<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Mcp\Tools;

use App\Features\Roles\Enums\Permission;
use App\Features\UserManagement\Actions\ResendInvitationAction;
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

#[Name('user_resend_invitation')]
#[Description('Send the invitation email again to a user who has not set their password yet.')]
class ResendInvitationTool extends Tool
{
    public function __construct(
        private readonly ResendInvitationAction $resendInvitationAction,
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
            'user_id' => $schema->integer()->description('The ID of the invited user.')->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        Gate::authorize('create', User::class);

        $user = User::query()->find($request->get('user_id'));

        if (! $user instanceof User) {
            return Response::error('User not found.');
        }

        if ($user->password !== null) {
            return Response::error(__('users.invitation.already_set_up'));
        }

        $this->resendInvitationAction->handle($user, ChangeContext::fromMcpRequest($request));

        return Response::text(__('users.invitation.resent'));
    }
}
