<?php

namespace Oro\Bundle\UserBundle\Handler;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\SoapBundle\Handler\DeleteHandler;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclSidManager;
use Oro\Bundle\SecurityBundle\Exception\ForbiddenException;
use Oro\Bundle\UserBundle\Entity\Repository\RoleRepository;

class RoleDeleteHandler extends DeleteHandler
{
    /**
     * @var AclSidManager
     */
    protected $aclSidManager;

    /**
     * Constructor
     *
     * @param AclSidManager $aclSidManager
     */
    public function __construct(AclSidManager $aclSidManager)
    {
        $this->aclSidManager = $aclSidManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function checkPermissions($entity, ObjectManager $em)
    {
        parent::checkPermissions($entity, $em);
        /** @var RoleRepository $roleRepo */
        $roleRepo = $em->getRepository('OroUserBundle:Role');
        if ($roleRepo->hasAssignedUsers($entity)) {
            throw new ForbiddenException('has users');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function deleteEntity($entity, ObjectManager $em)
    {
        parent::deleteEntity($entity, $em);
        if ($this->aclSidManager->isAclEnabled()) {
            $this->aclSidManager->deleteSid($this->aclSidManager->getSid($entity));
        }
    }
}
