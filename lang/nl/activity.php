<?php

declare(strict_types=1);

return [
    'title' => 'Activiteit',
    'description' => 'Alles wat er in de applicatie gebeurde: wie deed wat, wanneer en waarvandaan',
    'anonymized_user' => 'Verwijderde gebruiker #:id',
    'system' => 'Systeem',
    'guest' => 'Gast',

    'breadcrumb' => [
        'index' => 'Activiteit',
    ],

    'events' => [
        'created' => 'Aangemaakt',
        'updated' => 'Gewijzigd',
        'deleted' => 'Verwijderd',
        'restored' => 'Hersteld',
        'anonymized' => 'Geanonimiseerd',
        'status_changed' => 'Status gewijzigd',
        'roles_changed' => 'Rollen gewijzigd',
        'invitation_resent' => 'Uitnodiging opnieuw verstuurd',
        'invitation_accepted' => 'Uitnodiging geaccepteerd',
        'impersonation_started' => 'Imiteren gestart',
        'impersonation_stopped' => 'Imiteren gestopt',
        'logged_in' => 'Ingelogd',
        'logged_out' => 'Uitgelogd',
        'login_failed' => 'Inloggen mislukt',
        'locked_out' => 'Geblokkeerd',
        'password_reset' => 'Wachtwoord opnieuw ingesteld',
        'password_changed' => 'Wachtwoord gewijzigd',
        'other_sessions_logged_out' => 'Uitgelogd op andere apparaten',
        'two_factor_enabled' => 'Tweestapsverificatie aangezet',
        'two_factor_confirmed' => 'Tweestapsverificatie bevestigd',
        'two_factor_disabled' => 'Tweestapsverificatie uitgezet',
        'two_factor_failed' => 'Verificatiecode geweigerd',
        'recovery_codes_generated' => 'Herstelcodes aangemaakt',
        'recovery_code_used' => 'Herstelcode gebruikt',
    ],

    'sources' => [
        'web' => 'Web',
        'mcp' => 'Claude (MCP)',
        'system' => 'Systeem',
        'guest' => 'Gast',
    ],

    'subjects' => [
        'user' => 'Gebruiker',
        'role' => 'Rol',
    ],

    'filters' => [
        'button' => 'Filters',
        'heading' => 'Filters',
        'event' => 'Gebeurtenis',
        'all_events' => 'Alle gebeurtenissen',
        'causer' => 'Uitgevoerd door',
        'all_causers' => 'Iedereen',
        'source' => 'Bron',
        'all_sources' => 'Alle bronnen',
        'subject_type' => 'Onderwerp',
        'all_subject_types' => 'Alle onderwerpen',
        'from' => 'Van',
        'to' => 'Tot en met',
        'clear' => 'Wissen',
        'done' => 'Klaar',
    ],

    'columns' => [
        'when' => 'Wanneer',
        'event' => 'Gebeurtenis',
        'subject' => 'Onderwerp',
        'causer' => 'Uitgevoerd door',
        'origin' => 'Herkomst',
    ],

    'entry' => [
        'impersonated_by' => 'geïmiteerd door :name',
        'unknown_subject' => 'Onbekend',
        'deleted_subject' => ':name (verwijderd)',
        'details' => 'Details',
        'field' => 'Veld',
        'old' => 'Voor',
        'new' => 'Na',
        'snapshot' => 'Stand bij verwijderen',
        'ip_address' => 'IP-adres',
        'user_agent' => 'Browser',
        'no_changes' => 'Geen verdere details.',
    ],

    'timeline' => [
        'title' => 'Activiteit',
        'description' => 'Wat er gebeurde, nieuwste eerst',
        'empty' => 'Hier is nog niets gebeurd.',
        'view_all' => 'Bekijk alle activiteit',
        'performed_by_user' => 'Activiteit van deze gebruiker',
    ],

    'empty' => 'Geen activiteit gevonden.',
];
