<?php

namespace Oro\Bundle\ActivityListBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\ActivityListBundle\Tools\ActivityListEntityConfigDumperExtension;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class ActivityListRepository extends EntityRepository
{
    /**
     * @param string         $entityClass     Target entity class
     * @param integer        $entityId        Target entity id
     * @param array          $activityClasses Selected activity types
     * @param \DateTime|bool $dateFrom        Date from
     * @param \DateTime|bool $dateTo          Date to
     * @param string         $orderField      Order by field
     * @param string         $orderDirection  Order direction
     * @param boolean        $grouping        Do grouping
     *
     * @return QueryBuilder
     */
    public function getActivityListQueryBuilder(
        $entityClass,
        $entityId,
        $activityClasses = [],
        $dateFrom = null,
        $dateTo = null,
        $orderField = 'updatedAt',
        $orderDirection = 'DESC',
        $grouping = false
    ) {
        $qb = $this->getBaseActivityListQueryBuilder($entityClass, $entityId, $orderField, $orderDirection, $grouping);

        if ($activityClasses) {
            $qb->andWhere($qb->expr()->in('activity.relatedActivityClass', ':activityClasses'))
                ->setParameter('activityClasses', $activityClasses);
        }

        if ($dateFrom) {
            if ($dateTo) {
                $qb->andWhere($qb->expr()->between('activity.updatedAt', ':dateFrom', ':dateTo'))
                    ->setParameter('dateTo', $dateTo);
            } else {
                $qb->andWhere('activity.updatedAt > :dateFrom');
            }
            $qb->setParameter('dateFrom', $dateFrom);
        }

        return $qb;
    }

    /**
     * @param string  $entityClass
     * @param integer $entityId
     * @param string  $orderField
     * @param string  $orderDirection
     * @param boolean $grouping
     *
     * @return QueryBuilder
     */
    public function getBaseActivityListQueryBuilder(
        $entityClass,
        $entityId,
        $orderField = 'updatedAt',
        $orderDirection = 'DESC',
        $grouping = false
    ) {
        $queryBuilder = $this->createQueryBuilder('activity')
            ->join('activity.' . $this->getAssociationName($entityClass), 'r')
            ->where('r.id = :entityId')
            ->setParameter('entityId', $entityId)
            ->orderBy('activity.' . $orderField, $orderDirection);

        if ($grouping) {
            $queryBuilder->andWhere('activity.head = 1');
        }

        return $queryBuilder;
    }

    /**
     * Delete activity lists by related activity data
     *
     * @param $class
     * @param $id
     */
    public function deleteActivityListsByRelatedActivityData($class, $id)
    {
        $this->createQueryBuilder('list')
            ->delete()
            ->where('list.relatedActivityClass = :relatedActivityClass')
            ->andWhere('list.relatedActivityId = :relatedActivityId')
            ->setParameter('relatedActivityClass', $class)
            ->setParameter('relatedActivityId', $id)
            ->getQuery()
            ->execute();
    }

    /**
     * Return count of activity list records for current target class name and record id
     *
     * @param string $className Target entity class name
     * @param int $entityId     Target entity id
     *
     * @return int              Number of activity list records
     */
    public function getRecordsCountForTargetClassAndId($className, $entityId)
    {
        // we need try/catch here to avoid crash on non exist entity relation
        try {
            $result = $this->createQueryBuilder('list')
                ->select('COUNT(list.id)')
                ->join('list.' . $this->getAssociationName($className), 'r')
                ->where('r.id = :entityId')
                ->setParameter('entityId', $entityId)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (\Exception $e) {
            $result = 0;
        }

        return $result;
    }

    /**
     * Get Association name
     *
     * @param string $className
     *
     * @return string
     */
    protected function getAssociationName($className)
    {
        return ExtendHelper::buildAssociationName(
            $className,
            ActivityListEntityConfigDumperExtension::ASSOCIATION_KIND
        );
    }
}
