<?php

declare(strict_types=1);

return [
    'conflict' => [
        'changed_by' => ':name heeft deze rol op :time opgeslagen (versie :version), nadat jij hem opende.',
        'changed' => 'Iemand anders heeft deze rol opgeslagen nadat jij hem opende (versie :version).',
    ],

    'title' => 'Rollen',
    'description' => 'Wat elke rol mag, en wie hem heeft',

    'breadcrumb' => [
        'index' => 'Rollen',
        'create' => 'Nieuwe rol',
    ],

    'summary' => [
        'total' => ':count rol|:count rollen',
        'inactive' => ':count inactief',
    ],

    'columns' => [
        'role' => 'Rol',
        'status' => 'Status',
        'permissions' => 'Permissies',
        'users' => 'Gebruikers',
    ],

    'status' => [
        'active' => 'Actief',
        'inactive' => 'Inactief',
        'protected' => 'Beschermd',
    ],

    'list' => [
        'empty' => 'Er zijn nog geen rollen.',
        'users' => ':count gebruiker|:count gebruikers',
    ],

    'actions' => [
        'create' => 'Nieuwe rol',
        'open' => ':name openen',
        'activate' => 'Activeren',
        'deactivate' => 'Deactiveren',
        'delete' => 'Verwijderen',
    ],

    'form' => [
        'create_title' => 'Nieuwe rol',
        'create_description' => 'Bundel permissies in een rol die je aan gebruikers kunt geven',
        'name' => 'Naam',
        'name_placeholder' => 'Bijvoorbeeld Support',
        'description' => 'Omschrijving',
        'description_placeholder' => 'Waar is deze rol voor?',
        'permissions' => 'Permissies',
        'permission_summary' => ':count van :total aan',
        'protected_title' => 'Dit is de beheerdersrol',
        'protected_body' => 'Deze rol heeft altijd alle permissies en kan niet worden gedeactiveerd of verwijderd. Je kunt de naam en omschrijving wel aanpassen.',
        'protected_note' => 'Altijd alle :count permissies',
        'save' => 'Opslaan',
        'create' => 'Rol aanmaken',
    ],

    'groups' => [
        'users' => 'Gebruikers',
        'roles' => 'Rollen',
        'activity' => 'Activiteit',
    ],

    'permissions' => [
        'view users' => 'Gebruikers bekijken',
        'create users' => 'Gebruikers aanmaken',
        'update users' => 'Gebruikers bewerken',
        'delete users' => 'Gebruikers verwijderen',
        'impersonate users' => 'Gebruikers imiteren',
        'manage roles' => 'Rollen beheren en aan gebruikers geven',
        'view activity' => 'Activiteitenlog bekijken',
    ],

    'meta' => [
        'users' => 'Gegeven aan :count gebruiker|Gegeven aan :count gebruikers',
    ],

    'deactivate_dialog' => [
        'title' => ':name deactiveren?',
        'body' => ':count gebruiker verliest de rechten van deze rol. De toekenning blijft bewaard en werkt weer zodra je de rol opnieuw activeert.|:count gebruikers verliezen de rechten van deze rol. De toekenningen blijven bewaard en werken weer zodra je de rol opnieuw activeert.',
        'body_unassigned' => 'Niemand heeft deze rol. Zolang hij inactief is, kun je hem aan niemand geven.',
        'cancel' => 'Annuleren',
        'confirm' => 'Deactiveren',
    ],

    'blocked_dialog' => [
        'title' => ':name kan niet weg',
        'body' => ':count gebruiker heeft deze rol nog. Haal hem daar eerst weg.|:count gebruikers hebben deze rol nog. Haal hem daar eerst weg.',
        'more' => 'en nog :count',
        'close' => 'Sluiten',
    ],

    'delete_dialog' => [
        'title' => ':name verwijderen?',
        'body' => 'De rol en zijn permissies worden definitief verwijderd. Het activiteitenlog bewaart hoe hij was.',
        'type_to_confirm' => 'Typ :name om te bevestigen',
        'cancel' => 'Annuleren',
        'confirm' => 'Rol verwijderen',
    ],

    'flash' => [
        'created' => 'Rol :name toegevoegd.',
        'updated' => 'Rol :name opgeslagen.',
        'deleted' => 'Rol :name verwijderd.',
        'activated' => 'Rol :name geactiveerd.',
        'deactivated' => 'Rol :name gedeactiveerd.',
    ],

    'realtime' => [
        'created' => ':actor voegde de rol :name toe.',
        'updated' => ':actor wijzigde de rol :name.',
        'deleted' => ':actor verwijderde de rol :name.',
        'record_updated' => ':actor heeft deze rol net gewijzigd.',
        'record_deleted' => ':actor heeft deze rol verwijderd. Je gaat terug naar de lijst.',
    ],

    'validation' => [
        'active_role' => 'Kies een actieve rol.',
    ],

    'still_assigned' => ':name is nog aan gebruikers gegeven. Haal hem daar eerst weg.',
    'last_administrator' => 'Er moet altijd een beheerder zijn. Geef eerst iemand anders de beheerdersrol.',
];
