<?php

namespace Oro\Bundle\ActivityListBundle\Manager;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;

class ActivityListManager
{
    const STATE_CREATE = 'create';
    const STATE_UPDATE = 'update';

    /** @var ActivityListChainProvider */
    protected $chainProvider;

    /**
     * @param ActivityListChainProvider $chainProvider
     */
    public function __construct(ActivityListChainProvider $chainProvider)
    {
        $this->chainProvider = $chainProvider;
    }

    /**
     * Check if given entity supports by activity list providers
     *
     * @param $entity
     * @return bool
     */
    public function isSupportedEntity($entity)
    {
        return $this->chainProvider->isSupportedEntity($entity);
    }

    /**
     * @param               $deletedEntities
     * @param EntityManager $entityManager
     */
    public function processDeletedEntities($deletedEntities, EntityManager $entityManager)
    {
        if (!empty($deletedEntities)) {
            foreach ($deletedEntities as $entity) {
                $entityManager->getRepository('OroActivityListBundle:ActivityList')->createQueryBuilder('list')
                    ->delete()
                    ->where('list.relatedActivityClass = :relatedActivityClass')
                    ->andWhere('list.relatedActivityId = :relatedActivityId')
                    ->setParameter('relatedActivityClass', $entity['class'])
                    ->setParameter('relatedActivityId', $entity['id'])
                    ->getQuery()
                    ->execute();
            }
        }
    }

    /**
     * @param               $updatedEntities
     * @param EntityManager $entityManager
     * @return bool
     */
    public function processUpdatedEntities($updatedEntities, EntityManager $entityManager)
    {
        if (!empty($updatedEntities)) {
            foreach ($updatedEntities as $entity) {
                list($updateEntities, $deleteEntities) = $this->chainProvider->getUpdatedActivityLists($entity, $entityManager);

                if (!empty($updateEntities)) {
                    foreach ($updateEntities as $entity) {
                        $entityManager->persist($entity);
                    }

                }

                if (!empty($deleteEntities)) {
                    foreach ($updateEntities as $entity) {
                        $entityManager->remove($entity);
                    }

                }

            }
            return true;
        }

        return false;
    }

    /**
     * @param array         $insertedEntities
     * @param EntityManager $entityManager
     * @return bool
     */
    public function processInsertEntities($insertedEntities, EntityManager $entityManager)
    {
        if (!empty($insertedEntities)) {
            foreach ($insertedEntities as $entity) {
                $entityManager->persist($this->chainProvider->getActivityListEntitiesByActivityEntity($entity));
            }

            return true;
        }

        return false;
    }
}
