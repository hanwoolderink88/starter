<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Controllers;

use App\Features\UserManagement\Actions\DeleteUserAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class DestroyUserController extends Controller
{
    public function __construct(
        private readonly DeleteUserAction $deleteUserAction,
    ) {}

    public function __invoke(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();
        assert($actor instanceof User);

        if ($actor->id === $user->id) {
            throw new AccessDeniedHttpException;
        }

        Gate::authorize('delete', $user);

        $this->deleteUserAction->handle($user, $actor);

        return redirect()->route('users.index');
    }
}
