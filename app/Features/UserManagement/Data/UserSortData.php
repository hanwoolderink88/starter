<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Data;

use App\Features\UserManagement\Enums\SortDirection;
use App\Features\UserManagement\Enums\UserSortColumn;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class UserSortData extends Data
{
    public function __construct(
        public UserSortColumn $column,
        public SortDirection $direction,
    ) {}
}
