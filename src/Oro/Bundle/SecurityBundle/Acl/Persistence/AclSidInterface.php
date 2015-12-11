<?php

namespace Oro\Bundle\SecurityBundle\Acl\Persistence;

use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface as SID;

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
