<?php

namespace Oro\Bundle\SecurityBundle\Acl\Persistence;

use Oro\Bundle\SecurityBundle\Acl\Exception\InvalidAclManagerException;

/**
 * Provides common functionality for ACL managers with delegation support.
 *
 * This base class implements the {@see AclSidInterface} by delegating to a base ACL manager.
 * Subclasses should extend this to create specialized ACL managers that add additional functionality
 * while delegating core operations to the base manager.
 */
abstract class AbstractAclManager implements AclSidInterface
{
    /**
     * @var AclSidInterface
     */
    protected $baseAclManager;

    public function setBaseAclManager(AclSidInterface $baseAclManager)
    {
        $this->baseAclManager = $baseAclManager;
    }

    /**
     *
     * @throws InvalidAclManagerException
     */
    #[\Override]
    public function getSid($identity)
    {
        if ($this->baseAclManager instanceof AclSidInterface) {
            return $this->baseAclManager->getSid($identity);
        }

        throw new InvalidAclManagerException('Base Acl Manager should be defined');
    }
}
