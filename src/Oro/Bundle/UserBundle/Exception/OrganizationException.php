<?php

namespace Oro\Bundle\UserBundle\Exception;

use Symfony\Component\Security\Core\Exception\AccountStatusException;

/**
 * Thrown when a user does not have an active organization assigned.
 *
 * This exception is raised during authentication when the system detects that
 * a user lacks an active organization assignment, preventing them from accessing
 * the application.
 */
class OrganizationException extends AccountStatusException
{
    #[\Override]
    public function getMessageKey(): string
    {
        return 'You don\'t have active organization assigned.';
    }
}
