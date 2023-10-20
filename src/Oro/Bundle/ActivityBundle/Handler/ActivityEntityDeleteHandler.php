<?php

namespace Oro\Bundle\ActivityBundle\Handler;

use Doctrine\Common\Util\ClassUtils;
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
    private ManagerRegistry $doctrine;
    private ActivityManager $activityManager;
    private ActivityEntityDeleteHandlerExtensionInterface $extension;

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
     * Deletes an activity entity association that is represented by the given identifier.
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

        $this->deleteActivityAssociation($entity, $targetEntity, $flush);
    }

    /**
     * Deletes an association between the given activity entity and the given target entity.
     *
     * @param ActivityInterface $activityEntity The activity entity
     * @param object            $targetEntity   The entity associated with the activity entity
     * @param bool              $flush          Whether to call flush() method of an entity manager
     *
     * @return bool TRUE if the association was removed; otherwise, FALSE
     *
     * @throws AccessDeniedException if the delete operation is forbidden
     */
    public function deleteActivityAssociation(
        ActivityInterface $activityEntity,
        object $targetEntity,
        bool $flush = true
    ): bool {
        $this->extension->assertDeleteGranted($activityEntity, $targetEntity);

        $result = $this->activityManager->removeActivityTarget($activityEntity, $targetEntity);

        if ($flush) {
            $this->doctrine->getManagerForClass(ClassUtils::getClass($activityEntity))->flush();
        }

        return $result;
    }
}
