<?php

namespace Oro\Bundle\SecurityBundle\Owner\Metadata;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;

/**
 * Represents the entity ownership metadata that represents a "root" ACL entry.
 */
class RootOwnershipMetadata extends OwnershipMetadata
{
    /**
     * {@inheritDoc}
     */
    public function getAccessLevelNames(): array
    {
        // in community version the "root" ACL entry should not have GLOBAL(Organization) access level
        return AccessLevel::getAccessLevelNames(
            AccessLevel::BASIC_LEVEL,
            AccessLevel::SYSTEM_LEVEL,
            [AccessLevel::GLOBAL_LEVEL]
        );
    }
}
