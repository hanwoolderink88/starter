<?php

declare(strict_types=1);

return [
    'conflict' => [
        'changed_by' => ':name heeft deze gebruiker op :time opgeslagen (versie :version), nadat jij hem opende.',
        'changed' => 'Iemand anders heeft deze gebruiker opgeslagen nadat jij hem opende (versie :version).',
    ],

    'title' => 'Gebruikers',
    'description' => 'Beheer gebruikers en hun rollen',

    'breadcrumb' => [
        'index' => 'Gebruikers',
        'edit' => ':name bewerken',
        'show' => ':name',
        'create' => 'Gebruiker toevoegen',
    ],

    'filters' => [
        'search_placeholder' => 'Zoek op naam of e-mailadres',
        'all_roles' => 'Alle rollen',
        'all_statuses' => 'Alle statussen',
        'role' => 'Rol',
        'status' => 'Status',
        'button' => 'Filters',
        'heading' => 'Filters',
        'clear' => 'Wissen',
        'done' => 'Klaar',
    ],

    'columns' => [
        'name' => 'Naam',
        'email' => 'E-mailadres',
        'role' => 'Rollen',
        'status' => 'Status',
        'created' => 'Aangemaakt',
        'actions' => 'Acties',
    ],

    'status' => [
        'active' => 'Actief',
        'invited' => 'Uitgenodigd',
        'deleted' => 'Verwijderd',
    ],

    'deleted' => [
        'anonymizes_on' => 'Geanonimiseerd op :date',
    ],

    'restored' => ':name is hersteld.',

    'actions' => [
        'create' => 'Gebruiker toevoegen',
        'view' => 'Bekijken',
        'edit' => 'Bewerken',
        'delete' => 'Verwijderen',
        'impersonate' => 'Imiteren',
        'restore' => 'Herstellen',
        'resend_invitation' => 'Uitnodiging opnieuw sturen',
    ],

    'empty' => 'Geen gebruikers gevonden.',

    'delete_dialog' => [
        'title' => 'Gebruiker verwijderen',
        'description' => ':name kan dan niet meer inloggen. Je kunt de gebruiker nog :days dagen herstellen; daarna worden de persoonsgegevens definitief geanonimiseerd.',
        'cancel' => 'Annuleren',
        'confirm' => 'Verwijderen',
    ],

    'form' => [
        'name' => 'Naam',
        'name_placeholder' => 'Volledige naam',
        'email' => 'E-mailadres',
        'email_placeholder' => 'E-mailadres',
        'roles' => 'Rollen',
        'roles_hint' => 'Een gebruiker krijgt de rechten van al zijn actieve rollen.',
        'roles_readonly' => 'Alleen wie rollen mag beheren, kan deze wijzigen.',
        'role_inactive' => 'Inactief · kan niet worden gekozen',
        'role_inactive_assigned' => 'Inactief · nog toegekend, geeft geen rechten',
        'revoke' => 'Intrekken',
        'revoke_label' => ':role intrekken',
        'no_roles' => 'Geen rollen',
        'update' => 'Gebruiker bijwerken',
        'create' => 'Gebruiker toevoegen',
        'edit_title' => ':name bewerken',
        'edit_description' => 'Werk de gegevens en de rollen van de gebruiker bij',
        'create_title' => 'Gebruiker toevoegen',
        'create_description' => 'Voeg een nieuwe gebruiker toe',
    ],

    'show' => [
        'description' => 'Gegevens van de gebruiker',
        'member_since' => 'Lid sinds',
        'edit' => 'Gebruiker bewerken',
        'impersonate' => 'Imiteren',
    ],

    'impersonation' => [
        'banner' => 'Je imiteert :name. Alles wat je doet, wordt ook op jouw naam gelogd.',
        'stop' => 'Stoppen met imiteren',
        'settings_blocked' => 'Tijdens het imiteren kun je het profiel, het wachtwoord en de tweestapsverificatie van dit account niet wijzigen.',
    ],

    'realtime' => [
        'updated' => ':name is bijgewerkt door :actor.',
        'created' => ':actor heeft :name toegevoegd.',
        'deleted' => ':actor heeft :name verwijderd.',
        'record_updated' => 'Deze gebruiker is zojuist bijgewerkt door :actor.',
        'record_deleted' => ':actor heeft deze gebruiker verwijderd. Je gaat terug naar de lijst.',
    ],

    'invitation' => [
        'mail' => [
            'subject' => 'Je bent uitgenodigd',
            'intro' => 'Je bent uitgenodigd voor :app.',
            'instruction' => 'Klik op de knop hieronder om je account in te stellen.',
            'action' => 'Account instellen',
            'expiry' => 'Deze link verloopt over 48 uur.',
        ],
        'resent' => 'De uitnodiging is opnieuw gestuurd.',
        'already_set_up' => 'Deze gebruiker heeft al een wachtwoord ingesteld. Er is geen uitnodiging om opnieuw te sturen.',
        'already_accepted' => 'Deze uitnodiging is al geaccepteerd. Log in om verder te gaan.',
    ],

    'presence' => [
        'also_viewing' => 'Kijkt ook mee',
        'count' => ':count andere persoon kijkt mee|:count andere mensen kijken mee',
    ],
];
