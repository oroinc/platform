<?php

namespace Oro\Bundle\UserBundle\Handler;

use Oro\Bundle\EntityBundle\Handler\AbstractEntityDeleteHandlerExtension;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclSidManager;
use Oro\Bundle\UserBundle\Entity\Repository\RoleRepository;
use Oro\Bundle\UserBundle\Entity\Role;

/**
 * The delete handler extension for Role entity.
 */
class RoleDeleteHandlerExtension extends AbstractEntityDeleteHandlerExtension
{
    /** @var AclSidManager */
    private $aclSidManager;

    public function __construct(AclSidManager $aclSidManager)
    {
        $this->aclSidManager = $aclSidManager;
    }

    /**
     * {@inheritdoc}
     */
    public function assertDeleteGranted($entity): void
    {
        /** @var Role $entity */

        /** @var RoleRepository $repo */
        $repo = $this->getEntityRepository(Role::class);
        if ($repo->hasAssignedUsers($entity)) {
            throw $this->createAccessDeniedException('has users');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function postFlush($entity, array $options): void
    {
        if ($this->aclSidManager->isAclEnabled()) {
            $this->aclSidManager->deleteSid($this->aclSidManager->getSid($entity));
        }
    }
}
