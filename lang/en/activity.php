<?php

declare(strict_types=1);

return [
    'title' => 'Activity',
    'description' => 'Everything that happened in the application: who did what, when and from where',
    'anonymized_user' => 'Deleted user #:id',
    'system' => 'System',
    'guest' => 'Guest',

    'breadcrumb' => [
        'index' => 'Activity',
    ],

    'events' => [
        'created' => 'Created',
        'updated' => 'Updated',
        'deleted' => 'Deleted',
        'restored' => 'Restored',
        'anonymized' => 'Anonymized',
        'status_changed' => 'Status changed',
        'roles_changed' => 'Roles changed',
        'invitation_resent' => 'Invitation resent',
        'invitation_accepted' => 'Invitation accepted',
        'impersonation_started' => 'Impersonation started',
        'impersonation_stopped' => 'Impersonation stopped',
        'logged_in' => 'Logged in',
        'logged_out' => 'Logged out',
        'login_failed' => 'Login failed',
        'locked_out' => 'Locked out',
        'password_reset' => 'Password reset',
        'password_changed' => 'Password changed',
        'other_sessions_logged_out' => 'Logged out on other devices',
        'two_factor_enabled' => 'Two-factor authentication enabled',
        'two_factor_confirmed' => 'Two-factor authentication confirmed',
        'two_factor_disabled' => 'Two-factor authentication disabled',
        'two_factor_failed' => 'Two-factor code rejected',
        'recovery_codes_generated' => 'Recovery codes generated',
        'recovery_code_used' => 'Recovery code used',
    ],

    'sources' => [
        'web' => 'Web',
        'mcp' => 'Claude (MCP)',
        'system' => 'System',
        'guest' => 'Guest',
    ],

    'subjects' => [
        'user' => 'User',
        'role' => 'Role',
    ],

    'filters' => [
        'button' => 'Filters',
        'heading' => 'Filters',
        'event' => 'Event',
        'all_events' => 'All events',
        'causer' => 'Performed by',
        'all_causers' => 'Everyone',
        'source' => 'Source',
        'all_sources' => 'All sources',
        'subject_type' => 'Subject',
        'all_subject_types' => 'All subjects',
        'from' => 'From',
        'to' => 'To',
        'clear' => 'Clear',
        'done' => 'Done',
    ],

    'columns' => [
        'when' => 'When',
        'event' => 'Event',
        'subject' => 'Subject',
        'causer' => 'Performed by',
        'origin' => 'Origin',
    ],

    'entry' => [
        'impersonated_by' => 'impersonated by :name',
        'unknown_subject' => 'Unknown',
        'deleted_subject' => ':name (deleted)',
        'details' => 'Details',
        'field' => 'Field',
        'old' => 'Before',
        'new' => 'After',
        'snapshot' => 'State when deleted',
        'ip_address' => 'IP address',
        'user_agent' => 'Browser',
        'no_changes' => 'No further details.',
    ],

    'timeline' => [
        'title' => 'Activity',
        'description' => 'What happened, newest first',
        'empty' => 'No activity yet.',
        'view_all' => 'View all activity',
        'performed_by_user' => 'Activity by this user',
    ],

    'empty' => 'No activity found.',
];
