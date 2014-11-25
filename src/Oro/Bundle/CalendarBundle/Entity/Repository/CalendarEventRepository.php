<?php

namespace Oro\Bundle\CalendarBundle\Entity\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class CalendarEventRepository extends EntityRepository
{
    /**
     * Returns a query builder which can be used to get a list of user calendar events filtered by start and end dates
     *
     * @param \DateTime      $startDate
     * @param \DateTime      $endDate
     * @param array|Criteria $filters   Additional filtering criteria, e.g. ['allDay' => true, ...]
     *                                  or \Doctrine\Common\Collections\Criteria
     *
     * @return QueryBuilder
     */
    public function getUserEventListByTimeIntervalQueryBuilder($startDate, $endDate, $filters = [])
    {
        $qb = $this->getEventListQueryBuilder()
            ->innerJoin('e.calendar', 'c');

        $this->addFilters($qb, $filters);
        $this->addTimeIntervalFilter($qb, $startDate, $endDate);

        return $qb;
    }

    /**
     * Returns a query builder which can be used to get a list of system calendar events filtered by start and end dates
     *
     * @param \DateTime      $startDate
     * @param \DateTime      $endDate
     * @param array|Criteria $filters   Additional filtering criteria, e.g. ['allDay' => true, ...]
     *                                  or \Doctrine\Common\Collections\Criteria
     *
     * @return QueryBuilder
     */
    public function getSystemEventListByTimeIntervalQueryBuilder($startDate, $endDate, $filters = [])
    {
        $qb = $this->getEventListQueryBuilder()
            ->innerJoin('e.systemCalendar', 'c')
            ->andWhere('c.public = :public')
            ->setParameter('public', false);

        $this->addFilters($qb, $filters);
        $this->addTimeIntervalFilter($qb, $startDate, $endDate);

        return $qb;
    }

    /**
     * Returns a query builder which can be used to get a list of public calendar events filtered by start and end dates
     *
     * @param \DateTime      $startDate
     * @param \DateTime      $endDate
     * @param array|Criteria $filters   Additional filtering criteria, e.g. ['allDay' => true, ...]
     *                                  or \Doctrine\Common\Collections\Criteria
     *
     * @return QueryBuilder
     */
    public function getPublicEventListByTimeIntervalQueryBuilder($startDate, $endDate, $filters = [])
    {
        $qb = $this->getEventListQueryBuilder()
            ->innerJoin('e.systemCalendar', 'c')
            ->andWhere('c.public = :public')
            ->setParameter('public', true);

        $this->addFilters($qb, $filters);
        $this->addTimeIntervalFilter($qb, $startDate, $endDate);

        return $qb;
    }

    /**
     * Returns a base query builder which can be used to get a list of calendar events
     *
     * @return QueryBuilder
     */
    public function getEventListQueryBuilder()
    {
        return $this->createQueryBuilder('e')
            ->select(
                'c.id as calendar, e.id, e.title, e.description, e.start, e.end, e.allDay,'
                . ' e.backgroundColor, e.createdAt, e.updatedAt'
            );
    }

    /**
     * Adds time condition to a query builder responsible to get calender events
     *
     * @param QueryBuilder  $qb
     * @param \DateTime     $startDate
     * @param \DateTime     $endDate
     */
    public function addTimeIntervalFilter(QueryBuilder $qb, $startDate, $endDate)
    {
        $qb
            ->andWhere(
                '(e.start < :start AND e.end >= :start) OR '
                . '(e.start <= :end AND e.end > :end) OR'
                . '(e.start >= :start AND e.end < :end)'
            )
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->orderBy('c.id, e.start');
    }

    /**
     * Adds filters to a query builder
     *
     * @param QueryBuilder   $qb
     * @param array|Criteria $filters Additional filtering criteria, e.g. ['allDay' => true, ...]
     *                                or \Doctrine\Common\Collections\Criteria
     */
    protected function addFilters(QueryBuilder $qb, $filters)
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
    }

    /**
     * Adds connected calendars to a query builder
     *
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
}
