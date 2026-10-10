<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;

test('the framework mails are translated', function () {
    $user = User::factory()->create();
    app()->setLocale('nl');

    expect(new ResetPassword('token')->toMail($user)->subject)->toBe('Stel je wachtwoord opnieuw in')
        ->and(new VerifyEmail()->toMail($user)->subject)->toBe('Bevestig je e-mailadres');
});
