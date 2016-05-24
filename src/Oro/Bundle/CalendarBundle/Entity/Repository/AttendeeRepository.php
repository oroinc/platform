<?php

namespace Oro\Bundle\CalendarBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class AttendeeRepository extends EntityRepository
{
    /**
     * @param Organization|null $organization
     * @param string|null $query
     * @param int|null $limit
     *
     * @return array
     */
    public function getEmailRecipients(
        Organization $organization = null,
        $query = null,
        $limit = null
    ) {
        $qb = $this->createQueryBuilder('a')
            ->select('a.email, a.displayName AS name')
            ->groupBy('a.email, a.displayName');

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        if ($query) {
            $qb
                ->andWhere($qb->expr()->orX(
                    $qb->expr()->like('a.displayName', ':query'),
                    $qb->expr()->like('a.email', ':query')
                ))
                ->setParameter('query', sprintf('%%%s%%', $query));
        }

        if ($organization) {
            $qb
                ->join('a.calendarEvent', 'e')
                ->join('e.calendar', 'c')
                ->join('c.organization', 'o')
                ->andWhere('o.id = :organization');
            $qb->setParameter('organization', $organization);
        }

        return $qb->getQuery()
            ->getArrayResult();
    }

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

    /**
     * @param int $id
     *
     * @return Attendee[]
     */
    public function getAttendeesByCalendarEventId($id)
    {
        $qb = $this->createQueryBuilder('a')
            ->select('a')
            ->innerJoin('a.calendarEvent', 'calendarEvent')
            ->where('calendarEvent.id= :calendar_event_id')
            ->setParameter('calendar_event_id', $id);

        $query = $qb->getQuery();

        return $query->getResult();
    }
}
