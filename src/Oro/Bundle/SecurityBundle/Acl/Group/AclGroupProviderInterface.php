<?php

namespace Oro\Bundle\SecurityBundle\Acl\Group;

/**
 * Defines the contract for ACL group providers.
 *
 * Implementations determine whether they support providing security groups
 * and retrieve the appropriate security group identifier for ACL operations.
 */
interface AclGroupProviderInterface
{
    const DEFAULT_SECURITY_GROUP = '';

    /**
     * @return bool
     */
    public function supports();

    /**
     * @return string
     */
    public function getGroup();
}
