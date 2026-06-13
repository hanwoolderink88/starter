<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class UsersPageData extends Data
{
    /**
     * @param  PaginatedDataCollection<int, UserManagementData>  $users
     * @param  array<string, string>  $roleOptions
     * @param  array<string, string>  $statusOptions
     */
    public function __construct(
        #[DataCollectionOf(UserManagementData::class)]
        public PaginatedDataCollection $users,
        public UserFiltersData $filters,
        public UserSortData $sort,
        public array $roleOptions,
        public array $statusOptions,
        public bool $canCreate,
        public bool $canImpersonate,
    ) {}
}
