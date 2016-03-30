<?php

namespace Oro\Bundle\SoapBundle\Handler;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityNotFoundException;
use Oro\Bundle\SecurityBundle\Exception\ForbiddenException;
use Oro\Bundle\OrganizationBundle\Ownership\OwnerDeletionManager;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;

/**
 * A class encapsulates a business logic responsible to delete entity
 */
class DeleteHandler
{
    /**
     * @var OwnerDeletionManager
     */
    protected $ownerDeletionManager;

    /**
     * Sets an owner deletion manager
     *
     * @param OwnerDeletionManager $ownerDeletionManager
     */
    public function setOwnerDeletionManager(OwnerDeletionManager $ownerDeletionManager)
    {
        $this->ownerDeletionManager = $ownerDeletionManager;
    }

    /**
     * Handle delete entity object.
     *
     * @param mixed            $id
     * @param ApiEntityManager $manager
     * @throws EntityNotFoundException if an entity with the given id does not exist
     * @throws ForbiddenException if a delete operation is forbidden
     */
    public function handleDelete($id, ApiEntityManager $manager)
    {
        $entity = $manager->find($id);
        if (!$entity) {
            throw new EntityNotFoundException();
        }

        $em = $manager->getObjectManager();
        $this->processDelete($entity, $em);
    }

    /**
     * Deletes given entity object.
     *
     * @param object        $entity
     * @param ObjectManager $em
     */
    public function processDelete($entity, ObjectManager $em)
    {
        $this->checkPermissions($entity, $em);
        $this->deleteEntity($entity, $em);
        $em->flush();
    }

    /**
     * Checks if a delete operation is allowed
     *
     * @param object        $entity
     * @param ObjectManager $em
     * @throws ForbiddenException if a delete operation is forbidden
     */
    protected function checkPermissions($entity, ObjectManager $em)
    {
        if ($this->ownerDeletionManager->isOwner($entity) && $this->ownerDeletionManager->hasAssignments($entity)) {
            throw new ForbiddenException('has assignments');
        }
    }

    /**
     * Deletes the given entity
     *
     * @param object        $entity
     * @param ObjectManager $em
     */
    protected function deleteEntity($entity, ObjectManager $em)
    {
        $em->remove($entity);
    }
}
