<?php

declare(strict_types=1);

return [
    'failed' => 'Deze gegevens komen niet overeen met onze administratie.',
    'password' => 'Het opgegeven wachtwoord is onjuist.',
    'throttle' => 'Te veel inlogpogingen. Probeer het over :seconds seconden opnieuw.',

    'fields' => [
        'name' => 'Naam',
        'name_placeholder' => 'Volledige naam',
        'email' => 'E-mailadres',
        'email_placeholder' => 'naam@voorbeeld.nl',
        'password' => 'Wachtwoord',
        'password_confirmation' => 'Bevestig wachtwoord',
        'remember' => 'Onthoud mij',
    ],

    'password_input' => [
        'show' => 'Wachtwoord tonen',
        'hide' => 'Wachtwoord verbergen',
    ],

    'login' => [
        'head' => 'Inloggen',
        'title' => 'Log in op je account',
        'description' => 'Vul hieronder je e-mailadres en wachtwoord in om in te loggen',
        'forgot_password' => 'Wachtwoord vergeten?',
        'submit' => 'Inloggen',
        'no_account' => 'Nog geen account?',
        'sign_up' => 'Registreren',
    ],

    'register' => [
        'head' => 'Registreren',
        'title' => 'Maak een account aan',
        'description' => 'Vul hieronder je gegevens in om je account aan te maken',
        'submit' => 'Account aanmaken',
        'has_account' => 'Heb je al een account?',
        'log_in' => 'Inloggen',
    ],

    'forgot_password' => [
        'head' => 'Wachtwoord vergeten',
        'title' => 'Wachtwoord vergeten',
        'description' => 'Vul je e-mailadres in, dan sturen we je een link om je wachtwoord opnieuw in te stellen',
        'submit' => 'Stuur de link',
        'return_to' => 'Of ga terug naar',
        'log_in' => 'inloggen',
    ],

    'reset_password' => [
        'head' => 'Wachtwoord opnieuw instellen',
        'title' => 'Wachtwoord opnieuw instellen',
        'description' => 'Vul hieronder je nieuwe wachtwoord in',
        'email' => 'E-mailadres',
        'submit' => 'Wachtwoord opslaan',
    ],

    'verify_email' => [
        'head' => 'E-mailadres bevestigen',
        'title' => 'Bevestig je e-mailadres',
        'description' => 'Bevestig je e-mailadres via de link die we je net hebben gemaild.',
        'link_sent' => 'We hebben een nieuwe bevestigingslink gestuurd naar het e-mailadres waarmee je je hebt geregistreerd.',
        'resend' => 'Stuur de bevestigingsmail opnieuw',
        'logout' => 'Uitloggen',
    ],

    'confirm_password' => [
        'head' => 'Wachtwoord bevestigen',
        'title' => 'Bevestig je wachtwoord',
        'description' => 'Dit is een beveiligd deel van de applicatie. Bevestig je wachtwoord om verder te gaan.',
        'submit' => 'Wachtwoord bevestigen',
    ],

    'two_factor' => [
        'head' => 'Tweestapsverificatie',
        'submit' => 'Doorgaan',
        'or' => 'of je kunt',
        'code' => [
            'title' => 'Verificatiecode',
            'description' => 'Vul de code in die je authenticator-app toont.',
            'use_instead' => 'inloggen met een verificatiecode',
        ],
        'recovery' => [
            'title' => 'Herstelcode',
            'description' => 'Bevestig de toegang tot je account met een van je herstelcodes.',
            'placeholder' => 'Vul een herstelcode in',
            'use_instead' => 'inloggen met een herstelcode',
        ],
    ],

    'invitation' => [
        'accept' => [
            'head' => 'Uitnodiging accepteren',
            'title' => 'Welkom, :name!',
            'description' => 'Kies een wachtwoord om je account af te ronden.',
            'submit' => 'Account afronden',
            'processing' => 'Wachtwoord opslaan...',
        ],
        'expired' => [
            'head' => 'Uitnodiging verlopen',
            'title' => 'Uitnodigingslink verlopen',
            'description' => 'Deze uitnodigingslink is verlopen of ongeldig.',
            'contact_admin' => 'Vraag je beheerder om een nieuwe uitnodiging.',
        ],
    ],
];
