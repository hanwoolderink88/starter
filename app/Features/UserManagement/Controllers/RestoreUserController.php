<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Controllers;

use App\Features\UserManagement\Actions\RestoreUserAction;
use App\Http\Controllers\Controller;
use App\Infrastructure\Activity\Data\ChangeContext;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class RestoreUserController extends Controller
{
    public function __construct(
        private readonly RestoreUserAction $restoreUserAction,
    ) {}

    public function __invoke(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('restore', $user);

        $this->restoreUserAction->handle($user, ChangeContext::fromRequest($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('users.restored', ['name' => $user->name])]);

        return back();
    }
}
