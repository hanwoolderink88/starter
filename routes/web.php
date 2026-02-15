<?php

use App\Features\Auth\Data\WelcomePageData;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', fn () => Inertia::render('welcome', new WelcomePageData(
    canRegister: Features::enabled(Features::registration()),
)))->name('home');

Route::get('dashboard', fn () => Inertia::render('dashboard'))->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';
