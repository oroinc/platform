<?php

namespace Oro\Bundle\ActivityBundle\Entity\Manager;

use Doctrine\ORM\EntityNotFoundException;

use Oro\Bundle\ActivityBundle\Model\ActivityInterface;
use Oro\Bundle\SecurityBundle\Exception\ForbiddenException;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Bundle\SoapBundle\Handler\DeleteHandler;
use Oro\Bundle\SoapBundle\Model\RelationIdentifier;

class ActivityEntityDeleteHandler extends DeleteHandler
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param SecurityFacade $securityFacade
     */
    public function setSecurityFacade(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }

    /**
     * Handle delete entity object.
     *
     * @param RelationIdentifier $id
     * @param ApiEntityManager   $manager
     *
     * @throws EntityNotFoundException if an entity with the given id does not exist
     * @throws ForbiddenException if a delete operation is forbidden
     */
    public function handleDelete($id, ApiEntityManager $manager)
    {
        $em = $manager->getObjectManager();

        /** @var ActivityInterface $entity */
        $entity = $em->find($id->getOwnerEntityClass(), $id->getOwnerEntityId());
        if (!$entity) {
            throw new EntityNotFoundException();
        }
        if (!$this->securityFacade->isGranted('EDIT', $entity)) {
            throw new ForbiddenException('has no edit permissions for activity entity');
        }

        $targetEntity = $em->find($id->getTargetEntityClass(), $id->getTargetEntityId());
        if (!$targetEntity) {
            throw new EntityNotFoundException();
        }
        if (!$this->securityFacade->isGranted('VIEW', $targetEntity)) {
            throw new ForbiddenException('has no view permissions for related entity');
        }

        $entity->removeActivityTarget($targetEntity);

        $em->flush();
    }
}
