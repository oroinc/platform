<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Model\Recurrence;

use Oro\Bundle\CalendarBundle\Entity\Recurrence;
use Oro\Bundle\CalendarBundle\Model\Recurrence\MonthlyStrategy;

class MonthlyStrategyTest extends \PHPUnit_Framework_TestCase
{
    /** @var MonthlyStrategy  */
    protected $strategy;

    protected function setUp()
    {
        $this->strategy = new MonthlyStrategy();
    }

    public function testGetName()
    {
        $this->assertEquals($this->strategy->getName(), 'recurrence_monthly');
    }

    public function testSupports()
    {
        $recurrence = new Recurrence();
        $recurrence->setRecurrenceType(Recurrence::TYPE_MONTHLY);
        $this->assertTrue($this->strategy->supports($recurrence));

        $recurrence->setRecurrenceType('Test');
        $this->assertFalse($this->strategy->supports($recurrence));
    }

    public function testGetOccurrences()
    {
        $recurrence = new Recurrence();
        $recurrence->setRecurrenceType(Recurrence::TYPE_MONTHLY)
            ->setInterval(2)
            ->setDayOfMonth(25)
            ->setStartTime(new \DateTime('2016-04-25'))
            ->setEndTime(new \DateTime('2016-06-30'));

        $result = $this->strategy->getOccurrences(
            $recurrence,
            new \DateTime('2016-03-28'),
            new \DateTime('2016-05-01')
        );

        $this->assertEquals([new \DateTime('2016-04-25')], $result);

        $result = $this->strategy->getOccurrences(
            $recurrence,
            new \DateTime('2016-05-30'),
            new \DateTime('2016-07-03')
        );

        $this->assertEquals([new \DateTime('2016-06-25')], $result);

        $result = $this->strategy->getOccurrences(
            $recurrence,
            new \DateTime('2016-07-25'),
            new \DateTime('2016-09-04')
        );

        $this->assertEquals([], $result);

        $recurrence->setOccurrences(3);
        $recurrence->setEndTime(new \DateTime('2016-12-31'));
        $result = $this->strategy->getOccurrences(
            $recurrence,
            new \DateTime('2016-07-25'),
            new \DateTime('2016-09-04')
        );

        $this->assertEquals([new \DateTime('2016-08-25')], $result);

        $result = $this->strategy->getOccurrences(
            $recurrence,
            new \DateTime('2016-09-26'),
            new \DateTime('2016-11-06')
        );

        $this->assertEquals([], $result);
    }
}
