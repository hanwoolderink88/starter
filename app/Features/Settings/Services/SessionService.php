<?php

declare(strict_types=1);

namespace App\Features\Settings\Services;

use App\Features\Settings\Data\SessionData;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * The browser sessions of a user. They can only be listed and removed when
 * the session driver is `database`; other drivers keep no index per user.
 */
class SessionService
{
    public function isAvailable(): bool
    {
        return config('session.driver') === 'database';
    }

    /**
     * @return list<SessionData>
     */
    public function forUser(User $user, string $currentSessionId): array
    {
        if (! $this->isAvailable()) {
            return [];
        }

        return array_values($this->query($user)
            ->orderByDesc('last_activity')
            ->get(['id', 'ip_address', 'user_agent', 'last_activity'])
            // @phpstan-ignore argument.type (the selected columns of the sessions table)
            ->map(fn (object $row): SessionData => SessionData::fromRow($row, $currentSessionId))
            ->all());
    }

    /**
     * Remove every session of the user except the current one.
     *
     * @return int the number of sessions removed
     */
    public function deleteOthers(User $user, string $currentSessionId): int
    {
        if (! $this->isAvailable()) {
            return 0;
        }

        return $this->query($user)->where('id', '!=', $currentSessionId)->delete();
    }

    private function query(User $user): Builder
    {
        $table = config('session.table', 'sessions');
        assert(is_string($table));

        return DB::table($table)->where('user_id', $user->id);
    }
}
