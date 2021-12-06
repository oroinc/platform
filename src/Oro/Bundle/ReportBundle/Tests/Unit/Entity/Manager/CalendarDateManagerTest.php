<?php

namespace Oro\Bundle\ReportBundle\Tests\Unit\Entity\Manager;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\ReportBundle\Entity\CalendarDate;
use Oro\Bundle\ReportBundle\Entity\Manager\CalendarDateManager;
use Oro\Bundle\ReportBundle\Entity\Repository\CalendarDateRepository;

class CalendarDateManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var LocaleSettings|\PHPUnit\Framework\MockObject\MockObject */
    private $localeSettings;

    /** @var CalendarDateManager */
    private $calendarDateManager;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->localeSettings = $this->createMock(LocaleSettings::class);
        $this->localeSettings->expects($this->once())
            ->method('getTimeZone')
            ->willReturn('UTC');

        $this->calendarDateManager = new CalendarDateManager(
            $this->doctrineHelper,
            $this->localeSettings
        );
    }

    public function testHandleCalendarDatesWithoutAppending()
    {
        $timezone = new \DateTimeZone('UTC');
        $entityManager = $this->createMock(EntityManager::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->with(CalendarDate::class)
            ->willReturn($entityManager);
        $startDate = new \DateTime('first day of january', $timezone);
        $endDate = new \DateTime('tomorrow', $timezone);
        $entityManager->expects($this->exactly((int)$endDate->diff($startDate)->format('%a')))
            ->method('persist')
            ->with($this->isInstanceOf(CalendarDate::class));
        $entityManager->expects($this->once())
            ->method('flush');

        $this->calendarDateManager->handleCalendarDates();
    }

    public function testHandleCalendarDatesWithAppending()
    {
        $timezone = new \DateTimeZone('UTC');
        $startDate = new \DateTime('tomorrow midnight - 10 days', $timezone);
        $calendarDate = new CalendarDate();
        $calendarDate->setDate($startDate);
        $repository = $this->createMock(CalendarDateRepository::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(CalendarDate::class)
            ->willReturn($repository);
        $repository->expects($this->once())
            ->method('getDate')
            ->willReturn($calendarDate);

        $entityManager = $this->createMock(EntityManager::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->with(CalendarDate::class)
            ->willReturn($entityManager);
        $entityManager->expects($this->exactly(9))
            ->method('persist')
            ->with($this->isInstanceOf(CalendarDate::class));
        $entityManager->expects($this->once())
            ->method('flush');

        $this->calendarDateManager->handleCalendarDates(true);
    }

    public function testHandleCalendarDatesWithEmptyTable()
    {
        $timezone = new \DateTimeZone('UTC');
        $repository = $this->createMock(CalendarDateRepository::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(CalendarDate::class)
            ->willReturn($repository);
        $repository->expects($this->once())
            ->method('getDate')
            ->willReturn(null);

        $entityManager = $this->createMock(EntityManager::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->with(CalendarDate::class)
            ->willReturn($entityManager);
        $startDate = new \DateTime('first day of january', $timezone);
        $endDate = new \DateTime('tomorrow', $timezone);
        $entityManager->expects($this->exactly((int)$endDate->diff($startDate)->format('%a')))
            ->method('persist')
            ->with($this->isInstanceOf(CalendarDate::class));
        $entityManager->expects($this->once())
            ->method('flush');

        $this->calendarDateManager->handleCalendarDates(true);
    }
}
