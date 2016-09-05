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
        $grouping = false
    ) {
        $qb = $this->getBaseActivityListQueryBuilder($entityClass, $entityId, $grouping);

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
     * @param integer|integer[] $entityIds
     * @param boolean $grouping
     *
     * @return QueryBuilder
     */
    public function getBaseActivityListQueryBuilder(
        $entityClass,
        $entityIds,
        $grouping = false
    ) {
        if (is_scalar($entityIds)) {
            $entityIds = [$entityIds];
        }
        $queryBuilder = $this->createQueryBuilder('activity')
            ->leftJoin('activity.' . $this->getAssociationName($entityClass), 'r')
            ->leftJoin('activity.activityOwners', 'ao');
        if (count($entityIds) > 1) {
            $queryBuilder
                ->where('r.id IN (:entityIds)')
                ->setParameter('entityIds', $entityIds);
        } else {
            $queryBuilder
                ->where('r.id = :entityId')
                ->setParameter('entityId', reset($entityIds));
        }

        if ($grouping) {
            $queryBuilder->andWhere('activity.head = true');
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
     * @param array $types      Activity types
     *
     * @return int              Number of activity list records
     */
    public function getRecordsCountForTargetClassAndId($className, $entityId, $types = [])
    {
        // we need try/catch here to avoid crash on non exist entity relation
        try {
            $qb = $this->createQueryBuilder('list')
                ->select('COUNT(list.id)')
                ->join('list.' . $this->getAssociationName($className), 'r')
                ->where('r.id = :entityId')
                ->setParameter('entityId', $entityId);
            if (count($types) > 0) {
                $orX = $qb->expr()->orX();
                foreach ($types as $type) {
                    $orX->add('list.relatedActivityClass = :relatedActivityClass');
                    $qb->setParameter('relatedActivityClass', $type);
                }
                $qb->andWhere($orX);
            }
            $result = $qb->getQuery()
                ->getSingleScalarResult();
        } catch (\Exception $e) {
            $result = 0;
        }

        return $result;
    }

    /**
     * Return count of activity list records for current target class name
     *
     * @param string $className Target entity class name
     *
     * @return int Number of activity list records
     */
    public function getRecordsCountForTargetClass($className)
    {
        // we need try/catch here to avoid crash on non exist entity relation
        try {
            $result = $this->createQueryBuilder('list')
                ->select('COUNT(list.id)')
                ->join('list.' . $this->getAssociationName($className), 'r')
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

    /**
     * @param $entityClass
     * @param $entityId
     * @param $activityClass
     * @return QueryBuilder
     */
    public function getActivityListQueryBuilderByActivityClass($entityClass, $entityId, $activityClass)
    {
        return $this->getBaseActivityListQueryBuilder($entityClass, $entityId)
            ->select('activity.relatedActivityId, activity.id')
            ->andWhere('activity.relatedActivityClass = :activityClass')
            ->setParameter('activityClass', $activityClass);
    }
}
