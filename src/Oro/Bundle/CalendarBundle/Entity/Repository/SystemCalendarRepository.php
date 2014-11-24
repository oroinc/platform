<?php

namespace Oro\Bundle\CalendarBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class SystemCalendarRepository extends EntityRepository
{
    /**
     * Gets QueryBuilder
     *
     * @param int[] $calendarIds
     * @param bool  $public
     *
     * @return QueryBuilder
     */
    public function getSystemCalendarsByIdsQueryBuilder($calendarIds, $public = false)
    {
        $qb = $this->createQueryBuilder('sc')
            ->select('sc')
            ->where('sc.public = :public')
            ->setParameter('public', $public);

        if (!empty($calendarIds) && !$public) {
            $qb
                ->andWhere($qb->expr()->in('sc.id', $calendarIds));
        } elseif (!$public) {
            $qb
                ->andWhere('1 = 0');
        }

        return $qb;
    }
}
