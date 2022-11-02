<?php

namespace Oro\Bundle\UserBundle\Exception;

use Symfony\Component\Security\Core\Exception\AccountStatusException;

/**
 * Throws during authentication if an user has no assigned owner.
 */
class EmptyOwnerException extends AccountStatusException
{
    /**
     * {@inheritdoc}
     */
    public function getMessageKey()
    {
        return 'oro_user.login.errors.empty_owner';
    }
}
