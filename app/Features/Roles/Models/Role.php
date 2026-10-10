<?php

declare(strict_types=1);

namespace App\Features\Roles\Models;

use App\Infrastructure\Activity\Concerns\HasVersion;
use Database\Factories\RoleFactory;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Override;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * A role whose permissions only count while it is active.
 *
 * The global scope `active` hides inactive roles from `$user->roles`,
 * `findByName`/`findById` and `$permission->roles` (and so from Spatie's
 * permission cache), so an inactive role grants nothing and cannot be
 * assigned by name or id. Its assignments stay in `model_has_roles`.
 *
 * Exactly one role is protected: the administrator role, recognised by
 * `is_protected` rather than by its editable name.
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property bool $is_active
 * @property bool $is_protected
 * @property int $version
 */
#[UseFactory(RoleFactory::class)]
class Role extends SpatieRole
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory;

    use HasVersion;

    /**
     * The name of the global scope that hides inactive roles.
     */
    public const string ACTIVE_SCOPE = 'active';

    /**
     * Pinned to `web` (like `User`) so Spatie looks up roles under the guard
     * they are defined in, even when a request is authenticated via the
     * Passport `api` guard, e.g. an MCP tool call.
     */
    protected string $guard_name = 'web';

    /**
     * Matches the column default, so a role created in this request already
     * knows its first version.
     *
     * @var array<string, mixed>
     */
    #[Override]
    protected $attributes = [
        'version' => 1,
    ];

    #[Override]
    protected static function booted(): void
    {
        static::addGlobalScope(self::ACTIVE_SCOPE, function (Builder $query): void {
            $query->where($query->qualifyColumn('is_active'), true);
        });
    }

    /**
     * Resolve route bindings without the `active` scope, so an inactive role
     * stays reachable to edit and reactivate.
     *
     * @param  Model|BuilderContract|Relation<Model, Model, mixed>  $query
     * @param  mixed  $value
     * @param  string|null  $field
     * @return BuilderContract
     */
    #[Override]
    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        /** @var Builder<self>|Relation<self, Model, mixed> $bindingQuery */
        $bindingQuery = parent::resolveRouteBindingQuery($query, $value, $field);

        return $bindingQuery->withoutGlobalScope(self::ACTIVE_SCOPE);
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_protected' => 'boolean',
        ];
    }
}
