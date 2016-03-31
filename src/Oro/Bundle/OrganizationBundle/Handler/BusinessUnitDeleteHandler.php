<?php

namespace Oro\Bundle\OrganizationBundle\Handler;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\SecurityBundle\Exception\ForbiddenException;
use Oro\Bundle\SoapBundle\Handler\DeleteHandler;

class BusinessUnitDeleteHandler extends DeleteHandler
{
    /**
     * {@inheritdoc}
     */
    protected function checkPermissions($entity, ObjectManager $em)
    {
        parent::checkPermissions($entity, $em);
        $repository = $em->getRepository('OroOrganizationBundle:BusinessUnit');
        if ($repository->getBusinessUnitsCount() <= 1) {
            throw new ForbiddenException('Unable to remove the last business unit');
        }
    }
}
