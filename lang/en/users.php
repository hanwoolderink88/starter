<?php

declare(strict_types=1);

return [
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
        'role' => 'Role',
        'status' => 'Status',
        'created' => 'Created',
        'actions' => 'Actions',
    ],

    'status' => [
        'active' => 'Active',
        'invited' => 'Invited',
    ],

    'actions' => [
        'create' => 'Add user',
        'view' => 'View',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'impersonate' => 'Impersonate',
        'resend_invitation' => 'Resend invitation',
    ],

    'empty' => 'No users found.',

    'delete_dialog' => [
        'title' => 'Delete user',
        'description' => 'Are you sure you want to delete :name? This action cannot be undone.',
        'cancel' => 'Cancel',
        'confirm' => 'Delete',
    ],

    'form' => [
        'name' => 'Name',
        'name_placeholder' => 'Full name',
        'email' => 'Email',
        'email_placeholder' => 'Email address',
        'role' => 'Role',
        'role_placeholder' => 'Select a role',
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
    ],

    'realtime' => [
        'updated' => ':name was updated by :actor.',
        'created' => ':actor added :name.',
        'deleted' => ':actor deleted :name.',
        'record_updated' => 'This user was just updated by :actor.',
        'record_deleted' => ':actor deleted this user. Returning to the list.',
    ],

    'presence' => [
        'also_viewing' => 'Also viewing',
        'count' => ':count other person also viewing|:count other people also viewing',
    ],
];
