<?php

namespace Oro\Bundle\SecurityBundle\Acl\Persistence;

use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface as SID;

/**
 * Defines the contract for constructing security identities from various identity sources.
 *
 * Implementations of this interface are responsible for converting different types
 * of identity representations into SecurityIdentityInterface objects that can be
 * used in ACL operations.
 */
interface AclSidInterface
{
    /**
     * Constructs SID (an object implements SecurityIdentityInterface) based on the given identity
     *
     * @param mixed $identity
     * @throws \InvalidArgumentException
     * @return SID
     */
    public function getSid($identity);
}
