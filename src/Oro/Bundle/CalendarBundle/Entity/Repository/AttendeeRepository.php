<?php

namespace Oro\Bundle\CalendarBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

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
     * @param array $calendarEventIds
     *
     * @return array
     */
    public function getAttendeeListsByCalendarEventIds(array $calendarEventIds)
    {
        if (!$calendarEventIds) {
            return [];
        }

        $qb = $this->createQueryBuilder('attendee');
        $queryResult = $qb
            ->select('attendee.displayName, attendee.email, attendee.createdAt, attendee.updatedAt')
            ->addSelect('attendee_status.id as status, attendee_type.id as type')
            ->addSelect('event.id as calendarEventId')
            ->join('attendee.calendarEvent', 'event')
            ->leftJoin('attendee.status', 'attendee_status')
            ->leftJoin('attendee.type', 'attendee_type')
            ->where($qb->expr()->in('event.id', ':calendar_event'))
            ->setParameter('calendar_event', $calendarEventIds)
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($queryResult as $row) {
            $calendarEventId = $row['calendarEventId'];
            unset($row['calendarEventId']);
            $result[$calendarEventId][] = $row;
        }

        return $result += array_fill_keys($calendarEventIds, []);
    }
}
