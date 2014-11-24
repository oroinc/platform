<?php

namespace Oro\Bundle\CalendarBundle\Entity\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;

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
     * @param QueryBuilder  $qb
     * @param int           $calendarId
     * @param string        $calendarAlias
     *
     * @return QueryBuilder
     */
    protected function addCalendarConnectionsQueryBuilder(QueryBuilder $qb, $calendarId, $calendarAlias)
    {
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

        return $qb;
    }

    /**
     * @return QueryBuilder
     */
    public function getBaseEventListQueryBuilder()
    {
        /** @var QueryBuilder $qb */
        $qb = $this->createQueryBuilder('e')
            ->select(
                'c.id as calendar, e.id, e.title, e.description, e.start, e.end, e.allDay, e.createdAt, e.updatedAt,'
                . ' e.backgroundColor'
            );

        return $qb;
    }

    /**
     * Returns a query builder which can be used to get a list of user calendar events filtered by start and end dates
     *
     * @param int            $calendarId
     * @param \DateTime      $startDate
     * @param \DateTime      $endDate
     * @param bool           $subordinate   If true events from connected calendars will be returned as well
     * @param array|Criteria $filters       Additional filtering criteria, e.g. ['allDay' => true, ...]
     *                                      or \Doctrine\Common\Collections\Criteria
     *
     * @return QueryBuilder
     */
    public function getUserEventListByTimeIntervalQueryBuilder(
        $calendarId,
        $startDate,
        $endDate,
        $subordinate = false,
        $filters = []
    ) {
        /** @var QueryBuilder $qb */
        $qb = $this->getUserEventListQueryBuilder($calendarId, $subordinate, $filters);

        return $this->addTimeIntervalQueryBuilder($qb, $startDate, $endDate);
    }

    /**
     * Returns a query builder which can be used to get a list of system calendar events filtered by start and end dates
     *
     * @param int            $calendarId
     * @param \DateTime      $startDate
     * @param \DateTime      $endDate
     * @param array|Criteria $filters       Additional filtering criteria, e.g. ['allDay' => true, ...]
     *                                      or \Doctrine\Common\Collections\Criteria
     *
     * @return QueryBuilder
     */
    public function getSystemEventListByTimeIntervalQueryBuilder(
        $calendarId,
        $startDate,
        $endDate,
        $filters = []
    ) {
        /** @var QueryBuilder $qb */
        $qb = $this->getSystemEventListQueryBuilder($calendarId, $filters);

        return $this->addTimeIntervalQueryBuilder($qb, $startDate, $endDate);
    }

    /**
     * Returns a query builder which can be used to get a list of public calendar events filtered by start and end dates
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
        $qb = $this->getBaseEventListQueryBuilder();

        $qb->innerJoin('e.systemCalendar', 'c')
            ->andWhere('c.public = :public')
            ->setParameter('public', true);

        $qb = $this->addFiltersQueryBuilder($qb, $filters);

        return $this->addTimeIntervalQueryBuilder($qb, $startDate, $endDate);
    }

    /**
     * Returns a query builder which can be used to get a list of user calendar events
     *
     * @param int            $calendarId
     * @param bool           $subordinate   If true events from connected calendars will be returned as well
     * @param array|Criteria $filters       Additional filtering criteria, e.g. ['allDay' => true, ...]
     *                                      or \Doctrine\Common\Collections\Criteria
     *
     * @return QueryBuilder
     */
    public function getUserEventListQueryBuilder(
        $calendarId,
        $subordinate = false,
        $filters = []
    ) {
        /** @var QueryBuilder $qb */
        $qb = $this->getBaseEventListQueryBuilder();

        $qb->innerJoin('e.calendar', 'c');

        $qb = $this->addFiltersQueryBuilder($qb, $filters);

        if ($subordinate) {
            $qb = $this->addCalendarConnectionsQueryBuilder($qb, $calendarId, Calendar::CALENDAR_ALIAS);
        } else {
            $qb
                ->andWhere('c.id = :id')
                ->setParameter('id', $calendarId);
        }

        return $qb;
    }

    /**
     * Returns a query builder which can be used to get a list of system calendar events
     *
     * @param int            $calendarId
     * @param array|Criteria $filters       Additional filtering criteria, e.g. ['allDay' => true, ...]
     *                                      or \Doctrine\Common\Collections\Criteria
     *
     * @return QueryBuilder
     */
    public function getSystemEventListQueryBuilder(
        $calendarId,
        $filters = []
    ) {
        /** @var QueryBuilder $qb */
        $qb = $this->getBaseEventListQueryBuilder();

        $qb->innerJoin('e.systemCalendar', 'c')
            ->andWhere('c.public = :public')
            ->setParameter('public', false);

        $qb = $this->addFiltersQueryBuilder($qb, $filters);

        $qb = $this->addCalendarConnectionsQueryBuilder($qb, $calendarId, SystemCalendar::CALENDAR_ALIAS);

        return $qb;
    }
}
