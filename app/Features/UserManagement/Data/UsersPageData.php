<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class UsersPageData extends Data
{
    /**
     * @param  DataCollection<int, UserManagementData>  $users
     */
    public function __construct(
        #[DataCollectionOf(UserManagementData::class)]
        public DataCollection $users,
        public bool $canCreate,
        public bool $canImpersonate,
    ) {}
}
