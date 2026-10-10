<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Mcp\Tools;

use App\Features\Roles\Contracts\ListsAssignableRoles;
use App\Features\Roles\Data\AssignableRoleData;
use App\Features\Roles\Enums\Permission;
use App\Features\UserManagement\Data\UserFiltersData;
use App\Features\UserManagement\Data\UserManagementData;
use App\Features\UserManagement\Data\UserSortData;
use App\Features\UserManagement\Enums\SortDirection;
use App\Features\UserManagement\Enums\UserSortColumn;
use App\Features\UserManagement\Enums\UserStatus;
use App\Features\UserManagement\Services\UserManagementService;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Override;

#[Name('search_user')]
#[IsReadOnly]
#[Description('Search users by name or email, optionally filtered by role or status. Returns the first page of matches with the total count.')]
class SearchUsersTool extends Tool
{
    public function __construct(
        private readonly UserManagementService $userManagementService,
        private readonly ListsAssignableRoles $assignableRoles,
    ) {}

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
            'search' => $schema->string()
                ->description('Free-text search matched against the user\'s name and email.'),
            'role' => $schema->string()
                ->description('Filter by the name of a role.')
                ->enum($this->roleIdsByName()->keys()->all()),
            'status' => $schema->string()
                ->description('Filter by status.')
                ->enum(array_column(UserStatus::cases(), 'value')),
        ];
    }

    public function handle(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', Rule::in($this->roleIdsByName()->keys()->all())],
            'status' => ['nullable', new Enum(UserStatus::class)],
        ]);

        $filters = new UserFiltersData(
            search: $validated['search'] ?? null,
            roleId: isset($validated['role']) ? $this->roleIdsByName()->get($validated['role']) : null,
            status: isset($validated['status']) ? UserStatus::from($validated['status']) : null,
        );

        $paginator = $this->userManagementService->paginate(
            $filters,
            new UserSortData(UserSortColumn::Name, SortDirection::Asc),
        );

        $users = $paginator->items();

        return Response::json([
            'total' => $paginator->total(),
            'returned' => count($users),
            'users' => array_map(
                fn (User $user): array => UserManagementData::fromUser($user)->toArray(),
                $users,
            ),
        ]);
    }

    /**
     * @return Collection<string, int>
     */
    private function roleIdsByName(): Collection
    {
        return collect($this->assignableRoles->assignable())
            ->mapWithKeys(fn (AssignableRoleData $role): array => [$role->name => $role->id]);
    }
}
