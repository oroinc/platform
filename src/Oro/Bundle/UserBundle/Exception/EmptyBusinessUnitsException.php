<?php

namespace Oro\Bundle\UserBundle\Exception;

use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;

/**
 * This exception is thrown when a user has no business units during the authentication.
 */
class EmptyBusinessUnitsException extends CustomUserMessageAccountStatusException
{
    #[\Override]
    public function getMessageKey(): string
    {
        return 'oro_user.login.errors.empty_business_units';
    }
}
