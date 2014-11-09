<?php

namespace Oro\Bundle\ActivityListBundle\Entity\Manager;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Entity\Repository\ActivityListRepository;
use Oro\Bundle\DataGridBundle\Extension\Pager\Orm\Pager;
use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;

class ActivityListManager
{
    const STATE_CREATE = 'create';
    const STATE_UPDATE = 'update';

    /** @var EntityManager */
    protected $em;

    /** @var Pager */
    protected $pager;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var NameFormatter */
    protected $nameFormatter;

    /** @var ActivityListChainProvider */
    protected $chainProvider;

    /**
     * @ param Registry       $doctrine
     * @param SecurityFacade $securityFacade
     * @param NameFormatter  $nameFormatter
     * @param Pager          $pager
     * @param ActivityListChainProvider $chainProvider
     */
    public function __construct(
        //Registry $doctrine,
        SecurityFacade $securityFacade,
        NameFormatter $nameFormatter,
        Pager $pager,
        ActivityListChainProvider $chainProvider
    ) {
        //$this->em             = $doctrine->getManager();
        $this->securityFacade = $securityFacade;
        $this->nameFormatter  = $nameFormatter;
        $this->pager          = $pager;
        $this->chainProvider  = $chainProvider;
    }

    /**
     * @return ActivityListRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository(ActivityList::ENTITY_NAME);
    }

    /**
     * @param string    $entityClass
     * @param integer   $entityId
     * @param array     $activityEntityСlasses
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param integer   $page
     * @param integer   $limit
     *
     * @internal param array $activityClasses
     * @return ActivityList[]
     */
    public function getList(
        $entityClass,
        $entityId,
        $activityEntityСlasses,
        $dateFrom,
        $dateTo,
        $page,
        $limit
    ) {
        /** @var QueryBuilder $qb */
        $qb = $this->getRepository()->getActivityListQueryBuilder(
            $entityClass,
            $entityId,
            $activityEntityСlasses,
            $dateFrom,
            $dateTo
        );

        $pager = $this->pager;
        $pager->setQueryBuilder($qb);
        $pager->setPage($page);
        $pager->setMaxPerPage($limit);
        $pager->init();

        /** @var ActivityList[] $result */
        $results = $pager->getResults();

        $results = $this->getEntityViewModels($results);

        return $results;
    }

    /**
     * @param ActivityList[] $entities
     *
     * @return array
     */
    public function getEntityViewModels($entities)
    {
        $result = [];
        foreach ($entities as $entity) {
            $result[] = $this->getEntityViewModel($entity);
        }
        return $result;
    }

    /**
     * @param ActivityList $entity
     *
     * @return array
     */
    public function getEntityViewModel(ActivityList $entity)
    {
        $result = [
            'id'                   => $entity->getId(),
            'owner'                => $this->nameFormatter->format($entity->getOwner()),
            'owner_id'             => $entity->getOwner()->getId(),
            'verb'                 => $entity->getVerb(),
            'subject'              => $entity->getSubject(),
            'data'                 => $entity->getData(),
            //'relatedEntityClass'   => $entity->getRelatedEntityClass(),
            //'relatedEntityId'      => $entity->getRelatedEntityId(),
            'relatedActivityClass' => $entity->getRelatedActivityClass(),
            'relatedActivityId'    => $entity->getRelatedActivityId(),
            'createdAt'            => $entity->getCreatedAt()->format('c'),
            'updatedAt'            => $entity->getUpdatedAt()->format('c'),
            'hasUpdate'            => $entity->getCreatedAt() != $entity->getUpdatedAt(),
            'editable'             => $this->securityFacade->isGranted('EDIT', $entity),
            'removable'            => $this->securityFacade->isGranted('DELETE', $entity),
        ];

        return $result;
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
     * @param array         $deletedEntities
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
     * @param array         $updatedEntities
     * @param EntityManager $entityManager
     * @return bool
     */
    public function processUpdatedEntities($updatedEntities, EntityManager $entityManager)
    {
        if (!empty($updatedEntities)) {
            foreach ($updatedEntities as $entity) {
                $entityManager->persist($this->chainProvider->getUpdatedActivityList($entity, $entityManager));
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
