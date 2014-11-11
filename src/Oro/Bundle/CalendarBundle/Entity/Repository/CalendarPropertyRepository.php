<?php

namespace Oro\Bundle\CalendarBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class CalendarPropertyRepository extends EntityRepository
{
    /**
     * @param int         $targetCalendarId
     * @param string|null $alias
     *
     * @return QueryBuilder
     */
    public function getConnectionsByTargetCalendarQueryBuilder($targetCalendarId, $alias = null)
    {
        $qb = $this->createQueryBuilder('connection')
            ->where('connection.targetCalendar = :targetCalendarId')
            ->setParameter('targetCalendarId', $targetCalendarId);
        if ($alias) {
            $qb
                ->andWhere('connection.calendarAlias = :alias')
                ->setParameter('alias', $alias);
        }

        return $qb;
    }
}
