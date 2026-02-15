<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Enums;

enum Permission: string
{
    case ViewUsers = 'view users';
    case CreateUsers = 'create users';
    case UpdateUsers = 'update users';
    case DeleteUsers = 'delete users';
    case ImpersonateUsers = 'impersonate users';
}
