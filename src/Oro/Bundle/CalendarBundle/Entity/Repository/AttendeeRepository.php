<?php

namespace Oro\Bundle\CalendarBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;

class AttendeeRepository extends EntityRepository
{
    /**
     * @param CalendarEvent|int $calendarEvent
     *
     * @return array
     */
    public function getAttendeeList($calendarEvent)
    {
        return $this->createQueryBuilder('a')
            ->select('a.displayName, a.email, a.createdAt, a.updatedAt, o.id AS origin, s.id as status, t.id as type')
            ->leftJoin('a.origin', 'o')
            ->leftJoin('a.status', 's')
            ->leftJoin('a.type', 't')
            ->where('a.calendarEvent = :calendar_event')
            ->setParameter('calendar_event', $calendarEvent)
            ->getQuery()
            ->getArrayResult();
    }
}
