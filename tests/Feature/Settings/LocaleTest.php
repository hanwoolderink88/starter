<?php

use App\Features\UserManagement\Notifications\InvitationNotification;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

test('guests get the application default', function () {
    $this->get(route('login'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('locale', 'en')
            ->where('supportedLocales', ['en', 'nl']));
});

test('guests get their browser language when it is supported', function () {
    $this->withHeader('Accept-Language', 'nl-NL,nl;q=0.9,en;q=0.8')
        ->get(route('login'))
        ->assertInertia(fn (Assert $page) => $page->where('locale', 'nl'));

    $this->withHeader('Accept-Language', 'de-DE,de;q=0.9')
        ->get(route('login'))
        ->assertInertia(fn (Assert $page) => $page->where('locale', 'en'));
});

test('a user\'s own choice wins over the browser', function () {
    $user = User::factory()->create(['locale' => 'nl']);

    $this->actingAs($user)
        ->withHeader('Accept-Language', 'en-US')
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('locale', 'nl'));
});

test('a user can choose a language, which reloads the whole page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withHeader('X-Inertia', 'true')
        ->patch(route('locale.update'), ['locale' => 'nl'])
        ->assertStatus(409)
        ->assertHeader('X-Inertia-Location', route('appearance.edit'));

    expect($user->fresh()->locale)->toBe('nl');
});

test('only a supported language can be chosen', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('locale.update'), ['locale' => 'de'])
        ->assertSessionHasErrors('locale');

    expect($user->fresh()->locale)->toBeNull();
});

test('mails are sent in the language of the recipient', function () {
    Notification::fake();
    $user = User::factory()->create(['locale' => 'nl', 'password' => null]);

    $user->notify(new InvitationNotification($user));

    Notification::assertSentTo(
        $user,
        InvitationNotification::class,
        fn (InvitationNotification $notification, array $channels, User $notifiable, ?string $locale) => $locale === 'nl',
    );
});

test('the invitation mail is translated', function () {
    $user = User::factory()->create(['password' => null]);
    $english = new InvitationNotification($user)->toMail($user)->subject;

    app()->setLocale('nl');

    expect(new InvitationNotification($user)->toMail($user)->subject)->not->toBe($english);
});
