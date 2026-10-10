<?php

declare(strict_types=1);

return [
    'conflict' => [
        'changed_by' => ':name saved this user on :time (version :version), after you opened it.',
        'changed' => 'Someone else saved this user after you opened it (version :version).',
    ],

    'title' => 'Users',
    'description' => 'Manage users and their roles',

    'breadcrumb' => [
        'index' => 'Users',
        'edit' => 'Edit :name',
        'show' => ':name',
        'create' => 'Add user',
    ],

    'filters' => [
        'search_placeholder' => 'Search by name or email',
        'all_roles' => 'All roles',
        'all_statuses' => 'All statuses',
        'role' => 'Role',
        'status' => 'Status',
        'button' => 'Filters',
        'heading' => 'Filters',
        'clear' => 'Clear',
        'done' => 'Done',
    ],

    'columns' => [
        'name' => 'Name',
        'email' => 'Email',
        'role' => 'Roles',
        'status' => 'Status',
        'created' => 'Created',
        'actions' => 'Actions',
    ],

    'status' => [
        'active' => 'Active',
        'invited' => 'Invited',
        'deleted' => 'Deleted',
    ],

    'deleted' => [
        'anonymizes_on' => 'Anonymized on :date',
    ],

    'restored' => ':name has been restored.',

    'actions' => [
        'create' => 'Add user',
        'view' => 'View',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'impersonate' => 'Impersonate',
        'restore' => 'Restore',
        'resend_invitation' => 'Resend invitation',
    ],

    'empty' => 'No users found.',

    'delete_dialog' => [
        'title' => 'Delete user',
        'description' => ':name can no longer log in. You can restore them for :days days; after that their personal data is anonymized for good.',
        'cancel' => 'Cancel',
        'confirm' => 'Delete',
    ],

    'form' => [
        'name' => 'Name',
        'name_placeholder' => 'Full name',
        'email' => 'Email',
        'email_placeholder' => 'Email address',
        'roles' => 'Roles',
        'roles_hint' => 'A user gets the permissions of all their active roles.',
        'roles_readonly' => 'Only someone who may manage roles can change these.',
        'role_inactive' => 'Inactive, cannot be given',
        'role_inactive_assigned' => 'Inactive: grants nothing, but stays assigned',
        'revoke' => 'Take away',
        'revoke_label' => 'Take :role away',
        'no_roles' => 'No roles',
        'update' => 'Update user',
        'create' => 'Add user',
        'edit_title' => 'Edit :name',
        'edit_description' => 'Update user information and role',
        'create_title' => 'Add user',
        'create_description' => 'Add a new user to the system',
    ],

    'show' => [
        'description' => 'User details',
        'member_since' => 'Member since',
        'edit' => 'Edit user',
        'impersonate' => 'Impersonate',
    ],

    'impersonation' => [
        'banner' => 'You are impersonating :name. Everything you do is logged in your name too.',
        'stop' => 'Stop impersonating',
        'settings_blocked' => 'While impersonating, the profile, password and two-factor authentication of this account cannot be changed.',
    ],

    'realtime' => [
        'updated' => ':name was updated by :actor.',
        'created' => ':actor added :name.',
        'deleted' => ':actor deleted :name.',
        'record_updated' => 'This user was just updated by :actor.',
        'record_deleted' => ':actor deleted this user. Returning to the list.',
    ],

    'invitation' => [
        'mail' => [
            'subject' => 'You\'ve been invited',
            'intro' => 'You have been invited to join :app.',
            'instruction' => 'Click the button below to set up your account.',
            'action' => 'Set up your account',
            'expiry' => 'This link expires in 48 hours.',
        ],
        'resent' => 'Invitation email has been resent.',
        'already_set_up' => 'Cannot resend the invitation to a user who has already set their password.',
        'already_accepted' => 'Invitation already accepted. Please log in.',
    ],

    'presence' => [
        'also_viewing' => 'Also viewing',
        'count' => ':count other person also viewing|:count other people also viewing',
    ],
];
