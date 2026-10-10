<?php

declare(strict_types=1);

namespace App\Features\Roles\Exceptions;

use DomainException;

/**
 * A role cannot be deleted while a user still has it: the foreign keys would
 * otherwise take those assignments along silently. Controllers translate it
 * to a validation error on `role`.
 */
class RoleStillAssignedException extends DomainException {}
