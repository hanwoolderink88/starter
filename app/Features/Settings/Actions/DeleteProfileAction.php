<?php

declare(strict_types=1);

namespace App\Features\Settings\Actions;

use App\Features\Roles\Exceptions\LastAdministratorException;
use App\Features\UserManagement\Actions\DeleteUserAction;
use App\Infrastructure\Activity\Data\ChangeContext;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Deleting your own account goes the same way as an administrator deleting
 * it: a soft delete that is logged, restorable for a while and then
 * anonymized.
 */
class DeleteProfileAction
{
    public function __construct(
        private readonly DeleteUserAction $deleteUserAction,
    ) {}

    /**
     * @throws LastAdministratorException when the user is the last administrator
     */
    public function handle(Request $request, User $user): void
    {
        $this->deleteUserAction->handle($user, ChangeContext::fromRequest($request));

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
