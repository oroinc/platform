<?php

namespace Oro\Bundle\SecurityBundle\Acl\Domain;

use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;

/**
 * The SID to string converter that supports UserSecurityIdentity and RoleSecurityIdentity.
 */
class SecurityIdentityToStringConverter implements SecurityIdentityToStringConverterInterface
{
    /**
     * {@inheritDoc}
     */
    public function convert(SecurityIdentityInterface $sid): string
    {
        if ($sid instanceof UserSecurityIdentity) {
            return $sid->getClass() . '-' . $sid->getUsername();
        }

        if ($sid instanceof RoleSecurityIdentity) {
            return 'Role-' . $sid->getRole();
        }

        throw new \InvalidArgumentException(sprintf(
            'The security identity object "%s" is not supported.',
            get_class($sid)
        ));
    }
}
