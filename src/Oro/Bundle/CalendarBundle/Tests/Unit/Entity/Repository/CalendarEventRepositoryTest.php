<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Entity\Repository;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;

use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\OrmTestCase;
use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\Mocks\EntityManagerMock;

use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarEventRepository;

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

    public function testGetEventListByTimeIntervalQueryBuilder()
    {
        /** @var CalendarEventRepository $repo */
        $repo = $this->em->getRepository('OroCalendarBundle:CalendarEvent');

        $qb = $repo->getEventListByTimeIntervalQueryBuilder(1, new \DateTime(), new \DateTime(), true);

        $this->assertEquals(
            'SELECT c.id as calendar, e.id, e.title, e.description, e.start, e.end, e.allDay,'
            . ' e.backgroundColor, e.createdAt, e.updatedAt'
            . ' FROM Oro\Bundle\CalendarBundle\Entity\CalendarEvent e'
            . ' INNER JOIN e.calendar c'
            . ' WHERE (c.id IN(SELECT connection.calendar'
            . ' FROM Oro\Bundle\CalendarBundle\Entity\CalendarProperty connection'
            . ' WHERE connection.targetCalendar = :id'
            . ' AND connection.calendarAlias = :calendarAlias'
            . ' AND connection.visible = true))'
            . ' AND ('
            . '(e.start < :start AND e.end >= :start) OR '
            . '(e.start <= :end AND e.end > :end) OR'
            . '(e.start >= :start AND e.end < :end))'
            . ' ORDER BY c.id, e.start ASC',
            $qb->getQuery()->getDQL()
        );
    }

    public function testGetEventListByTimeIntervalQueryBuilderForOwnEventsOnly()
    {
        /** @var CalendarEventRepository $repo */
        $repo = $this->em->getRepository('OroCalendarBundle:CalendarEvent');

        $qb = $repo->getEventListByTimeIntervalQueryBuilder(1, new \DateTime(), new \DateTime(), false);

        $this->assertEquals(
            'SELECT c.id as calendar, e.id, e.title, e.description, e.start, e.end, e.allDay,'
            . ' e.backgroundColor, e.createdAt, e.updatedAt'
            . ' FROM Oro\Bundle\CalendarBundle\Entity\CalendarEvent e'
            . ' INNER JOIN e.calendar c'
            . ' WHERE c.id = :id'
            . ' AND ('
            . '(e.start < :start AND e.end >= :start) OR '
            . '(e.start <= :end AND e.end > :end) OR'
            . '(e.start >= :start AND e.end < :end))'
            . ' ORDER BY c.id, e.start ASC',
            $qb->getQuery()->getDQL()
        );
    }

    public function testGetEventListByTimeIntervalQueryBuilderWithAdditionalFiltersAsCriteria()
    {
        /** @var CalendarEventRepository $repo */
        $repo = $this->em->getRepository('OroCalendarBundle:CalendarEvent');

        $qb = $repo->getEventListByTimeIntervalQueryBuilder(
            1,
            new \DateTime(),
            new \DateTime(),
            false,
            new Criteria(Criteria::expr()->eq('allDay', true))
        );

        $this->assertEquals(
            'SELECT c.id as calendar, e.id, e.title, e.description, e.start, e.end, e.allDay,'
            . ' e.backgroundColor, e.createdAt, e.updatedAt'
            . ' FROM Oro\Bundle\CalendarBundle\Entity\CalendarEvent e'
            . ' INNER JOIN e.calendar c'
            . ' WHERE e.allDay = :allDay AND c.id = :id'
            . ' AND ('
            . '(e.start < :start AND e.end >= :start) OR '
            . '(e.start <= :end AND e.end > :end) OR'
            . '(e.start >= :start AND e.end < :end))'
            . ' ORDER BY c.id, e.start ASC',
            $qb->getQuery()->getDQL()
        );

        $this->assertTrue($qb->getQuery()->getParameter('allDay')->getValue());
    }

    public function testGetEventListByTimeIntervalQueryBuilderWithAdditionalFiltersAsArray()
    {
        /** @var CalendarEventRepository $repo */
        $repo = $this->em->getRepository('OroCalendarBundle:CalendarEvent');

        $qb = $repo->getEventListByTimeIntervalQueryBuilder(
            1,
            new \DateTime(),
            new \DateTime(),
            false,
            ['allDay' => true]
        );

        $this->assertEquals(
            'SELECT c.id as calendar, e.id, e.title, e.description, e.start, e.end, e.allDay,'
            . ' e.backgroundColor, e.createdAt, e.updatedAt'
            . ' FROM Oro\Bundle\CalendarBundle\Entity\CalendarEvent e'
            . ' INNER JOIN e.calendar c'
            . ' WHERE e.allDay = :allDay AND c.id = :id'
            . ' AND ('
            . '(e.start < :start AND e.end >= :start) OR '
            . '(e.start <= :end AND e.end > :end) OR'
            . '(e.start >= :start AND e.end < :end))'
            . ' ORDER BY c.id, e.start ASC',
            $qb->getQuery()->getDQL()
        );

        $this->assertTrue($qb->getQuery()->getParameter('allDay')->getValue());
    }
}
