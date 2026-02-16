<?php

use App\Features\UserManagement\Enums\Permission;
use App\Features\UserManagement\Enums\Role;
use App\Features\UserManagement\Notifications\InvitationNotification;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(function () {
    foreach (Permission::cases() as $permission) {
        PermissionModel::findOrCreate($permission->value, 'web');
    }

    $superAdminRole = RoleModel::findOrCreate(Role::SuperAdmin->value, 'web');
    $superAdminRole->givePermissionTo(PermissionModel::all());
    RoleModel::findOrCreate(Role::User->value, 'web');
});

if (! function_exists('createAdmin')) {
    function createAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::SuperAdmin);

        return $admin;
    }
}

if (! function_exists('createRegularUser')) {
    function createRegularUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::User);

        return $user;
    }
}

test('admin creates user and invitation is sent', function () {
    Notification::fake();

    $this->actingAs(createAdmin());

    $this->post(route('users.store'), [
        'name' => 'Invited User',
        'email' => 'invited@example.com',
        'role' => Role::User->value,
    ])->assertRedirect(route('users.index'));

    $user = User::where('email', 'invited@example.com')->first();

    expect($user)->not->toBeNull();

    $this->assertDatabaseHas('users', [
        'email' => 'invited@example.com',
        'password' => null,
    ]);

    Notification::assertSentTo($user, InvitationNotification::class);
});

test('invited user can view acceptance page via signed URL', function () {
    $user = User::factory()->invited()->create();

    $url = URL::temporarySignedRoute('invitation.accept', now()->addHours(48), ['user' => $user->id]);

    $this->get($url)
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('auth/accept-invitation'));
});

test('invited user can accept invitation', function () {
    $user = User::factory()->invited()->create();

    $this->post(route('invitation.store', $user), [
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])->assertRedirect('/dashboard');

    $user->refresh();

    expect($user->password)->not->toBeNull()
        ->and($user->email_verified_at)->not->toBeNull();

    expect(Auth::check())->toBeTrue()
        ->and(Auth::id())->toBe($user->id);
});

test('expired signed URL shows error page', function () {
    $user = User::factory()->invited()->create();

    $url = URL::temporarySignedRoute('invitation.accept', now()->subHour(), ['user' => $user->id]);

    $this->get($url)
        ->assertStatus(403)
        ->assertInertia(fn ($page) => $page->component('auth/invitation-expired'));
});

test('tampered signed URL is rejected', function () {
    $user = User::factory()->invited()->create();

    $url = URL::temporarySignedRoute('invitation.accept', now()->addHours(48), ['user' => $user->id]);

    $tamperedUrl = $url.'tampered';

    $this->get($tamperedUrl)
        ->assertStatus(403)
        ->assertInertia(fn ($page) => $page->component('auth/invitation-expired'));
});

test('already-accepted user is redirected to login on GET', function () {
    $user = User::factory()->create();

    $url = URL::temporarySignedRoute('invitation.accept', now()->addHours(48), ['user' => $user->id]);

    $this->get($url)->assertRedirect(route('login'));
});

test('already-accepted user is redirected to login on POST', function () {
    $user = User::factory()->create();

    $originalPasswordHash = $user->password;

    $this->post(route('invitation.store', $user), [
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ])->assertRedirect(route('login'));

    expect($user->fresh()->password)->toBe($originalPasswordHash);
});

test('acceptance validates password requirements', function () {
    $user = User::factory()->invited()->create();

    $this->post(route('invitation.store', $user), [
        'password' => '',
        'password_confirmation' => '',
    ])->assertSessionHasErrors(['password']);

    $this->post(route('invitation.store', $user), [
        'password' => 'short',
        'password_confirmation' => 'short',
    ])->assertSessionHasErrors(['password']);

    $this->post(route('invitation.store', $user), [
        'password' => 'Password123!',
        'password_confirmation' => 'DifferentPassword123!',
    ])->assertSessionHasErrors(['password']);
});

test('admin can resend invitation', function () {
    Notification::fake();

    $admin = createAdmin();
    $invitedUser = User::factory()->invited()->create();

    $this->actingAs($admin)
        ->post(route('users.resend-invitation', $invitedUser))
        ->assertRedirect();

    Notification::assertSentTo($invitedUser, InvitationNotification::class);
});

test('resend invitation blocked for accepted user', function () {
    Notification::fake();

    $admin = createAdmin();
    $acceptedUser = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('users.resend-invitation', $acceptedUser))
        ->assertSessionHas('error');

    Notification::assertNothingSent();
});

test('regular user cannot resend invitation', function () {
    $regularUser = createRegularUser();
    $invitedUser = User::factory()->invited()->create();

    $this->actingAs($regularUser)
        ->post(route('users.resend-invitation', $invitedUser))
        ->assertForbidden();
});

test('invited user cannot login', function () {
    $user = User::factory()->invited()->create();

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'any-password',
    ]);

    expect(Auth::check())->toBeFalse();
});
