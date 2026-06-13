<?php

declare(strict_types=1);

namespace App\Features\Auth\Responses;

use App\Features\Auth\Concerns\RedirectsAfterAuthentication;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    use RedirectsAfterAuthentication;
}
