<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Model\Recurrence;

use Oro\Bundle\CalendarBundle\Entity\Recurrence;
use Oro\Bundle\CalendarBundle\Model\Recurrence\YearlyStrategy;

class YearlyStrategyTest extends \PHPUnit_Framework_TestCase
{
    /** @var YearlyStrategy  */
    protected $strategy;

    protected function setUp()
    {
        $this->strategy = new YearlyStrategy();
    }

    public function testGetName()
    {
        $this->assertEquals($this->strategy->getName(), 'recurrence_yearly');
    }

    public function testSupports()
    {
        $recurrence = new Recurrence();
        $recurrence->setRecurrenceType(Recurrence::TYPE_YEARLY);
        $this->assertTrue($this->strategy->supports($recurrence));

        $recurrence->setRecurrenceType('Test');
        $this->assertFalse($this->strategy->supports($recurrence));
    }

    public function testGetOccurrences()
    {
        $recurrence = new Recurrence();
        $recurrence->setRecurrenceType(Recurrence::TYPE_YEARLY)
            ->setInterval(12)
            ->setDayOfMonth(25)
            ->setMonthOfYear(4)
            ->setStartTime(new \DateTime('2016-04-25'))
            ->setEndTime(new \DateTime('2019-06-30'));

        $result = $this->strategy->getOccurrences(
            $recurrence,
            new \DateTime('2016-03-28'),
            new \DateTime('2016-05-01')
        );

        $this->assertEquals([new \DateTime('2016-04-25')], $result);

        $result = $this->strategy->getOccurrences(
            $recurrence,
            new \DateTime('2017-05-30'),
            new \DateTime('2017-07-03')
        );

        $this->assertEquals([], $result);

        $result = $this->strategy->getOccurrences(
            $recurrence,
            new \DateTime('2021-03-28'),
            new \DateTime('2021-05-01')
        );

        $this->assertEquals([], $result);

        $recurrence->setOccurrences(2);
        $recurrence->setEndTime(new \DateTime('2026-12-31'));
        $result = $this->strategy->getOccurrences(
            $recurrence,
            new \DateTime('2019-03-28'),
            new \DateTime('2019-05-01')
        );

        $result = $this->strategy->getOccurrences(
            $recurrence,
            new \DateTime('2017-03-28'),
            new \DateTime('2017-05-01')
        );

        $this->assertEquals([new \DateTime('2017-04-25')], $result);
    }
}
