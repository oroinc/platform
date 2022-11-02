<?php

namespace Oro\Bundle\SecurityBundle\Acl\Persistence;

use Oro\Bundle\SecurityBundle\Model\Role;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Constructs SID based on role name, role object, user or security token.
 */
class BaseAclManager implements AclSidInterface
{
    /**
     * {@inheritdoc}
     */
    public function getSid($identity)
    {
        if (is_string($identity)) {
            return new RoleSecurityIdentity($identity);
        } elseif ($identity instanceof Role) {
            return new RoleSecurityIdentity((string)$identity->getRole());
        } elseif ($identity instanceof UserInterface) {
            return UserSecurityIdentity::fromAccount($identity);
        } elseif ($identity instanceof TokenInterface) {
            return UserSecurityIdentity::fromToken($identity);
        }

        throw new \InvalidArgumentException(
            sprintf(
                '$identity must be a string or implement one of RoleInterface, UserInterface, TokenInterface'
                . ' (%s given)',
                is_object($identity) ? get_class($identity) : gettype($identity)
            )
        );
    }
}
