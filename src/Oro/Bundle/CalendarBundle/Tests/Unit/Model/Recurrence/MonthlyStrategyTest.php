<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Model\Recurrence;

use Oro\Bundle\CalendarBundle\Entity\Recurrence;
use Oro\Bundle\CalendarBundle\Model\Recurrence\MonthlyStrategy;
use Symfony\Component\Translation\TranslatorInterface;

class MonthlyStrategyTest extends \PHPUnit_Framework_TestCase
{
    /** @var MonthlyStrategy  */
    protected $strategy;

    protected function setUp()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface */
        $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $translator->expects($this->any())
            ->method('transChoice')
            ->will(
                $this->returnCallback(
                    function ($id, $count, array $parameters = []) {
                        return $id;
                    }
                )
            );
        $translator->expects($this->any())
            ->method('trans')
            ->will(
                $this->returnCallback(
                    function ($id) {
                        return $id;
                    }
                )
            );
        $dateTimeFormatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->strategy = new MonthlyStrategy($translator, $dateTimeFormatter);
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

    /**
     * @param $recurrenceData
     * @param $expected
     *
     * @dataProvider recurrencePatternsDataProvider
     */
    public function testGetRecurrencePattern($recurrenceData, $expected)
    {
        $recurrence = new Recurrence();
        $recurrence->setRecurrenceType(Recurrence::TYPE_MONTHLY)
            ->setInterval($recurrenceData['interval'])
            ->setDayOfMonth($recurrenceData['dayOfMonth'])
            ->setStartTime(new \DateTime($recurrenceData['startTime']))
            ->setEndTime(new \DateTime($recurrenceData['endTime']))
            ->setOccurrences($recurrenceData['occurrences']);

        $this->assertEquals($expected, $this->strategy->getRecurrencePattern($recurrence));
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

    /**
     * @return array
     */
    public function recurrencePatternsDataProvider()
    {
        return [
            'without_occurrences_and_end_date' => [
                'params' => [
                    'interval' => 2,
                    'dayOfMonth' => 10,
                    'startTime' => '2016-04-28',
                    'endTime' => Recurrence::MAX_END_DATE,
                    'occurrences' => null,
                ],
                'expected' => 'oro.calendar.recurrence.patterns.monthly'
            ],
            'with_occurrences' => [
                'params' => [
                    'interval' => 2,
                    'dayOfMonth' => 10,
                    'startTime' => '2016-04-28',
                    'endTime' => Recurrence::MAX_END_DATE,
                    'occurrences' => 3,
                ],
                'expected' => 'oro.calendar.recurrence.patterns.monthlyoro.calendar.recurrence.patterns.occurrences'
            ],
            'with_end_date' => [
                'params' => [
                    'interval' => 2,
                    'dayOfMonth' => 10,
                    'startTime' => '2016-04-28',
                    'endTime' => '2016-06-10',
                    'occurrences' => null,
                ],
                'expected' => 'oro.calendar.recurrence.patterns.monthlyoro.calendar.recurrence.patterns.end_date'
            ]
        ];
    }
}
