<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Model\Recurrence;

use Oro\Bundle\CalendarBundle\Entity\Recurrence;
use Oro\Bundle\CalendarBundle\Model\Recurrence\YearNthStrategy;
use Oro\Bundle\CalendarBundle\Tools\Recurrence\NthStrategyHelper;

class YearNthStrategyTest extends \PHPUnit_Framework_TestCase
{
    /** @var YearNthStrategy  */
    protected $strategy;

    protected function setUp()
    {
        $helper = new NthStrategyHelper();
        $this->strategy = new YearNthStrategy($helper);
    }

    public function testGetName()
    {
        $this->assertEquals($this->strategy->getName(), 'recurrence_yearnth');
    }

    public function testSupports()
    {
        $recurrence = new Recurrence();
        $recurrence->setRecurrenceType(Recurrence::TYPE_YEAR_N_TH);
        $this->assertTrue($this->strategy->supports($recurrence));

        $recurrence->setRecurrenceType('Test');
        $this->assertFalse($this->strategy->supports($recurrence));
    }

    public function testGetOccurrences()
    {
        $recurrence = new Recurrence();
        $recurrence->setRecurrenceType(Recurrence::TYPE_YEAR_N_TH)
            ->setInterval(12)
            ->setMonthOfYear(4)
            ->setDayOfWeek(['monday'])
            ->setInstance(Recurrence::INSTANCE_FIRST)
            ->setStartTime(new \DateTime('2016-04-25'))
            ->setEndTime(new \DateTime('2020-06-10'));

        $result = $this->strategy->getOccurrences(
            $recurrence,
            new \DateTime('2016-03-28'),
            new \DateTime('2016-05-01')
        );

        $this->assertEquals([], $result);

        $result = $this->strategy->getOccurrences(
            $recurrence,
            new \DateTime('2017-03-28'),
            new \DateTime('2017-05-01')
        );

        $this->assertEquals([new \DateTime('2017-04-03')], $result);

        $recurrence->setInstance(Recurrence::INSTANCE_LAST);
        $result = $this->strategy->getOccurrences(
            $recurrence,
            new \DateTime('2016-03-28'),
            new \DateTime('2016-05-01')
        );
        $this->assertEquals([new \DateTime('2016-04-25')], $result);

        $result = $this->strategy->getOccurrences(
            $recurrence,
            new \DateTime('2022-03-28'),
            new \DateTime('2022-05-01')
        );

        $this->assertEquals([], $result);

        $recurrence->setOccurrences(2);
        $recurrence->setEndTime(new \DateTime('2022-12-31'));
        $result = $this->strategy->getOccurrences(
            $recurrence,
            new \DateTime('2018-03-28'),
            new \DateTime('2018-05-01')
        );
        $this->assertEquals([], $result);

        $result = $this->strategy->getOccurrences(
            $recurrence,
            new \DateTime('2017-03-28'),
            new \DateTime('2017-05-01')
        );
        $this->assertEquals([new \DateTime('2017-04-24')], $result);
    }
}
