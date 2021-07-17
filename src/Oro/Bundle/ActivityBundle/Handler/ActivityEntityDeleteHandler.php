<?php

namespace Oro\Bundle\ActivityBundle\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\ActivityBundle\Model\ActivityInterface;
use Oro\Bundle\SoapBundle\Model\RelationIdentifier;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * The delete handler for activity entity associations.
 */
class ActivityEntityDeleteHandler
{
    /** @var ManagerRegistry */
    private $doctrine;

    /** @var ActivityManager */
    private $activityManager;

    /** @var ActivityEntityDeleteHandlerExtensionInterface */
    private $extension;

    public function __construct(
        ManagerRegistry $doctrine,
        ActivityManager $activityManager,
        ActivityEntityDeleteHandlerExtensionInterface $extension
    ) {
        $this->doctrine = $doctrine;
        $this->activityManager = $activityManager;
        $this->extension = $extension;
    }

    /**
     * Deletes an activity entity associations that is represented by the given identifier.
     *
     * @param RelationIdentifier $id    The activity entity association identifier
     * @param bool               $flush Whether to call flush() method of an entity manager
     *
     * @throws EntityNotFoundException if an entity with the given id does not exist
     * @throws AccessDeniedException if the delete operation is forbidden
     */
    public function delete(RelationIdentifier $id, bool $flush = true): void
    {
        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass($id->getOwnerEntityClass());

        /** @var ActivityInterface|null $entity */
        $entity = $em->find($id->getOwnerEntityClass(), $id->getOwnerEntityId());
        if (!$entity) {
            throw new EntityNotFoundException();
        }

        $targetEntity = $em->find($id->getTargetEntityClass(), $id->getTargetEntityId());
        if (!$targetEntity) {
            throw new EntityNotFoundException();
        }

        $this->extension->assertDeleteGranted($entity, $targetEntity);

        $this->activityManager->removeActivityTarget($entity, $targetEntity);

        if ($flush) {
            $em->flush();
        }
    }
}
