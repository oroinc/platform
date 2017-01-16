<?php

namespace Oro\Bundle\ReportBundle\Tests\Unit\Entity\Manager;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ReportBundle\Entity\CalendarDate;
use Oro\Bundle\ReportBundle\Entity\Manager\CalendarDateManager;
use Oro\Bundle\ReportBundle\Entity\Repository\CalendarDateRepository;

class CalendarDateManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var CalendarDateManager */
    protected $calendarDateManager;

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
        $startDate = new \DateTime('first day of this year');
        $today = new \DateTime();
        $entityManager
            ->expects($this->exactly(1 + $today->diff($startDate)->format('%a')))
            ->method('persist')
            ->with($this->isInstanceOf(CalendarDate::class));
        $entityManager->expects($this->once())->method('flush');
        $this->calendarDateManager->handleCalendarDates();
    }

    public function testHandleCalendarDatesWithAppending()
    {
        $startDate = new \DateTime('first day of this year');
        $startDate->modify('+15 days');
        $calendarDate = new CalendarDate();
        $calendarDate->setDate($startDate);
        $today = new \DateTime();
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
            ->expects($this->exactly(1 + $today->diff($startDate)->format('%a')))
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
        $startDate = new \DateTime('first day of this year');
        $today = new \DateTime();
        $entityManager
            ->expects($this->exactly(1 + $today->diff($startDate)->format('%a')))
            ->method('persist')
            ->with($this->isInstanceOf(CalendarDate::class));
        $entityManager->expects($this->once())->method('flush');
        $this->calendarDateManager->handleCalendarDates(true);
    }
}
