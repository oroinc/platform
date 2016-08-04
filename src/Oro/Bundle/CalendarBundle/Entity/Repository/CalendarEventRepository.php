<?php

namespace Oro\Bundle\CalendarBundle\Entity\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\CalendarBundle\Model\Recurrence;

class CalendarEventRepository extends EntityRepository
{
    /**
     * Returns a query builder which can be used to get a list of user calendar events filtered by start and end dates
     *
     * @param \DateTime      $startDate
     * @param \DateTime      $endDate
     * @param array|Criteria $filters   Additional filtering criteria, e.g. ['allDay' => true, ...]
     *                                  or \Doctrine\Common\Collections\Criteria
     * @param array          $extraFields
     *
     * @return QueryBuilder
     */
    public function getUserEventListByTimeIntervalQueryBuilder($startDate, $endDate, $filters = [], $extraFields = [])
    {
        $qb = $this->getUserEventListQueryBuilder($filters, $extraFields);
        $this->addTimeIntervalFilter($qb, $startDate, $endDate);
        $this->addRecurrencesConditions($qb, $startDate, $endDate);

        return $qb;
    }

    /**
     * Returns a query builder which can be used to get a list of user calendar events
     *
     * @param array|Criteria $filters Additional filtering criteria, e.g. ['allDay' => true, ...]
     *                                or \Doctrine\Common\Collections\Criteria
     * @param array          $extraFields
     *
     * @return QueryBuilder
     */
    public function getUserEventListQueryBuilder($filters = [], $extraFields = [])
    {
        $qb = $this->getEventListQueryBuilder($extraFields)
            ->addSelect('status.id AS invitationStatus')
            ->addSelect('IDENTITY(e.parent) AS parentEventId, c.id as calendar')
            ->addSelect('IDENTITY(e.recurringEvent) AS recurringEventId, e.originalStart, e.cancelled AS isCancelled')
            ->leftJoin('e.relatedAttendee', 'relatedAttendee')
            ->leftJoin('e.parent', 'parent')
            ->leftJoin('relatedAttendee.status', 'status')
            ->innerJoin('e.calendar', 'c');

        $this->addRecurrenceData($qb);
        $this->addFilters($qb, $filters);

        return $qb;
    }

    /**
     * Returns a query builder which can be used to get a list of user calendar events associated with recurring event.
     * Recurring event will be returned as well.
     *
     * @param array $filters
     * @param array $extraFields
     * @param null|integer $recurringEventId
     *
     * @return QueryBuilder
     */
    public function getUserEventListByRecurringEventQueryBuilder(
        $filters = [],
        $extraFields = [],
        $recurringEventId = null
    ) {
        $qb = $this->getUserEventListQueryBuilder($filters, $extraFields);
        if ((int)$recurringEventId) {
            $qb->orWhere('e.id = :recurringEventId')
                ->setParameter('recurringEventId', (int)$recurringEventId);
        }

        return $qb;
    }

    /**
     * Returns a query builder which can be used to get a list of system calendar events filtered by start and end dates
     *
     * @param \DateTime      $startDate
     * @param \DateTime      $endDate
     * @param array|Criteria $filters   Additional filtering criteria, e.g. ['allDay' => true, ...]
     *                                  or \Doctrine\Common\Collections\Criteria
     * @param array          $extraFields
     *
     * @return QueryBuilder
     */
    public function getSystemEventListByTimeIntervalQueryBuilder($startDate, $endDate, $filters = [], $extraFields = [])
    {
        $qb = $this->getEventListQueryBuilder($extraFields)
            ->addSelect('c.id as calendar')
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
     * @param array          $extraFields
     *
     * @return QueryBuilder
     */
    public function getPublicEventListByTimeIntervalQueryBuilder($startDate, $endDate, $filters = [], $extraFields = [])
    {
        $qb = $this->getEventListQueryBuilder($extraFields)
            ->addSelect('c.id as calendar')
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
     * @param array $extraFields
     *
     * @return QueryBuilder
     */
    public function getEventListQueryBuilder($extraFields = [])
    {
        $qb = $this->createQueryBuilder('e')
            ->select(
                'e.id, e.title, e.description, e.start, e.end, e.allDay,'
                . ' e.backgroundColor, e.createdAt, e.updatedAt'
            );
        if ($extraFields) {
            foreach ($extraFields as $field) {
                $qb->addSelect('e.' . $field);
            }
        }
        return $qb;
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
     * Returns a query builder which can be used to get invited users for the given calendar events
     *
     * @param int[] $parentEventIds
     *
     * @return QueryBuilder
     */
    public function getInvitedUsersByParentsQueryBuilder($parentEventIds)
    {
        return $this->createQueryBuilder('e')
            ->select('IDENTITY(e.parent) AS parentEventId, e.id AS eventId, u.id AS userId')
            ->innerJoin('e.calendar', 'c')
            ->innerJoin('c.owner', 'u')
            ->where('e.parent IN (:parentEventIds)')
            ->setParameter('parentEventIds', $parentEventIds);
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
     * Adds recurrence rules data
     *
     * @param QueryBuilder $queryBuilder
     *
     * @return CalendarEventRepository
     */
    protected function addRecurrenceData(QueryBuilder $queryBuilder)
    {
        $key = Recurrence::STRING_KEY;
        $queryBuilder
            ->leftJoin(
                'OroCalendarBundle:Recurrence',
                'r',
                Join::WITH,
                '(parent.id IS NOT NULL AND parent.recurrence = r.id) OR (parent.id IS NULL AND e.recurrence = r.id)'
            )
            ->addSelect(
                "r.recurrenceType as {$key}RecurrenceType, r.interval as {$key}Interval,"
                . "r.dayOfWeek as {$key}DayOfWeek, r.dayOfMonth as {$key}DayOfMonth,"
                . "r.monthOfYear as {$key}MonthOfYear, r.startTime as {$key}StartTime,"
                . "r.endTime as {$key}EndTime, r.occurrences as {$key}Occurrences,"
                . "r.instance as {$key}Instance, r.id as {$key}Id, r.timeZone as {$key}TimeZone"
            );

        return $this;
    }

    /**
     * Adds conditions for getting recurrence events that could be out of filtering dates.
     *
     * @param QueryBuilder $queryBuilder
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     *
     * @return CalendarEventRepository
     */
    protected function addRecurrencesConditions(QueryBuilder $queryBuilder, $startDate, $endDate)
    {
        $key = Recurrence::STRING_KEY;
        $queryBuilder->addSelect("r.calculatedEndTime as {$key}calculatedEndTime");

        //add condition that recurrence dates and filter dates are crossing
        $expr = $queryBuilder->expr();
        $queryBuilder
            ->orWhere(
                $expr->andX(
                    $expr->lte('r.startTime', ':endDate'),
                    $expr->gte('r.calculatedEndTime', ':startDate')
                )
            )
            ->orWhere(
                $expr->andX(
                    $expr->isNotNull('e.originalStart'),
                    $expr->lte('e.originalStart', ':endDate'),
                    $expr->gte('e.originalStart', ':startDate')
                )
            )
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);

        return $this;
    }

    /**
     * @param array $calendarEventIds
     *
     * @return array Map with structure "parentId => [parentId, childId, ...]"
     * where value is array of items from $calendarEventIds
     */
    public function getParentEventIds(array $calendarEventIds)
    {
        if (!$calendarEventIds) {
            return [];
        }

        $qb = $this->createQueryBuilder('event');

        $queryResult = $qb
            ->select('event.id AS parent, children.id AS child')
            ->join('event.childEvents', 'children')
            ->where($qb->expr()->in('children.id', $calendarEventIds))
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($calendarEventIds as $id) {
            $result[$id][] = $id;
        }

        foreach ($queryResult as $row) {
            $result[$row['parent']][] = $row['child'];
        }

        return $result;
    }
}
