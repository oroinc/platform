<?php

namespace Oro\Bundle\CalendarBundle\Entity\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class CalendarEventRepository extends EntityRepository
{
    /**
     * Returns a query builder which can be used to get a list of calendar events filtered by start and end dates
     *
     * @param int            $calendarId
     * @param \DateTime      $startDate       Start date
     * @param \DateTime      $endDate         End date
     * @param bool           $withConnections If true events from connected calendars will be returned as well
     * @param array|Criteria $filters         Additional filtering criteria, e.g. ['allDay' => true, ...]
     *                                        or \Doctrine\Common\Collections\Criteria
     *
     * @return QueryBuilder
     */
    public function getEventListByTimeIntervalQueryBuilder(
        $calendarId,
        $startDate,
        $endDate,
        $withConnections = false,
        $filters = []
    ) {
        $qb = $this->getEventListQueryBuilder($calendarId, $withConnections, $filters);

        /** @var QueryBuilder $qb */
        $qb
            ->andWhere(
                '(e.start < :start AND e.end >= :start) OR '
                . '(e.start <= :end AND e.end > :end) OR'
                . '(e.start >= :start AND e.end < :end)'
            )
            ->orderBy('c.id, e.start')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate);

        return $qb;
    }

    /**
     * Returns a query builder which can be used to get a list of calendar events
     *
     * @param int            $calendarId
     * @param bool           $withConnections If true events from connected calendars will be returned as well
     * @param array|Criteria $filters         Additional filtering criteria, e.g. ['allDay' => true, ...]
     *                                        or \Doctrine\Common\Collections\Criteria
     *
     * @return QueryBuilder
     */
    public function getEventListQueryBuilder(
        $calendarId,
        $withConnections = false,
        $filters = []
    ) {
        /** @var QueryBuilder $qb */
        $qb = $this->createQueryBuilder('e')
            ->select(
                'c.id as calendar, e.id, e.title, e.description, e.start, e.end, e.allDay, e.createdAt, e.updatedAt'
            )
            ->innerJoin('e.calendar', 'c');
        if (is_array($filters)) {
            $newCriteria = new Criteria();
            foreach ($filters as $fieldName => $value) {
                $newCriteria->andWhere(Criteria::expr()->eq($fieldName, $value));
            }

            $filters = $newCriteria;
        }
        if ($filters) {
            $qb->addCriteria($filters);
        }
        if ($withConnections) {
            $calendarRepo  = $this->getEntityManager()->getRepository('OroCalendarBundle:Calendar');
            $qbConnections = $calendarRepo->createQueryBuilder('c1')
                ->select('ac.id')
                ->innerJoin('c1.connections', 'a')
                ->innerJoin('a.connectedCalendar', 'ac')
                ->where('c1.id = :id')
                ->setParameter('id', $calendarId);

            $qb
                ->andWhere($qb->expr()->in('c.id', $qbConnections->getDQL()))
                ->setParameter('id', $calendarId);
        } else {
            $qb
                ->andWhere('c.id = :id')
                ->setParameter('id', $calendarId);
        }

        return $qb;
    }
}
