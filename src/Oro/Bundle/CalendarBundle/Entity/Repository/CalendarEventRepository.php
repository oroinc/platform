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
     * @param \DateTime      $startDate
     * @param \DateTime      $endDate
     * @param bool           $subordinate If true events from connected calendars will be returned as well
     * @param array|Criteria $filters     Additional filtering criteria, e.g. ['allDay' => true, ...]
     *                                    or \Doctrine\Common\Collections\Criteria
     * @param string         $kind        user|system|null by default user
     *
     * @return QueryBuilder
     */
    public function getEventListByTimeIntervalQueryBuilder(
        $calendarId,
        $startDate,
        $endDate,
        $subordinate = false,
        $filters = [],
        $kind = 'user'
    ) {
        /** @var QueryBuilder $qb */
        $qb = $this->getEventListQueryBuilder($calendarId, $subordinate, $filters, $kind);

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
     * @param bool           $subordinate If true events from connected calendars will be returned as well
     * @param array|Criteria $filters     Additional filtering criteria, e.g. ['allDay' => true, ...]
     *                                    or \Doctrine\Common\Collections\Criteria
     * @param string         $kind        user|system|null by default user
     *
     * @return QueryBuilder
     */
    public function getEventListQueryBuilder(
        $calendarId,
        $subordinate = false,
        $filters = [],
        $kind = 'user'
    ) {
        /** @var QueryBuilder $qb */
        $qb = $this->createQueryBuilder('e')
            ->select(
                'c.id as calendar, e.id, e.title, e.description, e.start, e.end, e.allDay, e.createdAt, e.updatedAt'
            );
        switch ($kind) {
            case 'system':
                $qb->innerJoin('e.systemCalendar', 'c');
                break;
            default:
                $qb->innerJoin('e.calendar', 'c');
                break;
        }

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
        if ($subordinate) {
            $connectionRepo = $this->getEntityManager()->getRepository('OroCalendarBundle:CalendarProperty');
            $qbConnections  = $connectionRepo->createQueryBuilder('connection')
                ->select('connection.calendar')
                ->where(
                    'connection.targetCalendar = :id'
                    . ' AND connection.calendarAlias = :calendarAlias'
                    . ' AND connection.visible = true'
                );

            $qb
                ->andWhere($qb->expr()->in('c.id', $qbConnections->getDQL()))
                ->setParameter('id', $calendarId)
                ->setParameter('calendarAlias', 'user');
        } else {
            $qb
                ->andWhere('c.id = :id')
                ->setParameter('id', $calendarId);
        }

        return $qb;
    }
}
