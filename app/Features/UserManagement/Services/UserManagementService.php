<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Services;

use App\Features\Roles\Models\Role;
use App\Features\UserManagement\Data\UserFiltersData;
use App\Features\UserManagement\Data\UserSortData;
use App\Features\UserManagement\Enums\UserStatus;
use App\Infrastructure\Activity\Exceptions\StaleVersionException;
use App\Infrastructure\Activity\Models\Activity;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Passport;

class UserManagementService
{
    public const int TIMELINE_LIMIT = 20;

    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function paginate(UserFiltersData $filters, UserSortData $sort): LengthAwarePaginator
    {
        $query = User::query()->with('roles');

        $search = $filters->search;
        if ($search !== null) {
            $query->where(function (Builder $query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $roleId = $filters->roleId;
        if ($roleId !== null) {
            $query->whereHas('roles', function (Builder $query) use ($roleId): void {
                $query->withoutGlobalScope(Role::ACTIVE_SCOPE)->whereKey($roleId);
            });
        }

        if ($filters->status !== null) {
            match ($filters->status) {
                UserStatus::Active => $query->whereNotNull('password'),
                UserStatus::Invited => $query->whereNull('password'),
                UserStatus::Deleted => $query->onlyTrashed()->whereNull('anonymized_at'),
            };
        }

        return $query
            ->orderBy($sort->column->value, $sort->direction->value)
            ->paginate(10)
            ->withQueryString();
    }

    /**
     * The newest log entries about a user: what was done to their account,
     * and their own logins and logouts.
     *
     * @return Collection<int, Activity>
     */
    public function timeline(User $user): Collection
    {
        return Activity::query()
            ->forSubject($user)
            ->limit(self::TIMELINE_LIMIT)
            ->get();
    }

    public function store(string $name, string $email): User
    {
        return User::create([
            'name' => $name,
            'email' => $email,
        ]);
    }

    /**
     * @throws StaleVersionException when the user changed since `$expectedVersion`
     */
    public function update(User $user, int $expectedVersion, string $name, string $email): User
    {
        $user->updateAtVersion($expectedVersion, [
            'name' => $name,
            'email' => $email,
        ]);

        return $user;
    }

    public function acceptInvitation(User $user, string $password): void
    {
        $user->forceFill([
            'password' => $password,
            'email_verified_at' => now(),
        ])->save();
    }

    /**
     * Soft delete the user and end their access at once: their sessions and
     * API tokens go too, so no open browser or MCP client keeps working.
     */
    public function delete(User $user): void
    {
        $user->delete();

        $this->revokeAccess($user);
    }

    public function restore(User $user): User
    {
        $user->restore();

        return $user;
    }

    /**
     * The deleted users whose term to be restored ended before the moment and
     * who are not anonymized yet.
     *
     * @return Collection<int, User>
     */
    public function dueForAnonymization(CarbonImmutable $deletedBefore): Collection
    {
        return User::onlyTrashed()
            ->whereNull('anonymized_at')
            ->where('deleted_at', '<', $deletedBefore)
            ->get();
    }

    /**
     * Replace everything that identifies the user with the label. The row
     * stays (soft-deleted), so ids in the log and elsewhere keep pointing at
     * something; the roles, secrets and access go.
     */
    public function anonymize(User $user, string $label): User
    {
        $user->forceFill([
            'name' => $label,
            'email' => 'deleted-user-'.$user->id.'@anonymized.invalid',
            'email_verified_at' => null,
            'password' => null,
            'remember_token' => null,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'locale' => null,
            'anonymized_at' => now(),
        ])->saveQuietly();

        $user->roles()->detach();
        $user->permissions()->detach();

        $this->revokeAccess($user);

        return $user;
    }

    /**
     * End the user's sessions (when they are kept in the database, which also
     * holds their IP address and browser) and their API tokens.
     */
    private function revokeAccess(User $user): void
    {
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        }

        $tokenIds = Passport::token()->newQuery()->where('user_id', $user->id)->pluck('id');

        Passport::refreshToken()->newQuery()->whereIn('access_token_id', $tokenIds)->delete();
        Passport::token()->newQuery()->whereIn('id', $tokenIds)->delete();
    }
}
