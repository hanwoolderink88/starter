<?php

declare(strict_types=1);

namespace App\Features\Roles\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class RolesPageData extends Data
{
    /**
     * @param  DataCollection<int, RoleData>  $roles  every role, inactive ones included
     * @param  int  $permissionTotal  the size of the catalogue, for "n / total"
     */
    public function __construct(
        #[DataCollectionOf(RoleData::class)]
        public DataCollection $roles,
        public int $permissionTotal,
        public int $inactiveCount,
    ) {}
}
