<?php

declare(strict_types=1);

namespace App\Features\Roles\Exceptions;

use DomainException;

/**
 * The change would leave no administrator. There is always at least one
 * administrator who accepted their invitation. Controllers and tools
 * translate it to a validation error.
 */
class LastAdministratorException extends DomainException {}
