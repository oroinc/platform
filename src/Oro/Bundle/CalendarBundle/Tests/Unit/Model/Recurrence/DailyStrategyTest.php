<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Model\Recurrence;

use Oro\Bundle\CalendarBundle\Entity\Recurrence;
use Oro\Bundle\CalendarBundle\Model\Recurrence\DailyStrategy;

class DailyStrategyTest extends \PHPUnit_Framework_TestCase
{
    /** @var DailyStrategy  */
    protected $strategy;

    protected function setUp()
    {
        $this->strategy = new DailyStrategy();
    }

    public function testGetName()
    {
        $this->assertEquals($this->strategy->getName(), 'recurrence_daily');
    }

    public function testSupports()
    {
        $recurrence = new Recurrence();
        $recurrence->setRecurrenceType(Recurrence::TYPE_DAILY);
        $this->assertTrue($this->strategy->supports($recurrence));

        $recurrence->setRecurrenceType('Test');
        $this->assertFalse($this->strategy->supports($recurrence));
    }

    public function testGetOccurrences()
    {
        $recurrence = new Recurrence();
        $recurrence->setRecurrenceType(Recurrence::TYPE_DAILY)
            ->setInterval(5)
            ->setStartTime(new \DateTime('2016-04-25'))
            ->setEndTime(new \DateTime('2016-06-10'));

        $result = $this->strategy->getOccurrences(
            $recurrence,
            new \DateTime('2016-03-28'),
            new \DateTime('2016-05-01')
        );

        $expectedResult = [
            new \DateTime('2016-04-25'),
            new \DateTime('2016-04-30'),
        ];

        $this->assertEquals($expectedResult, $result);

        $result = $this->strategy->getOccurrences(
            $recurrence,
            new \DateTime('2016-03-28'),
            new \DateTime('2016-04-17')
        );

        $this->assertEquals([], $result);

        $result = $this->strategy->getOccurrences(
            $recurrence,
            new \DateTime('2016-05-30'),
            new \DateTime('2016-07-03')
        );

        $expectedResult = [
            new \DateTime('2016-05-30'),
            new \DateTime('2016-06-04'),
            new \DateTime('2016-06-09'),
        ];

        $this->assertEquals($expectedResult, $result);

        $recurrence->setOccurrences(8);
        $result = $this->strategy->getOccurrences(
            $recurrence,
            new \DateTime('2016-05-30'),
            new \DateTime('2016-07-03')
        );
        $this->assertEquals([new \DateTime('2016-05-30')], $result);
    }
}
