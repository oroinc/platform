<?php

namespace Oro\Bundle\CalendarBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\AbstractQuery;

class SystemCalendarRepository extends EntityRepository
{
    /**
     * Gets QueryBuilder
     *
     * @param int[] $calendarIds
     * @param bool  $public
     *
     * @return AbstractQuery
     */
    public function getCalendarsByIdsQuery($calendarIds, $public = false)
    {
        $qb = $this->createQueryBuilder('sc')
            ->select('sc');
        $qb
            ->where($qb->expr()->in('sc.id', $calendarIds))
            ->andWhere('sc.public = :public')
            ->setParameter('public', $public);

        return $qb->getQuery();
    }
}
