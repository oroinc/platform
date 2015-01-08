<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Entity\Repository;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;

use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarPropertyRepository;
use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\OrmTestCase;
use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\Mocks\EntityManagerMock;

class CalendarPropertyRepositoryTest extends OrmTestCase
{
    /** @var EntityManagerMock */
    protected $em;

    protected function setUp()
    {
        $metadataDriver = new AnnotationDriver(
            new AnnotationReader(),
            'Oro\Bundle\CalendarBundle\Entity'
        );

        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl($metadataDriver);
        $this->em->getConfiguration()->setEntityNamespaces(
            [
                'OroCalendarBundle' => 'Oro\Bundle\CalendarBundle\Entity'
            ]
        );
    }

    public function testGetTaskListByTimeIntervalQueryBuilder()
    {
        $targetCalendarId = 123;

        /** @var CalendarPropertyRepository $repo */
        $repo = $this->em->getRepository('OroCalendarBundle:CalendarProperty');
        $qb   = $repo->getConnectionsByTargetCalendarQueryBuilder($targetCalendarId);

        $this->assertEquals(
            'SELECT connection'
            . ' FROM Oro\Bundle\CalendarBundle\Entity\CalendarProperty connection'
            . ' WHERE connection.targetCalendar = :targetCalendarId',
            $qb->getDQL()
        );
        $this->assertEquals($targetCalendarId, $qb->getParameter('targetCalendarId')->getValue());
    }

    public function testGetTaskListByTimeIntervalQueryBuilderWithAlias()
    {
        $targetCalendarId = 123;
        $alias            = 'test';

        /** @var CalendarPropertyRepository $repo */
        $repo = $this->em->getRepository('OroCalendarBundle:CalendarProperty');
        $qb   = $repo->getConnectionsByTargetCalendarQueryBuilder($targetCalendarId, $alias);

        $this->assertEquals(
            'SELECT connection'
            . ' FROM Oro\Bundle\CalendarBundle\Entity\CalendarProperty connection'
            . ' WHERE connection.targetCalendar = :targetCalendarId AND connection.calendarAlias = :alias',
            $qb->getDQL()
        );
        $this->assertEquals($targetCalendarId, $qb->getParameter('targetCalendarId')->getValue());
        $this->assertEquals($alias, $qb->getParameter('alias')->getValue());
    }
}
