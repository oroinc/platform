<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Token;

use Oro\Bundle\SecurityBundle\Model\Role;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * The interface for the authentication tokens that information about the organization an user is logged in.
 */
interface RolesAwareTokenInterface extends TokenInterface
{
    /**
     * Returns the user roles.
     *
     * @return Role[]
     */
    public function getRoles(): array;
}
