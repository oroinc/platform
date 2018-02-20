<?php

namespace Oro\Bundle\NoteBundle\Entity\Manager;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\ActivityBundle\Entity\Manager\ActivityEntityDeleteHandler;
use Oro\Bundle\ActivityBundle\Exception\InvalidArgumentException;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\NoteBundle\Entity\Note;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager as ActivityApiEntityManager;
use Oro\Bundle\SoapBundle\Model\RelationIdentifier;

class NoteActivityEntityDeleteHandler extends ActivityEntityDeleteHandler
{
    /**
     * @var ActivityManager
     */
    protected $activityManager;

    /**
     * {@inheritdoc}
     */
    public function handleDelete($id, ActivityApiEntityManager $manager)
    {
        /** @var EntityManager $em */
        $em = $manager->getObjectManager();

        /** @var Note $entity */
        $entity = $em->find($id->getOwnerEntityClass(), $id->getOwnerEntityId());
        if ($entity) {
            $targetEntities = $entity->getActivityTargetEntities();
            if (count($targetEntities) == 1 && $this->isTargetRequestedForDelete(reset($targetEntities), $id)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'The last activity target entity of %s could not be removed.',
                        Note::class
                    )
                );
            }
        }

        parent::handleDelete($id, $manager);
    }

    /**
     * @param object             $targetEntity
     * @param RelationIdentifier $id
     *
     * @return bool
     */
    protected function isTargetRequestedForDelete($targetEntity, RelationIdentifier $id)
    {
        return ClassUtils::getClass($targetEntity) == $id->getTargetEntityClass()
            && $targetEntity->getId() == $id->getTargetEntityId();
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(RelationIdentifier $relationIdentifier)
    {
        return $relationIdentifier->getOwnerEntityClass() == Note::class;
    }
}
