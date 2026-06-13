<?php

use App\Models\User;

test('login during the oauth flow forces a full-page visit to the consent screen', function () {
    $user = User::factory()->create();
    $intended = 'http://localhost/oauth/authorize?client_id=abc&response_type=code&scope=mcp%3Ause';

    $response = $this->withSession(['url.intended' => $intended])
        ->withHeader('X-Inertia', 'true')
        ->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

    $this->assertAuthenticated();

    // Inertia performs a hard visit when it receives a 409 + X-Inertia-Location,
    // so the browser loads Passport's (non-Inertia) consent screen directly.
    $response->assertStatus(409);
    $response->assertHeader('X-Inertia-Location', $intended);
});

test('a normal inertia login stays a client-side visit', function () {
    $user = User::factory()->create();

    $response = $this->withHeader('X-Inertia', 'true')
        ->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

    $this->assertAuthenticated();

    // No 409 hard-reload: a regular redirect to an Inertia page.
    $response->assertStatus(302);
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('login without inertia redirects normally into the oauth flow', function () {
    $user = User::factory()->create();
    $intended = 'http://localhost/oauth/authorize?client_id=abc&response_type=code';

    $response = $this->withSession(['url.intended' => $intended])
        ->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

    $this->assertAuthenticated();
    $response->assertRedirect($intended);
});
