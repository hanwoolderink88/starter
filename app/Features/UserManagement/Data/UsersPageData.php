<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Data;

use App\Features\Roles\Data\AssignableRoleData;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class UsersPageData extends Data
{
    /**
     * @param  PaginatedDataCollection<int, UserManagementData>  $users
     * @param  list<AssignableRoleData>  $roleOptions  every role, inactive ones included
     * @param  array<string, string>  $statusOptions
     * @param  list<int>  $impersonatableIds  the users on this page the viewer may impersonate now
     */
    public function __construct(
        #[DataCollectionOf(UserManagementData::class)]
        public PaginatedDataCollection $users,
        public UserFiltersData $filters,
        public UserSortData $sort,
        public array $roleOptions,
        public array $statusOptions,
        public bool $canCreate,
        public array $impersonatableIds,
        /** How many days a deleted user can be restored before they are anonymized. */
        public int $restoreDays,
    ) {}
}
