<?php

declare(strict_types=1);

namespace App\Features\Settings\Controllers;

use App\Features\Settings\Actions\LogoutOtherSessionsAction;
use App\Features\Settings\Data\SessionsPageData;
use App\Features\Settings\Requests\LogoutOtherSessionsRequest;
use App\Features\Settings\Services\SessionService;
use App\Http\Controllers\Controller;
use App\Infrastructure\Activity\Data\ChangeContext;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SessionController extends Controller
{
    public function __construct(
        private readonly SessionService $sessionService,
        private readonly LogoutOtherSessionsAction $logoutOtherSessionsAction,
    ) {}

    /**
     * Show where the user is logged in.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        assert($user instanceof User);

        return Inertia::render('settings/sessions', new SessionsPageData(
            sessions: $this->sessionService->forUser($user, $request->session()->getId()),
            available: $this->sessionService->isAvailable(),
        ));
    }

    /**
     * Log the user out on every other device.
     */
    public function destroy(LogoutOtherSessionsRequest $request): RedirectResponse
    {
        $user = $request->user();
        assert($user instanceof User);

        $password = $request->validated('password');
        assert(is_string($password));

        $this->logoutOtherSessionsAction->handle($user, $password, $request->session()->getId(), ChangeContext::fromRequest($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('settings.sessions.logged_out')]);

        return redirect()->route('sessions.index');
    }
}
