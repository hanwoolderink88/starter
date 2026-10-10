<?php

declare(strict_types=1);

return [
    'conflict' => [
        'changed_by' => ':name saved this role on :time (version :version), after you opened it.',
        'changed' => 'Someone else saved this role after you opened it (version :version).',
    ],

    'title' => 'Roles',
    'description' => 'What each role may do, and who has it',

    'breadcrumb' => [
        'index' => 'Roles',
        'create' => 'Add role',
    ],

    'summary' => [
        'total' => ':count role|:count roles',
        'inactive' => ':count inactive',
    ],

    'columns' => [
        'role' => 'Role',
        'status' => 'Status',
        'permissions' => 'Permissions',
        'users' => 'Users',
    ],

    'status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'protected' => 'Protected',
    ],

    'list' => [
        'empty' => 'No roles yet.',
        'users' => ':count user|:count users',
    ],

    'actions' => [
        'create' => 'Add role',
        'open' => 'Open :name',
        'activate' => 'Activate',
        'deactivate' => 'Deactivate',
        'delete' => 'Delete',
    ],

    'form' => [
        'create_title' => 'Add role',
        'create_description' => 'Bundle permissions into a role you can give to users',
        'name' => 'Name',
        'name_placeholder' => 'e.g. Support',
        'description' => 'Description',
        'description_placeholder' => 'What is this role for?',
        'permissions' => 'Permissions',
        'permission_summary' => ':count of :total on',
        'protected_title' => 'This is the administrator role',
        'protected_body' => 'It always has every permission and can be neither deactivated nor deleted. You can change its name and description.',
        'protected_note' => 'Always all :count permissions',
        'save' => 'Save',
        'create' => 'Add role',
    ],

    'groups' => [
        'users' => 'Users',
        'roles' => 'Roles',
        'activity' => 'Activity',
    ],

    'permissions' => [
        'view users' => 'View users',
        'create users' => 'Create users',
        'update users' => 'Edit users',
        'delete users' => 'Delete users',
        'impersonate users' => 'Impersonate users',
        'manage roles' => 'Manage roles and give them to users',
        'view activity' => 'View the activity log',
    ],

    'meta' => [
        'users' => 'Given to :count user|Given to :count users',
    ],

    'deactivate_dialog' => [
        'title' => 'Deactivate :name?',
        'body' => ':count user loses the permissions of this role. The assignment is kept and works again when you reactivate the role.|:count users lose the permissions of this role. The assignments are kept and work again when you reactivate the role.',
        'body_unassigned' => 'Nobody has this role. While it is inactive, it cannot be given to anyone.',
        'cancel' => 'Cancel',
        'confirm' => 'Deactivate',
    ],

    'blocked_dialog' => [
        'title' => ':name cannot be deleted',
        'body' => ':count user still has this role. Take it away from them first.|:count users still have this role. Take it away from them first.',
        'more' => 'and :count more',
        'close' => 'Close',
    ],

    'delete_dialog' => [
        'title' => 'Delete :name?',
        'body' => 'The role and its permissions are deleted for good. The activity log keeps what it was.',
        'type_to_confirm' => 'Type :name to confirm',
        'cancel' => 'Cancel',
        'confirm' => 'Delete role',
    ],

    'flash' => [
        'created' => 'Role :name added.',
        'updated' => 'Role :name saved.',
        'deleted' => 'Role :name deleted.',
        'activated' => 'Role :name activated.',
        'deactivated' => 'Role :name deactivated.',
    ],

    'realtime' => [
        'created' => ':actor added the role :name.',
        'updated' => ':actor changed the role :name.',
        'deleted' => ':actor deleted the role :name.',
        'record_updated' => ':actor just changed this role.',
        'record_deleted' => ':actor deleted this role. Returning to the list.',
    ],

    'validation' => [
        'active_role' => 'Choose an active role.',
    ],

    'still_assigned' => ':name is still given to users. Take it away from them first.',
    'last_administrator' => 'There must always be an administrator. Give someone else the administrator role first.',
];
