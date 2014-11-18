<?php

namespace Oro\Bundle\ActivityListBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\ActivityListBundle\Tools\ActivityListEntityConfigDumperExtension;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class ActivityListRepository extends EntityRepository
{
    /**
     * @param string         $entityClass
     * @param integer        $entityId
     * @param array          $activityClasses
     * @param \DateTime|bool $dateFrom
     * @param \DateTime|bool $dateTo
     * @param string         $orderField
     * @param string         $orderDirection
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
        $orderDirection = 'DESC'
    ) {
        $qb = $this->getBaseActivityListQueryBuilder($entityClass, $entityId, $orderField, $orderDirection);

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
     * @return QueryBuilder
     */
    public function getBaseActivityListQueryBuilder(
        $entityClass,
        $entityId,
        $orderField = 'updatedAt',
        $orderDirection = 'DESC'
    ) {
        $associationName = ExtendHelper::buildAssociationName(
            $entityClass,
            ActivityListEntityConfigDumperExtension::ASSOCIATION_KIND
        );

        return $this->createQueryBuilder('activity')
            ->join('activity.' . $associationName, 'r')
            ->where('r.id = :entityId')
            ->setParameter('entityId', $entityId)
            ->orderBy('activity.' . $orderField, $orderDirection);
    }
}
