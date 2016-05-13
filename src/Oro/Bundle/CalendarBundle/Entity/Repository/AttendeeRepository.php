<?php

namespace Oro\Bundle\CalendarBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;

class AttendeeRepository extends EntityRepository
{
    /**
     * @param string|null $query
     * @param int|null $limit
     *
     * @return array
     */
    public function getEmailRecipients($query = null, $limit = null)
    {
        $subQb = $this->createQueryBuilder('sa')
            ->select('MIN(sa.id)')
            ->groupBy('sa.email, sa.displayName');

        if ($limit) {
            $subQb->setMaxResults($limit);
        }

        $qb = $this->createQueryBuilder('a');

        if ($query) {
            $subQb
                ->andWhere($subQb->expr()->orX(
                    $subQb->expr()->like('a.displayName', ':query'),
                    $subQb->expr()->like('a.email', ':query')
                ));
            $qb->setParameter('query', sprintf('%%%s%%', $query));
        }

        $qb
            ->select('a.id as entityId, a.email, a.displayName AS name, o.name AS organization')
            ->join('a.calendarEvent', 'e')
            ->join('e.calendar', 'c')
            ->join('c.organization', 'o')
            ->where($qb->expr()->in('a.id', $subQb->getDQL()));

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
}
