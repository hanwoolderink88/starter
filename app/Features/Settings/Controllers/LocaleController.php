<?php

declare(strict_types=1);

namespace App\Features\Settings\Controllers;

use App\Features\Settings\Requests\LocaleUpdateRequest;
use App\Features\Settings\Services\ProfileService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * A user's own language choice. Not logged in the activity log: it is a
 * preference that changes nothing for anyone else.
 */
class LocaleController extends Controller
{
    public function __construct(
        private readonly ProfileService $profileService,
    ) {}

    /**
     * Save the language and reload the whole page, so every translation that
     * was already loaded switches too.
     */
    public function update(LocaleUpdateRequest $request): Response
    {
        $user = $request->user();
        assert($user instanceof User);

        $locale = $request->validated('locale');
        assert(is_string($locale));

        $this->profileService->updateLocale($user, $locale);
        App::setLocale($locale);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('settings.language.updated')]);

        return Inertia::location(route('appearance.edit'));
    }
}
