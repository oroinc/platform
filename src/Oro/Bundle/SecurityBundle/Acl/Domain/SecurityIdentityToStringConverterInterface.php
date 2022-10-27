<?php

namespace Oro\Bundle\SecurityBundle\Acl\Domain;

use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;

/**
 * Represents a class that converts the security identity object to a string that unique identify this object.
 */
interface SecurityIdentityToStringConverterInterface
{
    /**
     * Returns a string that unique identify the given security identity object.
     */
    public function convert(SecurityIdentityInterface $sid): string;
}
