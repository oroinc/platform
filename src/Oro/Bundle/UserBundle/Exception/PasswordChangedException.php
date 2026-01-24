<?php

namespace Oro\Bundle\UserBundle\Exception;

use Symfony\Component\Security\Core\Exception\AccountStatusException;

/**
 * Thrown when a user's password has been changed.
 *
 * This exception is raised during authentication when the system detects that
 * a user's password has been changed, potentially invalidating the current
 * authentication session.
 */
class PasswordChangedException extends AccountStatusException
{
    #[\Override]
    public function getMessageKey(): string
    {
        return 'Password has been changed.';
    }
}
