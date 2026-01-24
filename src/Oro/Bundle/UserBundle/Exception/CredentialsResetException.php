<?php

namespace Oro\Bundle\UserBundle\Exception;

use Symfony\Component\Security\Core\Exception\AccountStatusException;

/**
 * Thrown when a user's credentials have been reset by an administrator.
 *
 * This exception is raised during authentication when the system detects that
 * a user's password has been reset by an administrator, requiring the user to
 * check their email for password reset instructions.
 */
class CredentialsResetException extends AccountStatusException
{
    #[\Override]
    public function getMessageKey(): string
    {
        return 'Your password was reset by administrator. Please, check your email for details.';
    }
}
