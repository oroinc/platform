<?php

namespace Oro\Bundle\UserBundle\Exception;

use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;

/**
 * This exception is thrown when a user has no organization units during the authentication.
 */
class EmptyOrganizationException extends CustomUserMessageAccountStatusException
{
    #[\Override]
    public function getMessageKey(): string
    {
        return 'oro_user.login.errors.empty_organization';
    }
}
