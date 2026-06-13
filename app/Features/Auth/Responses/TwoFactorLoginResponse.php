<?php

declare(strict_types=1);

namespace App\Features\Auth\Responses;

use App\Features\Auth\Concerns\RedirectsAfterAuthentication;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;

class TwoFactorLoginResponse implements TwoFactorLoginResponseContract
{
    use RedirectsAfterAuthentication;
}
