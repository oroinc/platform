<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Model\Recurrence;

use Oro\Bundle\CalendarBundle\Entity\Recurrence;
use Oro\Bundle\CalendarBundle\Model\Recurrence\WeeklyStrategy;

class WeeklyStrategyTest extends \PHPUnit_Framework_TestCase
{
    /** @var WeeklyStrategy  */
    protected $strategy;

    protected function setUp()
    {
        $this->strategy = new WeeklyStrategy();
    }

    public function testGetName()
    {
        $this->assertEquals($this->strategy->getName(), 'recurrence_weekly');
    }

    public function testSupports()
    {
        $recurrence = new Recurrence();
        $recurrence->setRecurrenceType(Recurrence::TYPE_WEEKLY);
        $this->assertTrue($this->strategy->supports($recurrence));

        $recurrence->setRecurrenceType('Test');
        $this->assertFalse($this->strategy->supports($recurrence));
    }

    public function testGetOccurrences()
    {
        $recurrence = new Recurrence();
        $recurrence->setRecurrenceType(Recurrence::TYPE_WEEKLY)
            ->setInterval(2)
            ->setDayOfWeek(['sunday', 'monday'])
            ->setStartTime(new \DateTime('2016-04-25'))
            ->setEndTime(new \DateTime('2016-06-10'));

        $result = $this->strategy->getOccurrences(
            $recurrence,
            new \DateTime('2016-03-28'),
            new \DateTime('2016-05-01')
        );

        $this->assertEquals([new \DateTime('2016-04-25')], $result);

        $result = $this->strategy->getOccurrences(
            $recurrence,
            new \DateTime('2016-05-01'),
            new \DateTime('2016-05-27')
        );

        $expectedResult = [
            new \DateTime('2016-05-08'),
            new \DateTime('2016-05-09'),
            new \DateTime('2016-05-22'),
            new \DateTime('2016-05-23'),
        ];

        $this->assertEquals($expectedResult, $result);

        $recurrence->setOccurrences(4);
        $result = $this->strategy->getOccurrences(
            $recurrence,
            new \DateTime('2016-05-01'),
            new \DateTime('2016-05-27')
        );

        $expectedResult = [
            new \DateTime('2016-05-08'),
            new \DateTime('2016-05-09'),
            new \DateTime('2016-05-22'),
        ];

        $this->assertEquals($expectedResult, $result);

        $result = $this->strategy->getOccurrences(
            $recurrence,
            new \DateTime('2016-05-30'),
            new \DateTime('2016-07-03')
        );

        $this->assertEquals([], $result);
    }
}
