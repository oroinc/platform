<?php

namespace Oro\Bundle\ActivityListBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Model\ActivityListInterface;

class ActivityListListener
{
    const STATE_CREATE = 'create';
    const STATE_UPDATE = 'update';

    /**  @var array */
    protected $insertedEntities = [];

    /**  @var array */
    protected $updatedEntities = [];

    /**  @var array */
    protected $deletedEntities = [];

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * Collect activities changes
     *
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $entityManager = $args->getEntityManager();
        $unitOfWork = $entityManager->getUnitOfWork();

        $this->collectEntities($this->insertedEntities, $unitOfWork->getScheduledEntityInsertions());
        $this->collectEntities($this->updatedEntities, $unitOfWork->getScheduledEntityUpdates());
        $this->collectDeletedEntities($unitOfWork->getScheduledEntityDeletions());
    }

    /**
     * Save collected changes
     *
     * @param PostFlushEventArgs $args
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        /** @var  $entityManager */
        $entityManager = $args->getEntityManager();
        $this->processInsertEntities($entityManager);
        $this->processUpdatedEntities($entityManager);
        $this->processDeletedEntities($entityManager);
    }

    /**
     * We should collect here id's because after flush, object has no id
     *
     * @param $entities
     */
    protected function collectDeletedEntities($entities)
    {
        if (!empty($entities)) {
            foreach ($entities as $hash=> $entity) {
                if ($entity instanceof ActivityListInterface && empty($this->deletedEntities[$hash])) {
                    $this->deletedEntities[$hash] = [
                        'class' => $this->doctrineHelper->getEntityClass($entity),
                        'id' => $this->doctrineHelper->getSingleEntityIdentifier($entity)
                    ];
                }
            }
        }
    }

    /**
     * Delete activity lists on delete activities
     *
     * @param EntityManager $entityManager
     */
    protected function processDeletedEntities(EntityManager $entityManager)
    {
        if (!empty($this->deletedEntities)) {
            foreach ($this->deletedEntities as $entity) {
                $entityManager->getRepository('OroActivityListBundle:ActivityList')->createQueryBuilder('list')
                    ->delete()
                    ->where('list.relatedActivityClass = :relatedActivityClass')
                    ->andWhere('list.relatedActivityId = :relatedActivityId')
                    ->setParameter('relatedEntityClass', $entity['class'])
                    ->setParameter('relatedEntityId', $entity['id'])
                    ->getQuery()
                    ->execute();
            }
            $this->deletedEntities = [];
        }
    }

    /**
     * Update Activity lists
     *
     * @param EntityManager $entityManager
     */
    protected function processUpdatedEntities(EntityManager $entityManager)
    {
        if (!empty($this->updatedEntities)) {
            foreach ($this->updatedEntities as $entity) {
                $qb = $entityManager->getRepository('OroActivityListBundle:ActivityList')->createQueryBuilder('list');
                $qb->update()
                    ->set('list.verb', $qb->expr()->literal(self::STATE_UPDATE))
                    ->set('list.subject', $qb->expr()->literal($entity->getActivityListSubject()))
                    ->set('list.updatedAt', $qb->expr()->literal(new \DateTime('now', new \DateTimeZone('UTC'))))
                    ->where('list.relatedActivityClass = :relatedActivityClass')
                    ->andWhere('list.relatedActivityId = :relatedActivityId')
                    ->setParameter('relatedActivityClass', $this->doctrineHelper->getEntityClass($entity))
                    ->setParameter('relatedActivityId', $this->doctrineHelper->getSingleEntityIdentifier($entity))
                    ->getQuery()
                    ->execute();
            }
            $this->updatedEntities = [];
        }
    }

    /**
     * Process new records.
     *
     * @param EntityManager $entityManager
     */
    protected function processInsertEntities(EntityManager $entityManager)
    {
        if (!empty($this->insertedEntities)) {
            foreach ($this->insertedEntities as $entity) {
                $activityList = new ActivityList();
                $activityList->setVerb(self::STATE_CREATE);
                $activityList->setRelatedActivityClass($this->doctrineHelper->getEntityClass($entity));
                $activityList->setRelatedActivityId($this->doctrineHelper->getSingleEntityIdentifier($entity));
                $activityList->setSubject($entity->getActivityListSubject());
                $entityManager->persist($activityList);
            }
            $this->insertedEntities = [];
            $entityManager->flush();
        }
    }

    /**
     * Collect inserted or updated activities
     *
     * @param array $storage
     * @param array $entities
     */
    protected function collectEntities(array &$storage, array $entities)
    {
        foreach ($entities as $hash => $entity) {
            if ($entity instanceof ActivityListInterface && empty($storage[$hash])) {
                $storage[$hash] = $entity;
            }
        }
    }
}
