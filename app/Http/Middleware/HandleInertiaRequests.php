<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Data\SharedData;
use App\Features\Auth\Data\AuthData;
use App\Features\Auth\Data\UserData;
use App\Features\UserManagement\Enums\Permission;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            ...new SharedData(
                name: config('app.name'),
                auth: new AuthData(
                    user: $user instanceof User ? UserData::from($user) : null,
                ),
                permissions: $user instanceof User
                    ? array_values(array_filter(
                        Permission::cases(),
                        fn (Permission $permission) => $user->can($permission->value),
                    ))
                    : [],
                sidebarOpen: ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            )->toArray(),
        ];
    }
}
