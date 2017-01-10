<?php

namespace Oro\Bundle\SecurityBundle\Owner\Metadata;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;

class RootOwnershipMetadata extends OwnershipMetadata
{
    /**
     * {@inheritdoc}
     */
    public function getAccessLevelNames()
    {
        // in community version the "root" ACL entry should not have GLOBAL(Organization) access level
        return AccessLevel::getAccessLevelNames(
            AccessLevel::BASIC_LEVEL,
            AccessLevel::SYSTEM_LEVEL,
            [AccessLevel::GLOBAL_LEVEL]
        );
    }
}
