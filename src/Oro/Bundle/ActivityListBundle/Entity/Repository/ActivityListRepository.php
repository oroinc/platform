<?php

namespace Oro\Bundle\ActivityListBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class ActivityListRepository extends EntityRepository
{
    /*
        Usage example in controller:

        $perPage = 5;
        $pager   = $this->get('oro_datagrid.extension.pager.orm.pager');
        $qb = $this->getDoctrine()->getManager()->getRepository('OroActivityListBundle:ActivityList')->getActivityListQueryBuilder(
            $request->get('class_name'),
            $request->get('entity_id')
        );
        $pager->setQueryBuilder($qb);
        $pager->setPage($request->get('page', 1));
        $pager->setMaxPerPage($perPage);
        $pager->init();
        $pager->getResults();
     * */
    /**
     * @param string    $entityClass
     * @param integer   $entityId
     * @param null      $activityClass
     * @param null      $activityId
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     *
     * @return QueryBuilder
     */
    public function getActivityListQueryBuilder(
        $entityClass,
        $entityId,
        $activityClass = null,
        $activityId = null,
        \DateTime $dateFrom = null,
        \DateTime $dateTo = null
    ) {
        $qb = $this->createQueryBuilder('activity')
            ->select('activity')
            ->where('activity.relatedEntityClass = :class')
            ->andWhere('activity.relatedEntityId = :entityId')
            ->setParameter('class', $entityClass)
            ->setParameter('entityId', $entityId)
            ->orderBy('activity.id', 'DESC');

        if ($activityClass) {
            $qb->andWhere('activity.relatedActivityClass = :activityClass')
                ->setParameter('activityClass', $activityClass);

            if ($activityId) {
                $qb->andWhere('activity.relatedActivityId = :activityId')
                    ->setParameter('activityId', $activityId);
            }
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
}
