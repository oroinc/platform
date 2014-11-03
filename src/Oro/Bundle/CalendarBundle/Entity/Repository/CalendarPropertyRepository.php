<?php

namespace Oro\Bundle\CalendarBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class CalendarPropertyRepository extends EntityRepository
{
    /**
     * Returns a query builder which can be used to get a list of calendars
     * connected to to the given target calendar
     *
     * @param int    $targetCalendarId
     * @param string $calendarAlias
     *
     * @return QueryBuilder
     */
    public function getConnectedCalendarsQueryBuilder($targetCalendarId, $calendarAlias)
    {
        return $this->createQueryBuilder('connected')
            ->select('connected.calendar')
            ->where('connected.targetCalendar = :targetCalendarId AND connected.calendarAlias = :calendarAlias')
            ->setParameter('targetCalendarId', $targetCalendarId)
            ->setParameter('calendarAlias', $calendarAlias);
    }
}
