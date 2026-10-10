<?php

use App\Features\Activity\Controllers\IndexActivityController;
use App\Features\Auth\Data\WelcomePageData;
use App\Features\Collaboration\Controllers\FieldLockController;
use App\Features\Collaboration\Controllers\FieldRevisionController;
use App\Features\Collaboration\Controllers\FieldValueController;
use App\Features\Roles\Controllers\CreateRoleController;
use App\Features\Roles\Controllers\DestroyRoleController;
use App\Features\Roles\Controllers\EditRoleController;
use App\Features\Roles\Controllers\IndexRolesController;
use App\Features\Roles\Controllers\StoreRoleController;
use App\Features\Roles\Controllers\ToggleRoleActiveController;
use App\Features\Roles\Controllers\UpdateRoleController;
use App\Features\Settings\Controllers\LocaleController;
use App\Features\Settings\Controllers\PasswordController;
use App\Features\Settings\Controllers\ProfileController;
use App\Features\Settings\Controllers\SessionController;
use App\Features\Settings\Controllers\TwoFactorAuthenticationController;
use App\Features\UserManagement\Controllers\CreateUserController;
use App\Features\UserManagement\Controllers\DestroyUserController;
use App\Features\UserManagement\Controllers\EditUserController;
use App\Features\UserManagement\Controllers\ImpersonateController;
use App\Features\UserManagement\Controllers\IndexUsersController;
use App\Features\UserManagement\Controllers\LogoutController;
use App\Features\UserManagement\Controllers\ResendInvitationController;
use App\Features\UserManagement\Controllers\RestoreUserController;
use App\Features\UserManagement\Controllers\RevokeUserRoleController;
use App\Features\UserManagement\Controllers\ShowAcceptInvitationController;
use App\Features\UserManagement\Controllers\ShowUserController;
use App\Features\UserManagement\Controllers\StopImpersonatingController;
use App\Features\UserManagement\Controllers\StoreAcceptInvitationController;
use App\Features\UserManagement\Controllers\StoreUserController;
use App\Features\UserManagement\Controllers\UpdateUserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', fn () => Inertia::render('welcome', new WelcomePageData(
    canRegister: Features::enabled(Features::registration()),
)))->name('home');

Route::get('/invitation/accept/{user}', ShowAcceptInvitationController::class)
    ->name('invitation.accept')
    ->middleware(['signed', 'guest']);

Route::post('/invitation/accept/{user}', StoreAcceptInvitationController::class)
    ->name('invitation.store')
    ->middleware('guest');

Route::get('dashboard', fn () => Inertia::render('dashboard'))->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth'])->group(function () {
    // Overrides Fortify's logout route, so logging out stops an impersonation first.
    Route::post('logout', LogoutController::class)->name('logout');
    Route::post('impersonation/stop', StopImpersonatingController::class)->name('impersonation.stop');

    Route::redirect('settings', '/settings/profile');
    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('settings/password', [PasswordController::class, 'edit'])->name('user-password.edit');
    Route::put('settings/password', [PasswordController::class, 'update'])->middleware('throttle:6,1')->name('user-password.update');
    Route::get('settings/appearance', fn () => Inertia::render('settings/appearance'))->name('appearance.edit');
    Route::patch('settings/locale', [LocaleController::class, 'update'])->name('locale.update');
    Route::get('settings/sessions', [SessionController::class, 'index'])->name('sessions.index');
    Route::delete('settings/sessions', [SessionController::class, 'destroy'])->middleware('throttle:6,1')->name('sessions.destroy');
    Route::get('settings/two-factor', [TwoFactorAuthenticationController::class, 'show'])->name('two-factor.show');

    Route::get('users', IndexUsersController::class)->name('users.index');
    Route::get('users/create', CreateUserController::class)->name('users.create');
    Route::get('users/{user}', ShowUserController::class)->name('users.show');
    Route::post('users', StoreUserController::class)->name('users.store');
    Route::get('users/{user}/edit', EditUserController::class)->name('users.edit');
    Route::put('users/{user}', UpdateUserController::class)->name('users.update');
    Route::delete('users/{user}', DestroyUserController::class)->name('users.destroy');
    Route::post('users/{user}/restore', RestoreUserController::class)->withTrashed()->name('users.restore');
    Route::post('users/{user}/impersonate', ImpersonateController::class)->name('users.impersonate');
    Route::post('users/{user}/resend-invitation', ResendInvitationController::class)->name('users.resend-invitation');
    Route::delete('users/{user}/roles/{role}', RevokeUserRoleController::class)->whereNumber('role')->name('users.roles.destroy');

    Route::get('roles', IndexRolesController::class)->name('roles.index');
    Route::get('roles/create', CreateRoleController::class)->name('roles.create');
    Route::post('roles', StoreRoleController::class)->name('roles.store');
    Route::get('roles/{role}/edit', EditRoleController::class)->name('roles.edit');
    Route::put('roles/{role}', UpdateRoleController::class)->name('roles.update');
    Route::post('roles/{role}/toggle-active', ToggleRoleActiveController::class)->name('roles.toggle-active');
    Route::delete('roles/{role}', DestroyRoleController::class)->name('roles.destroy');

    Route::get('activity', IndexActivityController::class)->name('activity.index');

    // Editing fields together (app/Infrastructure/Collaboration).
    Route::prefix('collaboration/{type}/{id}/{field}')->whereNumber('id')->name('collaboration.')->group(function () {
        Route::post('lock', [FieldLockController::class, 'store'])->name('lock.store');
        Route::put('lock', [FieldLockController::class, 'update'])->name('lock.update');
        Route::delete('lock', [FieldLockController::class, 'destroy'])->name('lock.destroy');
        Route::put('/', FieldValueController::class)->name('value.update');
        Route::get('revisions', [FieldRevisionController::class, 'index'])->name('revisions.index');
        Route::get('revisions/{number}', [FieldRevisionController::class, 'show'])->whereNumber('number')->name('revisions.show');
        Route::post('revisions/{number}/restore', [FieldRevisionController::class, 'restore'])->whereNumber('number')->name('revisions.restore');
    });
});
