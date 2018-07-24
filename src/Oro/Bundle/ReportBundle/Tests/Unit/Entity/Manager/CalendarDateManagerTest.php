<?php

namespace Oro\Bundle\ReportBundle\Tests\Unit\Entity\Manager;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ReportBundle\Entity\CalendarDate;
use Oro\Bundle\ReportBundle\Entity\Manager\CalendarDateManager;
use Oro\Bundle\ReportBundle\Entity\Repository\CalendarDateRepository;

class CalendarDateManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineHelper;

    /** @var CalendarDateManager */
    protected $calendarDateManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->calendarDateManager = new CalendarDateManager($this->doctrineHelper);
    }

    public function testHandleCalendarDatesWithoutAppending()
    {
        $entityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this
            ->doctrineHelper
            ->expects($this->once())
            ->method('getEntityManager')
            ->with(CalendarDate::class)
            ->willReturn($entityManager);
        $startDate = new \DateTime('first day of january');
        $endDate = new \DateTime('tomorrow');
        $entityManager
            ->expects($this->exactly((int)$endDate->diff($startDate)->format('%a')))
            ->method('persist')
            ->with($this->isInstanceOf(CalendarDate::class));
        $entityManager->expects($this->once())->method('flush');

        $this->calendarDateManager->handleCalendarDates();
    }

    public function testHandleCalendarDatesWithAppending()
    {
        $timezone = new \DateTimeZone('UTC');
        $startDate = new \DateTime('tomorrow midnight - 10 days', $timezone);
        $calendarDate = new CalendarDate();
        $calendarDate->setDate($startDate);
        $repository = $this->getMockBuilder(CalendarDateRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this
            ->doctrineHelper
            ->expects($this->once())
            ->method('getEntityRepository')
            ->with(CalendarDate::class)
            ->willReturn($repository);
        $repository
            ->expects($this->once())
            ->method('getDate')
            ->willReturn($calendarDate);

        $entityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this
            ->doctrineHelper
            ->expects($this->once())
            ->method('getEntityManager')
            ->with(CalendarDate::class)
            ->willReturn($entityManager);
        $entityManager
            ->expects($this->exactly(10))
            ->method('persist')
            ->with($this->isInstanceOf(CalendarDate::class));
        $entityManager->expects($this->once())->method('flush');

        $this->calendarDateManager->handleCalendarDates(true);
    }

    public function testHandleCalendarDatesWithEmptyTable()
    {
        $repository = $this->getMockBuilder(CalendarDateRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this
            ->doctrineHelper
            ->expects($this->once())
            ->method('getEntityRepository')
            ->with(CalendarDate::class)
            ->willReturn($repository);
        $repository
            ->expects($this->once())
            ->method('getDate')
            ->willReturn(null);

        $entityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this
            ->doctrineHelper
            ->expects($this->once())
            ->method('getEntityManager')
            ->with(CalendarDate::class)
            ->willReturn($entityManager);
        $startDate = new \DateTime('first day of january');
        $endDate = new \DateTime('tomorrow');
        $entityManager
            ->expects($this->exactly((int)$endDate->diff($startDate)->format('%a')))
            ->method('persist')
            ->with($this->isInstanceOf(CalendarDate::class));
        $entityManager->expects($this->once())->method('flush');

        $this->calendarDateManager->handleCalendarDates(true);
    }
}
