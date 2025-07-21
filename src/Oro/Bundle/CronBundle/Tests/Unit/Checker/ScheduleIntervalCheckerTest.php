<?php

namespace Oro\Bundle\CronBundle\Tests\Unit\Checker;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CronBundle\Checker\ScheduleIntervalChecker;
use Oro\Bundle\CronBundle\Tests\Unit\Stub\ScheduleIntervalStub;
use PHPUnit\Framework\TestCase;

class ScheduleIntervalCheckerTest extends TestCase
{
    private ScheduleIntervalChecker $scheduleIntervalChecker;

    #[\Override]
    protected function setUp(): void
    {
        $this->scheduleIntervalChecker = new ScheduleIntervalChecker();
    }

    public function testHasActiveScheduleWithoutDateTimePassed(): void
    {
        $scheduleOne = (new ScheduleIntervalStub())
            ->setActiveAt((new \DateTime('now', new \DateTimeZone('UTC')))->modify('-1 day'))
            ->setDeactivateAt((new \DateTime('now', new \DateTimeZone('UTC')))->modify('+1 day'));
        $schedules = new ArrayCollection([$scheduleOne]);
        $this->assertTrue($this->scheduleIntervalChecker->hasActiveSchedule($schedules));
    }

    public function testHasNotActiveScheduleWithDateTimePassed(): void
    {
        $dateTime = (new \DateTime('now', new \DateTimeZone('UTC')))->modify('+2 day');
        $scheduleOne = (new ScheduleIntervalStub())
            ->setActiveAt((new \DateTime('now', new \DateTimeZone('UTC')))->modify('-1 day'))
            ->setDeactivateAt((new \DateTime('now', new \DateTimeZone('UTC')))->modify('+1 day'));
        $schedules = new ArrayCollection([$scheduleOne]);
        $this->assertFalse($this->scheduleIntervalChecker->hasActiveSchedule($schedules, $dateTime));
    }

    public function testScheduleWithoutDeactivateAt(): void
    {
        $dateTime = (new \DateTime('now', new \DateTimeZone('UTC')))->modify('+2 day');
        $scheduleOne = (new ScheduleIntervalStub())
            ->setActiveAt((new \DateTime('now', new \DateTimeZone('UTC')))->modify('-1 day'));
        $schedules = new ArrayCollection([$scheduleOne]);
        $this->assertTrue($this->scheduleIntervalChecker->hasActiveSchedule($schedules, $dateTime));
    }

    public function testScheduleWithoutActivateAt(): void
    {
        $dateTime = (new \DateTime('now', new \DateTimeZone('UTC')));
        $scheduleOne = (new ScheduleIntervalStub())
            ->setDeactivateAt((new \DateTime('now', new \DateTimeZone('UTC')))->modify('+2 day'));
        $schedules = new ArrayCollection([$scheduleOne]);
        $this->assertTrue($this->scheduleIntervalChecker->hasActiveSchedule($schedules, $dateTime));
    }

    public function testHasActiveScheduleWithActivateAtEqualsToDateTime(): void
    {
        $scheduleOne = (new ScheduleIntervalStub())
            ->setActiveAt(new \DateTime('now', new \DateTimeZone('UTC')))
            ->setDeactivateAt((new \DateTime('now', new \DateTimeZone('UTC')))->modify('+1 day'));
        $schedules = new ArrayCollection([$scheduleOne]);
        $this->assertTrue($this->scheduleIntervalChecker->hasActiveSchedule($schedules));
    }

    public function testHasNotActiveScheduleWithDeactivateAtEqualsToDateTime(): void
    {
        $scheduleOne = (new ScheduleIntervalStub())
            ->setActiveAt((new \DateTime('now', new \DateTimeZone('UTC')))->modify('-1 day'))
            ->setDeactivateAt(new \DateTime('now', new \DateTimeZone('UTC')));
        $schedules = new ArrayCollection([$scheduleOne]);
        $this->assertFalse($this->scheduleIntervalChecker->hasActiveSchedule($schedules));
    }

    public function testHasNotActiveScheduleWithDateTimeBeforeActiveAt(): void
    {
        $dateTime = (new \DateTime('now', new \DateTimeZone('UTC')))->modify('-2 day');
        $scheduleOne = (new ScheduleIntervalStub())
            ->setActiveAt((new \DateTime('now', new \DateTimeZone('UTC')))->modify('-1 day'))
            ->setDeactivateAt((new \DateTime('now', new \DateTimeZone('UTC')))->modify('+1 day'));
        $schedules = new ArrayCollection([$scheduleOne]);
        $this->assertFalse($this->scheduleIntervalChecker->hasActiveSchedule($schedules, $dateTime));
    }
}
