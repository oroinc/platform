<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Entity\Repository;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;

use Oro\Bundle\CalendarBundle\Model\Recurrence;
use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\OrmTestCase;
use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\Mocks\EntityManagerMock;

use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarEventRepository;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CalendarEventRepositoryTest extends OrmTestCase
{
    /**
     * @var EntityManagerMock
     */
    protected $em;

    protected function setUp()
    {
        $reader         = new AnnotationReader();
        $metadataDriver = new AnnotationDriver(
            $reader,
            'Oro\Bundle\CalendarBundle\Entity'
        );

        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl($metadataDriver);
        $this->em->getConfiguration()->setEntityNamespaces(
            array(
                'OroCalendarBundle' => 'Oro\Bundle\CalendarBundle\Entity'
            )
        );
    }

    public function testGetEventListQueryBuilder()
    {
        /** @var CalendarEventRepository $repo */
        $repo = $this->em->getRepository('OroCalendarBundle:CalendarEvent');

        $qb = $repo->getEventListQueryBuilder();

        $this->assertEquals(
            'SELECT e.id, e.title, e.description, e.start, e.end, e.allDay,'
            . ' e.backgroundColor, e.createdAt, e.updatedAt'
            . ' FROM Oro\Bundle\CalendarBundle\Entity\CalendarEvent e',
            $qb->getQuery()->getDQL()
        );
    }

    public function testGetUserEventListByTimeIntervalQueryBuilder()
    {
        /** @var CalendarEventRepository $repo */
        $repo = $this->em->getRepository('OroCalendarBundle:CalendarEvent');

        $qb = $repo->getUserEventListByTimeIntervalQueryBuilder(new \DateTime(), new \DateTime());

        $key = Recurrence::STRING_KEY;
        $this->assertEquals(
            'SELECT e.id, e.title, e.description, e.start, e.end, e.allDay,'
            . ' e.backgroundColor, e.createdAt, e.updatedAt,'
            . ' status.id AS invitationStatus, IDENTITY(e.parent) AS parentEventId,'
            . ' c.id as calendar,'
            . ' IDENTITY(e.recurringEvent) AS recurringEventId,'
            . ' e.originalStart, e.cancelled AS isCancelled,'
            . " r.recurrenceType as {$key}RecurrenceType, r.interval as {$key}Interval,"
            . "r.dayOfWeek as {$key}DayOfWeek, r.dayOfMonth as {$key}DayOfMonth,"
            . "r.monthOfYear as {$key}MonthOfYear, r.startTime as {$key}StartTime,"
            . "r.endTime as {$key}EndTime, r.occurrences as {$key}Occurrences,"
            . "r.instance as {$key}Instance, r.id as recurrenceId,"
            . ' r.timeZone as recurrenceTimeZone,'
            . " r.calculatedEndTime as {$key}calculatedEndTime"
            . ' FROM Oro\Bundle\CalendarBundle\Entity\CalendarEvent e'
            . ' LEFT JOIN e.relatedAttendee relatedAttendee'
            . ' LEFT JOIN e.parent parent'
            . ' LEFT JOIN relatedAttendee.status status'
            . ' INNER JOIN e.calendar c'
            . ' LEFT JOIN OroCalendarBundle:Recurrence r WITH (parent.id IS NOT NULL AND parent.recurrence = r.id) OR'
            . ' (parent.id IS NULL AND e.recurrence = r.id)'
            . ' WHERE '
            . '((e.start < :start AND e.end >= :start) OR '
            . '(e.start <= :end AND e.end > :end) OR'
            . '(e.start >= :start AND e.end < :end)) OR'
            . ' (r.startTime <= :endDate AND r.calculatedEndTime >= :startDate) OR'
            . ' (e.originalStart IS NOT NULL AND e.originalStart <= :endDate AND e.originalStart >= :startDate)'
            . ' ORDER BY c.id, e.start ASC',
            $qb->getQuery()->getDQL()
        );
    }

    public function testGetUserEventListByTimeIntervalQueryBuilderWithAdditionalFiltersAsCriteria()
    {
        /** @var CalendarEventRepository $repo */
        $repo = $this->em->getRepository('OroCalendarBundle:CalendarEvent');

        $qb = $repo->getUserEventListByTimeIntervalQueryBuilder(
            new \DateTime(),
            new \DateTime(),
            new Criteria(Criteria::expr()->eq('allDay', true))
        );

        $key = Recurrence::STRING_KEY;
        $this->assertEquals(
            'SELECT e.id, e.title, e.description, e.start, e.end, e.allDay,'
            . ' e.backgroundColor, e.createdAt, e.updatedAt,'
            . ' status.id AS invitationStatus, IDENTITY(e.parent) AS parentEventId,'
            . ' c.id as calendar,'
            . ' IDENTITY(e.recurringEvent) AS recurringEventId,'
            . ' e.originalStart, e.cancelled AS isCancelled,'
            . " r.recurrenceType as {$key}RecurrenceType, r.interval as {$key}Interval,"
            . "r.dayOfWeek as {$key}DayOfWeek, r.dayOfMonth as {$key}DayOfMonth,"
            . "r.monthOfYear as {$key}MonthOfYear, r.startTime as {$key}StartTime,"
            . "r.endTime as {$key}EndTime, r.occurrences as {$key}Occurrences,"
            . "r.instance as {$key}Instance, r.id as recurrenceId,"
            . ' r.timeZone as recurrenceTimeZone,'
            . " r.calculatedEndTime as {$key}calculatedEndTime"
            . ' FROM Oro\Bundle\CalendarBundle\Entity\CalendarEvent e'
            . ' LEFT JOIN e.relatedAttendee relatedAttendee'
            . ' LEFT JOIN e.parent parent'
            . ' LEFT JOIN relatedAttendee.status status'
            . ' INNER JOIN e.calendar c'
            . ' LEFT JOIN OroCalendarBundle:Recurrence r WITH (parent.id IS NOT NULL AND parent.recurrence = r.id) OR'
            . ' (parent.id IS NULL AND e.recurrence = r.id)'
            . ' WHERE '
            . '(e.allDay = :allDay'
            . ' AND ('
            . '(e.start < :start AND e.end >= :start) OR '
            . '(e.start <= :end AND e.end > :end) OR'
            . '(e.start >= :start AND e.end < :end))) OR'
            . ' (r.startTime <= :endDate AND r.calculatedEndTime >= :startDate) OR'
            . ' (e.originalStart IS NOT NULL AND e.originalStart <= :endDate AND e.originalStart >= :startDate)'
            . ' ORDER BY c.id, e.start ASC',
            $qb->getQuery()->getDQL()
        );

        $this->assertTrue($qb->getQuery()->getParameter('allDay')->getValue());
    }

    public function testGetUserEventListByTimeIntervalQueryBuilderWithAdditionalFiltersAsArray()
    {
        /** @var CalendarEventRepository $repo */
        $repo = $this->em->getRepository('OroCalendarBundle:CalendarEvent');

        $qb = $repo->getUserEventListByTimeIntervalQueryBuilder(
            new \DateTime(),
            new \DateTime(),
            ['allDay' => true]
        );

        $key = Recurrence::STRING_KEY;
        $this->assertEquals(
            'SELECT e.id, e.title, e.description, e.start, e.end, e.allDay,'
            . ' e.backgroundColor, e.createdAt, e.updatedAt,'
            . ' status.id AS invitationStatus, IDENTITY(e.parent) AS parentEventId,'
            . ' c.id as calendar,'
            . ' IDENTITY(e.recurringEvent) AS recurringEventId,'
            . ' e.originalStart, e.cancelled AS isCancelled,'
            . " r.recurrenceType as {$key}RecurrenceType, r.interval as {$key}Interval,"
            . "r.dayOfWeek as {$key}DayOfWeek, r.dayOfMonth as {$key}DayOfMonth,"
            . "r.monthOfYear as {$key}MonthOfYear, r.startTime as {$key}StartTime,"
            . "r.endTime as {$key}EndTime, r.occurrences as {$key}Occurrences,"
            . "r.instance as {$key}Instance, r.id as recurrenceId,"
            . ' r.timeZone as recurrenceTimeZone,'
            . " r.calculatedEndTime as {$key}calculatedEndTime"
            . ' FROM Oro\Bundle\CalendarBundle\Entity\CalendarEvent e'
            . ' LEFT JOIN e.relatedAttendee relatedAttendee'
            . ' LEFT JOIN e.parent parent'
            . ' LEFT JOIN relatedAttendee.status status'
            . ' INNER JOIN e.calendar c'
            . ' LEFT JOIN OroCalendarBundle:Recurrence r WITH (parent.id IS NOT NULL AND parent.recurrence = r.id) OR'
            . ' (parent.id IS NULL AND e.recurrence = r.id)'
            . ' WHERE '
            . '(e.allDay = :allDay'
            . ' AND ('
            . '(e.start < :start AND e.end >= :start) OR '
            . '(e.start <= :end AND e.end > :end) OR'
            . '(e.start >= :start AND e.end < :end))) OR'
            . ' (r.startTime <= :endDate AND r.calculatedEndTime >= :startDate) OR'
            . ' (e.originalStart IS NOT NULL AND e.originalStart <= :endDate AND e.originalStart >= :startDate)'
            . ' ORDER BY c.id, e.start ASC',
            $qb->getQuery()->getDQL()
        );

        $this->assertTrue($qb->getQuery()->getParameter('allDay')->getValue());
    }

    public function testGetUserEventListByTimeIntervalQueryBuilderWithAdditionalFields()
    {
        /** @var CalendarEventRepository $repo */
        $repo = $this->em->getRepository('OroCalendarBundle:CalendarEvent');

        $qb = $repo->getUserEventListByTimeIntervalQueryBuilder(
            new \DateTime(),
            new \DateTime(),
            [],
            ['status']
        );

        $key = Recurrence::STRING_KEY;
        $this->assertEquals(
            'SELECT e.id, e.title, e.description, e.start, e.end, e.allDay,'
            . ' e.backgroundColor, e.createdAt, e.updatedAt, e.status,'
            . ' status.id AS invitationStatus, IDENTITY(e.parent) AS parentEventId,'
            . ' c.id as calendar,'
            . ' IDENTITY(e.recurringEvent) AS recurringEventId,'
            . ' e.originalStart, e.cancelled AS isCancelled,'
            . " r.recurrenceType as {$key}RecurrenceType, r.interval as {$key}Interval,"
            . "r.dayOfWeek as {$key}DayOfWeek, r.dayOfMonth as {$key}DayOfMonth,"
            . "r.monthOfYear as {$key}MonthOfYear, r.startTime as {$key}StartTime,"
            . "r.endTime as {$key}EndTime, r.occurrences as {$key}Occurrences,"
            . "r.instance as {$key}Instance, r.id as recurrenceId,"
            . ' r.timeZone as recurrenceTimeZone,'
            . " r.calculatedEndTime as {$key}calculatedEndTime"
            . ' FROM Oro\Bundle\CalendarBundle\Entity\CalendarEvent e'
            . ' LEFT JOIN e.relatedAttendee relatedAttendee'
            . ' LEFT JOIN e.parent parent'
            . ' LEFT JOIN relatedAttendee.status status'
            . ' INNER JOIN e.calendar c'
            . ' LEFT JOIN OroCalendarBundle:Recurrence r WITH (parent.id IS NOT NULL AND parent.recurrence = r.id) OR'
            . ' (parent.id IS NULL AND e.recurrence = r.id)'
            . ' WHERE '
            . '((e.start < :start AND e.end >= :start) OR '
            . '(e.start <= :end AND e.end > :end) OR'
            . '(e.start >= :start AND e.end < :end)) OR'
            . ' (r.startTime <= :endDate AND r.calculatedEndTime >= :startDate) OR'
            . ' (e.originalStart IS NOT NULL AND e.originalStart <= :endDate AND e.originalStart >= :startDate)'
            . ' ORDER BY c.id, e.start ASC',
            $qb->getQuery()->getDQL()
        );
    }

    public function testGetSystemEventListByTimeIntervalQueryBuilder()
    {
        /** @var CalendarEventRepository $repo */
        $repo = $this->em->getRepository('OroCalendarBundle:CalendarEvent');

        $qb = $repo->getSystemEventListByTimeIntervalQueryBuilder(new \DateTime(), new \DateTime());

        $this->assertEquals(
            'SELECT e.id, e.title, e.description, e.start, e.end, e.allDay,'
            . ' e.backgroundColor, e.createdAt, e.updatedAt, c.id as calendar'
            . ' FROM Oro\Bundle\CalendarBundle\Entity\CalendarEvent e'
            . ' INNER JOIN e.systemCalendar c'
            . ' WHERE '
            . 'c.public = :public'
            . ' AND ('
            . '(e.start < :start AND e.end >= :start) OR '
            . '(e.start <= :end AND e.end > :end) OR'
            . '(e.start >= :start AND e.end < :end))'
            . ' ORDER BY c.id, e.start ASC',
            $qb->getQuery()->getDQL()
        );
    }

    public function testGetSystemEventListByTimeIntervalQueryBuilderWithAdditionalFiltersAsCriteria()
    {
        /** @var CalendarEventRepository $repo */
        $repo = $this->em->getRepository('OroCalendarBundle:CalendarEvent');

        $qb = $repo->getSystemEventListByTimeIntervalQueryBuilder(
            new \DateTime(),
            new \DateTime(),
            new Criteria(Criteria::expr()->eq('allDay', true))
        );

        $this->assertEquals(
            'SELECT e.id, e.title, e.description, e.start, e.end, e.allDay,'
            . ' e.backgroundColor, e.createdAt, e.updatedAt, c.id as calendar'
            . ' FROM Oro\Bundle\CalendarBundle\Entity\CalendarEvent e'
            . ' INNER JOIN e.systemCalendar c'
            . ' WHERE '
            . 'c.public = :public AND e.allDay = :allDay'
            . ' AND ('
            . '(e.start < :start AND e.end >= :start) OR '
            . '(e.start <= :end AND e.end > :end) OR'
            . '(e.start >= :start AND e.end < :end))'
            . ' ORDER BY c.id, e.start ASC',
            $qb->getQuery()->getDQL()
        );

        $this->assertTrue($qb->getQuery()->getParameter('allDay')->getValue());
    }

    public function testGetSystemEventListByTimeIntervalQueryBuilderWithAdditionalFiltersAsArray()
    {
        /** @var CalendarEventRepository $repo */
        $repo = $this->em->getRepository('OroCalendarBundle:CalendarEvent');

        $qb = $repo->getSystemEventListByTimeIntervalQueryBuilder(
            new \DateTime(),
            new \DateTime(),
            ['allDay' => true]
        );

        $this->assertEquals(
            'SELECT e.id, e.title, e.description, e.start, e.end, e.allDay,'
            . ' e.backgroundColor, e.createdAt, e.updatedAt, c.id as calendar'
            . ' FROM Oro\Bundle\CalendarBundle\Entity\CalendarEvent e'
            . ' INNER JOIN e.systemCalendar c'
            . ' WHERE '
            . 'c.public = :public AND e.allDay = :allDay'
            . ' AND ('
            . '(e.start < :start AND e.end >= :start) OR '
            . '(e.start <= :end AND e.end > :end) OR'
            . '(e.start >= :start AND e.end < :end))'
            . ' ORDER BY c.id, e.start ASC',
            $qb->getQuery()->getDQL()
        );

        $this->assertTrue($qb->getQuery()->getParameter('allDay')->getValue());
    }

    public function testGetPublicEventListByTimeIntervalQueryBuilder()
    {
        /** @var CalendarEventRepository $repo */
        $repo = $this->em->getRepository('OroCalendarBundle:CalendarEvent');

        $qb = $repo->getPublicEventListByTimeIntervalQueryBuilder(new \DateTime(), new \DateTime());

        $this->assertEquals(
            'SELECT e.id, e.title, e.description, e.start, e.end, e.allDay,'
            . ' e.backgroundColor, e.createdAt, e.updatedAt, c.id as calendar'
            . ' FROM Oro\Bundle\CalendarBundle\Entity\CalendarEvent e'
            . ' INNER JOIN e.systemCalendar c'
            . ' WHERE '
            . 'c.public = :public'
            . ' AND ('
            . '(e.start < :start AND e.end >= :start) OR '
            . '(e.start <= :end AND e.end > :end) OR'
            . '(e.start >= :start AND e.end < :end))'
            . ' ORDER BY c.id, e.start ASC',
            $qb->getQuery()->getDQL()
        );
    }

    public function testGetPublicEventListByTimeIntervalQueryBuilderWithAdditionalFiltersAsCriteria()
    {
        /** @var CalendarEventRepository $repo */
        $repo = $this->em->getRepository('OroCalendarBundle:CalendarEvent');

        $qb = $repo->getPublicEventListByTimeIntervalQueryBuilder(
            new \DateTime(),
            new \DateTime(),
            new Criteria(Criteria::expr()->eq('allDay', true))
        );

        $this->assertEquals(
            'SELECT e.id, e.title, e.description, e.start, e.end, e.allDay,'
            . ' e.backgroundColor, e.createdAt, e.updatedAt, c.id as calendar'
            . ' FROM Oro\Bundle\CalendarBundle\Entity\CalendarEvent e'
            . ' INNER JOIN e.systemCalendar c'
            . ' WHERE '
            . 'c.public = :public AND e.allDay = :allDay'
            . ' AND ('
            . '(e.start < :start AND e.end >= :start) OR '
            . '(e.start <= :end AND e.end > :end) OR'
            . '(e.start >= :start AND e.end < :end))'
            . ' ORDER BY c.id, e.start ASC',
            $qb->getQuery()->getDQL()
        );

        $this->assertTrue($qb->getQuery()->getParameter('allDay')->getValue());
    }

    public function testGetPublicEventListByTimeIntervalQueryBuilderWithAdditionalFiltersAsArray()
    {
        /** @var CalendarEventRepository $repo */
        $repo = $this->em->getRepository('OroCalendarBundle:CalendarEvent');

        $qb = $repo->getPublicEventListByTimeIntervalQueryBuilder(
            new \DateTime(),
            new \DateTime(),
            ['allDay' => true]
        );

        $this->assertEquals(
            'SELECT e.id, e.title, e.description, e.start, e.end, e.allDay,'
            . ' e.backgroundColor, e.createdAt, e.updatedAt, c.id as calendar'
            . ' FROM Oro\Bundle\CalendarBundle\Entity\CalendarEvent e'
            . ' INNER JOIN e.systemCalendar c'
            . ' WHERE '
            . 'c.public = :public AND e.allDay = :allDay'
            . ' AND ('
            . '(e.start < :start AND e.end >= :start) OR '
            . '(e.start <= :end AND e.end > :end) OR'
            . '(e.start >= :start AND e.end < :end))'
            . ' ORDER BY c.id, e.start ASC',
            $qb->getQuery()->getDQL()
        );

        $this->assertTrue($qb->getQuery()->getParameter('allDay')->getValue());
    }

    public function testGetInvitedUsersByParentsQueryBuilder()
    {
        $parentEventIds = [1, 2];

        /** @var CalendarEventRepository $repo */
        $repo = $this->em->getRepository('OroCalendarBundle:CalendarEvent');

        $qb = $repo->getInvitedUsersByParentsQueryBuilder($parentEventIds);

        $this->assertEquals(
            'SELECT IDENTITY(e.parent) AS parentEventId, e.id AS eventId, u.id AS userId'
            . ' FROM Oro\Bundle\CalendarBundle\Entity\CalendarEvent e'
            . ' INNER JOIN e.calendar c'
            . ' INNER JOIN c.owner u'
            . ' WHERE e.parent IN (:parentEventIds)',
            $qb->getQuery()->getDQL()
        );

        $this->assertEquals($parentEventIds, $qb->getQuery()->getParameter('parentEventIds')->getValue());
    }
}
