<?php

declare(strict_types=1);

namespace App\Features\Settings\Actions;

use App\Features\Settings\Services\ProfileService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeleteProfileAction
{
    public function __construct(
        private readonly ProfileService $profileService,
    ) {}

    public function handle(Request $request, User $user): void
    {
        Auth::logout();

        $this->profileService->delete($user);

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
