<?php

namespace Oro\Bundle\SecurityBundle\Acl\Persistence;

use Oro\Bundle\SecurityBundle\Acl\Exception\InvalidAclManagerException;

abstract class AbstractAclManager implements AclSidInterface
{
    /**
     * @var AclSidInterface
     */
    protected $baseAclManager;

    /**
     * @param AclSidInterface $baseAclManager
     */
    public function setBaseAclManager(AclSidInterface $baseAclManager)
    {
        $this->baseAclManager = $baseAclManager;
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidAclManagerException
     */
    public function getSid($identity)
    {
        if ($this->baseAclManager instanceof AclSidInterface) {
            return $this->baseAclManager->getSid($identity);
        }

        throw new InvalidAclManagerException('Base Acl Manager should be defined');
    }
}
