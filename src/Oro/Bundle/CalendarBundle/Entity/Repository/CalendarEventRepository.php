<?php

namespace Oro\Bundle\CalendarBundle\Entity\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class CalendarEventRepository extends EntityRepository
{
    /**
     * Returns a query builder with time condition for all calendar types
     *
     * @param QueryBuilder  $qb
     * @param \DateTime     $startDate
     * @param \DateTime     $endDate
     *
     * @return QueryBuilder
     */
    protected function addTimeIntervalQueryBuilder(QueryBuilder $qb, $startDate, $endDate)
    {
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
     * Returns a query builder with filters for all calendar types
     *
     * @param QueryBuilder   $qb
     * @param array|Criteria $filters       Additional filtering criteria, e.g. ['allDay' => true, ...]
     *                                      or \Doctrine\Common\Collections\Criteria
     *
     * @return QueryBuilder
     */
    protected function addFiltersQueryBuilder(QueryBuilder $qb, $filters)
    {
        if ($filters) {
            if (is_array($filters)) {
                $newCriteria = new Criteria();
                foreach ($filters as $fieldName => $value) {
                    $newCriteria->andWhere(Criteria::expr()->eq($fieldName, $value));
                }

                $filters = $newCriteria;
            }

            if ($filters instanceof Criteria) {
                $qb->addCriteria($filters);
            }
        }

        return $qb;
    }

    /**
     * Returns a query builder which can be used to get a list of calendar events filtered by start and end dates
     * This method apply to User and System Calendars
     *
     * @param int            $calendarId
     * @param \DateTime      $startDate
     * @param \DateTime      $endDate
     * @param bool           $subordinate   If true events from connected calendars will be returned as well
     * @param array|Criteria $filters       Additional filtering criteria, e.g. ['allDay' => true, ...]
     *                                      or \Doctrine\Common\Collections\Criteria
     * @param string         $calendarAlias user|system|null by default user
     * @param array          $options       Array of additional options
     *
     * @return QueryBuilder
     */
    public function getEventListByTimeIntervalQueryBuilder(
        $calendarId,
        $startDate,
        $endDate,
        $subordinate = false,
        $filters = [],
        $calendarAlias = 'user',
        $options = []
    ) {
        /** @var QueryBuilder $qb */
        $qb = $this->getEventListQueryBuilder($calendarId, $subordinate, $filters, $calendarAlias, $options);

        return $this->addTimeIntervalQueryBuilder($qb, $startDate, $endDate);
    }

    /**
     * Returns a query builder which can be used to get a list of calendar events filtered by start and end dates
     * This method apply to Public Calendar
     *
     * @param \DateTime      $startDate
     * @param \DateTime      $endDate
     * @param array|Criteria $filters       Additional filtering criteria, e.g. ['allDay' => true, ...]
     *                                      or \Doctrine\Common\Collections\Criteria
     *
     * @return QueryBuilder
     */
    public function getPublicEventListByTimeIntervalQueryBuilder($startDate, $endDate, $filters = [])
    {
        /** @var QueryBuilder $qb */
        $qb = $this->createQueryBuilder('e')
            ->select(
                'c.id as calendar, e.id, e.title, e.description, e.start, e.end, e.allDay, e.createdAt, e.updatedAt'
            );

        $qb->innerJoin('e.systemCalendar', 'c')
            ->andWhere('c.public = :public')
            ->setParameter('public', true);

        $qb = $this->addFiltersQueryBuilder($qb, $filters);

        return $this->addTimeIntervalQueryBuilder($qb, $startDate, $endDate);
    }

    /**
     * Returns a query builder which can be used to get a list of calendar events
     *
     * @param int            $calendarId
     * @param bool           $subordinate   If true events from connected calendars will be returned as well
     * @param array|Criteria $filters       Additional filtering criteria, e.g. ['allDay' => true, ...]
     *                                      or \Doctrine\Common\Collections\Criteria
     * @param string         $calendarAlias user|system|null by default user
     * @param array          $options       Array of additional options
     *
     * @return QueryBuilder
     */
    public function getEventListQueryBuilder(
        $calendarId,
        $subordinate = false,
        $filters = [],
        $calendarAlias = 'user',
        $options = []
    ) {
        /** @var QueryBuilder $qb */
        $qb = $this->createQueryBuilder('e')
            ->select(
                'c.id as calendar, e.id, e.title, e.description, e.start, e.end, e.allDay, e.createdAt, e.updatedAt'
            );

        switch ($calendarAlias) {
            case 'system':
                $qb->innerJoin('e.systemCalendar', 'c')
                    ->andWhere('c.public = :public')
                    ->setParameter('public', $options['public']);
                break;
            default:
                $qb->innerJoin('e.calendar', 'c');
        }

        $qb = $this->addFiltersQueryBuilder($qb, $filters);

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
                ->setParameter('calendarAlias', $calendarAlias);
        } else {
            $qb
                ->andWhere('c.id = :id')
                ->setParameter('id', $calendarId);
        }

        return $qb;
    }
}
