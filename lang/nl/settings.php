<?php

declare(strict_types=1);

return [
    'title' => 'Instellingen',
    'description' => 'Beheer je profiel en je account',

    'nav' => [
        'label' => 'Instellingen',
        'profile' => 'Profiel',
        'password' => 'Wachtwoord',
        'two_factor' => 'Tweestapsverificatie',
        'appearance' => 'Weergave',
        'sessions' => 'Sessies',
    ],

    'profile' => [
        'title' => 'Profielinstellingen',
        'heading' => 'Profielgegevens',
        'description' => 'Wijzig je naam en e-mailadres',
        'name' => 'Naam',
        'name_placeholder' => 'Volledige naam',
        'email' => 'E-mailadres',
        'email_placeholder' => 'E-mailadres',
        'unverified' => 'Je e-mailadres is nog niet bevestigd.',
        'resend_verification' => 'Klik hier om de bevestigingsmail opnieuw te sturen.',
        'verification_sent' => 'Er is een nieuwe bevestigingslink naar je e-mailadres gestuurd.',
        'save' => 'Opslaan',
    ],

    'password' => [
        'title' => 'Wachtwoordinstellingen',
        'heading' => 'Wachtwoord wijzigen',
        'description' => 'Gebruik een lang, willekeurig wachtwoord om je account veilig te houden',
        'current' => 'Huidig wachtwoord',
        'new' => 'Nieuw wachtwoord',
        'confirm' => 'Bevestig wachtwoord',
        'save' => 'Wachtwoord opslaan',
    ],

    'sessions' => [
        'title' => 'Browsersessies',
        'heading' => 'Browsersessies',
        'description' => 'Waar je bent ingelogd. Log overal anders uit als je een apparaat kwijt bent of een sessie niet herkent.',
        'unavailable' => 'Sessies zijn alleen te tonen als ze in de database worden bewaard.',
        'this_device' => 'Dit apparaat',
        'last_active' => 'Laatst actief :time',
        'unknown_browser' => 'Onbekende browser',
        'unknown_platform' => 'onbekend systeem',
        'unknown_ip' => 'onbekend adres',
        'logout_others' => 'Uitloggen op andere apparaten',
        'dialog_title' => 'Uitloggen op andere apparaten',
        'dialog_description' => 'Vul je wachtwoord in om uit te loggen op al je andere apparaten. Daarmee vervalt daar ook "onthoud mij".',
        'password' => 'Wachtwoord',
        'cancel' => 'Annuleren',
        'confirm' => 'Andere sessies uitloggen',
        'logged_out' => 'Je bent uitgelogd op je andere apparaten.',
    ],

    'appearance' => [
        'title' => 'Weergave-instellingen',
        'description' => 'Pas de weergave van je account aan',
        'theme' => [
            'title' => 'Thema',
            'description' => 'Licht, donker of volgens je systeem',
        ],
        'options' => [
            'light' => 'Licht',
            'dark' => 'Donker',
            'system' => 'Systeem',
        ],
    ],

    'language' => [
        'title' => 'Taal',
        'description' => 'Kies de taal van de interface',
        'options' => [
            'en' => 'English',
            'nl' => 'Nederlands',
        ],
        'updated' => 'Taal bijgewerkt.',
        'blocked' => 'Tijdens het imiteren kun je de taal van dit account niet wijzigen.',
    ],

    'delete_account' => [
        'heading' => 'Account verwijderen',
        'description' => 'Verwijder je account en alles wat erbij hoort',
        'warning_title' => 'Let op',
        'warning' => 'Je kunt daarna niet meer inloggen. Een beheerder kan je account nog een tijdje herstellen; daarna worden je persoonsgegevens definitief geanonimiseerd.',
        'button' => 'Account verwijderen',
        'dialog_title' => 'Weet je zeker dat je je account wilt verwijderen?',
        'dialog_description' => 'Als je account is verwijderd, word je uitgelogd en kun je niet meer inloggen. Na een tijdje worden je persoonsgegevens definitief geanonimiseerd. Vul je wachtwoord in om te bevestigen.',
        'password' => 'Wachtwoord',
        'cancel' => 'Annuleren',
        'confirm' => 'Account verwijderen',
    ],

    'two_factor' => [
        'title' => 'Tweestapsverificatie',
        'description' => 'Beheer je tweestapsverificatie',
        'enabled' => 'Aan',
        'disabled' => 'Uit',
        'enabled_text' => 'Met tweestapsverificatie vraagt de app bij het inloggen om een veilige, willekeurige code. Die vind je in de authenticator-app (TOTP) op je telefoon.',
        'disabled_text' => 'Zet je tweestapsverificatie aan, dan vraagt de app bij het inloggen om een veilige code. Die vind je in een authenticator-app (TOTP) op je telefoon.',
        'enable' => 'Zet 2FA aan',
        'disable' => 'Zet 2FA uit',
        'continue_setup' => 'Ga verder met instellen',

        'recovery' => [
            'title' => 'Herstelcodes voor 2FA',
            'description' => 'Met een herstelcode kom je weer binnen als je je 2FA-apparaat kwijt bent. Bewaar ze in een veilige wachtwoordmanager.',
            'view' => 'Toon herstelcodes',
            'hide' => 'Verberg herstelcodes',
            'regenerate' => 'Maak nieuwe codes',
            'list_label' => 'Herstelcodes',
            'loading' => 'Herstelcodes laden',
            'warning_before' => 'Elke herstelcode werkt één keer en vervalt na gebruik. Heb je er meer nodig, klik dan hierboven op',
            'warning_after' => '.',
        ],

        'setup' => [
            'enabled_title' => 'Tweestapsverificatie staat aan',
            'enabled_description' => 'Tweestapsverificatie staat nu aan. Scan de QR-code of voer de sleutel in je authenticator-app in.',
            'verify_title' => 'Bevestig de code',
            'verify_description' => 'Voer de 6-cijferige code uit je authenticator-app in',
            'enable_title' => 'Zet tweestapsverificatie aan',
            'enable_description' => 'Scan de QR-code of voer de sleutel in je authenticator-app in om tweestapsverificatie aan te zetten',
            'manual' => 'of voer de code handmatig in',
            'copy_key' => 'Kopieer de sleutel',
            'close' => 'Sluiten',
            'continue' => 'Doorgaan',
            'back' => 'Terug',
            'confirm' => 'Bevestigen',
        ],

        'errors' => [
            'qr_code' => 'De QR-code kon niet worden opgehaald',
            'setup_key' => 'De sleutel kon niet worden opgehaald',
            'recovery_codes' => 'De herstelcodes konden niet worden opgehaald',
        ],
    ],

    'flash' => [
        'profile_updated' => 'Profiel bijgewerkt.',
        'password_updated' => 'Wachtwoord bijgewerkt.',
    ],
];
