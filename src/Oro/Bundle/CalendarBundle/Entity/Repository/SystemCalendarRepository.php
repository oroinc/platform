<?php

namespace Oro\Bundle\CalendarBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class SystemCalendarRepository extends EntityRepository
{
    /**
     * Returns a query builder which can be used to get list of system calendars by list of calendar IDs
     *
     * @param int[] $calendarIds
     *
     * @return QueryBuilder
     */
    public function getSystemCalendarsByIdsQueryBuilder($calendarIds)
    {
        $qb = $this->createQueryBuilder('sc')
            ->select('sc')
            ->where('sc.public = :public')
            ->setParameter('public', false);

        if ($calendarIds) {
            $qb->andWhere($qb->expr()->in('sc.id', $calendarIds));
        } else {
            $qb->andWhere('1 = 0');
        }

        return $qb;
    }

    /**
     * Returns a query builder which can be used to get list of system calendars
     *
     * @param int $organizationId
     *
     * @return QueryBuilder
     */
    public function getSystemCalendarsQueryBuilder($organizationId)
    {
        return $this->createQueryBuilder('sc')
            ->select('sc')
            ->where('sc.organization = :organizationId AND sc.public = :public')
            ->setParameter('organizationId', $organizationId)
            ->setParameter('public', false);
    }

    /**
     * Returns a query builder which can be used to get list of public calendars
     *
     * @return QueryBuilder
     */
    public function getPublicCalendarsQueryBuilder()
    {
        return $this->createQueryBuilder('sc')
            ->select('sc')
            ->where('sc.public = :public')
            ->setParameter('public', true);
    }

    /**
     * Returns a query builder which can be used to get list of both system and public calendars
     *
     * @param int $organizationId
     *
     * @return QueryBuilder
     */
    public function getCalendarsQueryBuilder($organizationId)
    {
        return $this->createQueryBuilder('sc')
            ->select('sc')
            ->where('sc.organization = :organizationId OR sc.public = :public')
            ->setParameter('organizationId', $organizationId)
            ->setParameter('public', true);
    }
}
