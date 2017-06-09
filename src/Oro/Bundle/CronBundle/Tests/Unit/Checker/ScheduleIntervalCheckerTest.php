<?php

namespace Oro\Bundle\CronBundle\Tests\Unit\Checker;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CronBundle\Checker\ScheduleIntervalChecker;
use Oro\Bundle\CronBundle\Tests\Unit\Checker\Stub\ScheduleIntervalStub;

/**
 * {@inheritDoc}
 */
class ScheduleIntervalCheckerTest extends \PHPUnit_Framework_TestCase
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

    public function testHasActiveScheduleWithoutDatePassed()
    {
        $scheduleOne = (new ScheduleIntervalStub())
            ->setActiveAt((new \DateTime('now', new \DateTimeZone('UTC')))->modify('-1 day'))
            ->setDeactivateAt((new \DateTime('now', new \DateTimeZone('UTC')))->modify('+1 day'));
        $schedules = new ArrayCollection([$scheduleOne]);
        $this->assertTrue($this->scheduleIntervalChecker->hasActiveSchedule($schedules));
    }

    public function testHasNotActiveScheduleWithDatePassed()
    {
        $date = (new \DateTime('now', new \DateTimeZone('UTC')))->modify('+2 day');
        $scheduleOne = (new ScheduleIntervalStub())
            ->setActiveAt((new \DateTime('now', new \DateTimeZone('UTC')))->modify('-1 day'))
            ->setDeactivateAt((new \DateTime('now', new \DateTimeZone('UTC')))->modify('+1 day'));
        $schedules = new ArrayCollection([$scheduleOne]);
        $this->assertFalse($this->scheduleIntervalChecker->hasActiveSchedule($schedules, $date));
    }

    public function testScheduleWithoutDeactivateAt()
    {
        $date = (new \DateTime('now', new \DateTimeZone('UTC')))->modify('+2 day');
        $scheduleOne = (new ScheduleIntervalStub())
            ->setActiveAt((new \DateTime('now', new \DateTimeZone('UTC')))->modify('-1 day'));
        $schedules = new ArrayCollection([$scheduleOne]);
        $this->assertTrue($this->scheduleIntervalChecker->hasActiveSchedule($schedules, $date));
    }

    public function testScheduleWithoutActivateAt()
    {
        $date = (new \DateTime('now', new \DateTimeZone('UTC')));
        $scheduleOne = (new ScheduleIntervalStub())
            ->setDeactivateAt((new \DateTime('now', new \DateTimeZone('UTC')))->modify('+2 day'));
        $schedules = new ArrayCollection([$scheduleOne]);
        $this->assertTrue($this->scheduleIntervalChecker->hasActiveSchedule($schedules, $date));
    }
}
