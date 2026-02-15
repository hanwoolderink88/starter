<?php

use App\Features\Auth\Data\WelcomePageData;
use App\Features\Settings\Controllers\PasswordController;
use App\Features\Settings\Controllers\ProfileController;
use App\Features\Settings\Controllers\TwoFactorAuthenticationController;
use App\Features\UserManagement\Controllers\CreateUserController;
use App\Features\UserManagement\Controllers\DestroyUserController;
use App\Features\UserManagement\Controllers\EditUserController;
use App\Features\UserManagement\Controllers\ImpersonateController;
use App\Features\UserManagement\Controllers\IndexUsersController;
use App\Features\UserManagement\Controllers\StoreUserController;
use App\Features\UserManagement\Controllers\UpdateUserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', fn () => Inertia::render('welcome', new WelcomePageData(
    canRegister: Features::enabled(Features::registration()),
)))->name('home');

Route::get('dashboard', fn () => Inertia::render('dashboard'))->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');
    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('settings/password', [PasswordController::class, 'edit'])->name('user-password.edit');
    Route::put('settings/password', [PasswordController::class, 'update'])->middleware('throttle:6,1')->name('user-password.update');
    Route::get('settings/appearance', fn () => Inertia::render('settings/appearance'))->name('appearance.edit');
    Route::get('settings/two-factor', [TwoFactorAuthenticationController::class, 'show'])->name('two-factor.show');

    Route::get('users', IndexUsersController::class)->name('users.index');
    Route::get('users/create', CreateUserController::class)->name('users.create');
    Route::post('users', StoreUserController::class)->name('users.store');
    Route::get('users/{user}/edit', EditUserController::class)->name('users.edit');
    Route::put('users/{user}', UpdateUserController::class)->name('users.update');
    Route::delete('users/{user}', DestroyUserController::class)->name('users.destroy');
    Route::post('users/{user}/impersonate', ImpersonateController::class)->name('users.impersonate');
});
