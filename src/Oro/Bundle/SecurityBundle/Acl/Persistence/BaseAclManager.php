<?php

namespace Oro\Bundle\SecurityBundle\Acl\Persistence;

use Symfony\Component\Security\Core\Role\RoleInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnitInterface;
use Oro\Bundle\SecurityBundle\Acl\Domain\BusinessUnitSecurityIdentity;

class BaseAclManager implements AclSidInterface
{
    /**
     * {@inheritdoc}
     *
     * @param string|RoleInterface|UserInterface|TokenInterface|BusinessUnitInterface $identity
     */
    public function getSid($identity)
    {
        if (is_string($identity)) {
            return new RoleSecurityIdentity($identity);
        } elseif ($identity instanceof RoleInterface) {
            return new RoleSecurityIdentity($identity->getRole());
        } elseif ($identity instanceof UserInterface) {
            return UserSecurityIdentity::fromAccount($identity);
        } elseif ($identity instanceof TokenInterface) {
            return UserSecurityIdentity::fromToken($identity);
        } elseif ($identity instanceof BusinessUnitInterface) {
            return BusinessUnitSecurityIdentity::fromBusinessUnit($identity);
        }

        throw new \InvalidArgumentException(
            sprintf(
                '$identity must be a string or implement one of RoleInterface, UserInterface, TokenInterface,'
                . ' BusinessUnitInterface (%s given)',
                is_object($identity) ? get_class($identity) : gettype($identity)
            )
        );
    }
}
