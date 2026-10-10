<?php

declare(strict_types=1);

return [
    'locked_by' => ':name bewerkt dit.',
    'locked_other_tab' => 'Je bewerkt dit in een ander tabblad.',
    'not_held' => 'Je bewerkt dit veld niet meer.',

    'field' => [
        'editing_by' => ':name bewerkt dit',
        'editing_by_impersonated' => ':name (geïmiteerd door :impersonator) bewerkt dit',
        'editing_via_claude' => ':name bewerkt dit via Claude',
        'other_tab' => 'Je bewerkt dit in een ander tabblad',
        'saving' => 'Opslaan…',
        'saved' => 'Opgeslagen',
        'unsaved' => 'Niet opgeslagen',
        'offline' => 'Verbinding verbroken. Je tekst blijft staan en wordt opgeslagen zodra je weer online bent.',
        'expired' => 'Je bewerksessie is gesloten na een tijdje niet typen. Je tekst is opgeslagen.',
        'continue' => 'Verder bewerken',
        'claim_failed' => 'Dit veld kon niet worden geopend om te bewerken.',
        'conflict_fallback' => 'Iemand anders heeft dit veld gewijzigd terwijl je offline was.',
        'history' => 'Geschiedenis',
    ],

    'revisions' => [
        'title' => 'Geschiedenis van :field',
        'description' => 'Elke bewerksessie van deze tekst. Terugzetten maakt een nieuwe versie; er gaat niets verloren.',
        'empty' => 'Nog geen eerdere versies.',
        'number' => 'Versie :number',
        'restored_from' => 'teruggezet van versie :number',
        'by' => 'door :name',
        'via_claude' => 'via Claude',
        'impersonated_by' => 'geïmiteerd door :name',
        'changes' => 'Wijzigingen tot de huidige tekst',
        'no_changes' => 'Gelijk aan de huidige tekst.',
        'content' => 'Tekst van deze versie',
        'restore' => 'Deze versie terugzetten',
        'restore_locked' => 'Iemand bewerkt dit veld; zet terug zodra die klaar is.',
        'restored' => 'Versie :number teruggezet.',
        'load_failed' => 'De geschiedenis kon niet worden geladen.',
        'additions' => '+:count',
        'deletions' => '−:count',
    ],
];
