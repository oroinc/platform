<?php

namespace Oro\Bundle\OrganizationBundle\Handler;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Exception\ForbiddenException;
use Oro\Bundle\SoapBundle\Handler\DeleteHandler;

class OrganizationDeleteHandler extends DeleteHandler
{
    /**
     * {@inheritdoc}
     */
    protected function checkPermissions($entity, ObjectManager $em)
    {
        /** @var $entity Organization */
        parent::checkPermissions($entity, $em);
        if ($this->ownerDeletionManager->hasOrganizationAssignments($entity)) {
            throw new ForbiddenException('organization has assignments');
        }
    }
}
