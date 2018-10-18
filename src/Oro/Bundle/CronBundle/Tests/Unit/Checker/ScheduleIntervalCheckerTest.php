<?php

namespace Oro\Bundle\CronBundle\Tests\Unit\Checker;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CronBundle\Checker\ScheduleIntervalChecker;
use Oro\Bundle\CronBundle\Tests\Unit\Stub\ScheduleIntervalStub;

/**
 * {@inheritDoc}
 */
class ScheduleIntervalCheckerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ScheduleIntervalChecker
     */
    private $scheduleIntervalChecker;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->scheduleIntervalChecker = new ScheduleIntervalChecker();
    }

    public function testHasActiveScheduleWithoutDateTimePassed()
    {
        $scheduleOne = (new ScheduleIntervalStub())
            ->setActiveAt((new \DateTime('now', new \DateTimeZone('UTC')))->modify('-1 day'))
            ->setDeactivateAt((new \DateTime('now', new \DateTimeZone('UTC')))->modify('+1 day'));
        $schedules = new ArrayCollection([$scheduleOne]);
        $this->assertTrue($this->scheduleIntervalChecker->hasActiveSchedule($schedules));
    }

    public function testHasNotActiveScheduleWithDateTimePassed()
    {
        $dateTime = (new \DateTime('now', new \DateTimeZone('UTC')))->modify('+2 day');
        $scheduleOne = (new ScheduleIntervalStub())
            ->setActiveAt((new \DateTime('now', new \DateTimeZone('UTC')))->modify('-1 day'))
            ->setDeactivateAt((new \DateTime('now', new \DateTimeZone('UTC')))->modify('+1 day'));
        $schedules = new ArrayCollection([$scheduleOne]);
        $this->assertFalse($this->scheduleIntervalChecker->hasActiveSchedule($schedules, $dateTime));
    }

    public function testScheduleWithoutDeactivateAt()
    {
        $dateTime = (new \DateTime('now', new \DateTimeZone('UTC')))->modify('+2 day');
        $scheduleOne = (new ScheduleIntervalStub())
            ->setActiveAt((new \DateTime('now', new \DateTimeZone('UTC')))->modify('-1 day'));
        $schedules = new ArrayCollection([$scheduleOne]);
        $this->assertTrue($this->scheduleIntervalChecker->hasActiveSchedule($schedules, $dateTime));
    }

    public function testScheduleWithoutActivateAt()
    {
        $dateTime = (new \DateTime('now', new \DateTimeZone('UTC')));
        $scheduleOne = (new ScheduleIntervalStub())
            ->setDeactivateAt((new \DateTime('now', new \DateTimeZone('UTC')))->modify('+2 day'));
        $schedules = new ArrayCollection([$scheduleOne]);
        $this->assertTrue($this->scheduleIntervalChecker->hasActiveSchedule($schedules, $dateTime));
    }

    public function testHasActiveScheduleWithActivateAtEqualsToDateTime()
    {
        $scheduleOne = (new ScheduleIntervalStub())
            ->setActiveAt(new \DateTime('now', new \DateTimeZone('UTC')))
            ->setDeactivateAt((new \DateTime('now', new \DateTimeZone('UTC')))->modify('+1 day'));
        $schedules = new ArrayCollection([$scheduleOne]);
        $this->assertTrue($this->scheduleIntervalChecker->hasActiveSchedule($schedules));
    }

    public function testHasNotActiveScheduleWithDeactivateAtEqualsToDateTime()
    {
        $scheduleOne = (new ScheduleIntervalStub())
            ->setActiveAt((new \DateTime('now', new \DateTimeZone('UTC')))->modify('-1 day'))
            ->setDeactivateAt(new \DateTime('now', new \DateTimeZone('UTC')));
        $schedules = new ArrayCollection([$scheduleOne]);
        $this->assertFalse($this->scheduleIntervalChecker->hasActiveSchedule($schedules));
    }

    public function testHasNotActiveScheduleWithDateTimeBeforeActiveAt()
    {
        $dateTime = (new \DateTime('now', new \DateTimeZone('UTC')))->modify('-2 day');
        $scheduleOne = (new ScheduleIntervalStub())
            ->setActiveAt((new \DateTime('now', new \DateTimeZone('UTC')))->modify('-1 day'))
            ->setDeactivateAt((new \DateTime('now', new \DateTimeZone('UTC')))->modify('+1 day'));
        $schedules = new ArrayCollection([$scheduleOne]);
        $this->assertFalse($this->scheduleIntervalChecker->hasActiveSchedule($schedules, $dateTime));
    }
}
