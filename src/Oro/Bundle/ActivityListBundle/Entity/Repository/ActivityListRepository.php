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
     * @param array     $activityClasses
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     *
     * @return QueryBuilder
     */
    public function getActivityListQueryBuilder(
        $entityClass,
        $entityId,
        $activityClasses = array(),
        \DateTime $dateFrom = null,
        \DateTime $dateTo = null
    ) {
        $qb = $this->createQueryBuilder('activity')
            ->where('activity.relatedEntityClass = :class')
            ->andWhere('activity.relatedEntityId = :entityId')
            ->setParameter('class', $entityClass)
            ->setParameter('entityId', $entityId)
            ->orderBy('activity.id', 'DESC');

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
}
