<?php

namespace Oro\Bundle\UserBundle\Exception;

use Symfony\Component\Security\Core\Exception\AccountStatusException;

/**
 * Throws during authentication if an user has no assigned owner.
 */
class EmptyOwnerException extends AccountStatusException
{
    #[\Override]
    public function getMessageKey(): string
    {
        return 'oro_user.login.errors.empty_owner';
    }
}
