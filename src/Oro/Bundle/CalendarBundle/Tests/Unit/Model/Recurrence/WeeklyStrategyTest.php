<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Model\Recurrence;

use Oro\Bundle\CalendarBundle\Entity\Recurrence;
use Oro\Bundle\CalendarBundle\Model\Recurrence\WeeklyStrategy;
use Symfony\Component\Translation\TranslatorInterface;

class WeeklyStrategyTest extends \PHPUnit_Framework_TestCase
{
    /** @var WeeklyStrategy  */
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

        $this->strategy = new WeeklyStrategy($translator, $dateTimeFormatter);
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

    /**
     * @param $recurrenceData
     * @param $expected
     *
     * @dataProvider recurrencePatternsDataProvider
     */
    public function testGetRecurrencePattern($recurrenceData, $expected)
    {
        $recurrence = new Recurrence();
        $recurrence->setRecurrenceType(Recurrence::TYPE_WEEKLY)
            ->setInterval($recurrenceData['interval'])
            ->setDayOfWeek($recurrenceData['dayOfWeek'])
            ->setStartTime(new \DateTime($recurrenceData['startTime']))
            ->setEndTime(new \DateTime($recurrenceData['endTime']))
            ->setOccurrences($recurrenceData['occurrences']);

        $this->assertEquals($expected, $this->strategy->getRecurrencePattern($recurrence));
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
                    'dayOfWeek' => ['monday'],
                    'startTime' => '2016-04-28',
                    'endTime' => Recurrence::MAX_END_DATE,
                    'occurrences' => null,
                ],
                'expected' => 'oro.calendar.recurrence.patterns.weekly'
            ],
            'with_occurrences' => [
                'params' => [
                    'interval' => 2,
                    'dayOfWeek' => ['monday'],
                    'startTime' => '2016-04-28',
                    'endTime' => Recurrence::MAX_END_DATE,
                    'occurrences' => 3,
                ],
                'expected' => 'oro.calendar.recurrence.patterns.weeklyoro.calendar.recurrence.patterns.occurrences'
            ],
            'with_end_date' => [
                'params' => [
                    'interval' => 2,
                    'dayOfWeek' => ['monday'],
                    'startTime' => '2016-04-28',
                    'endTime' => '2016-06-10',
                    'occurrences' => null,
                ],
                'expected' => 'oro.calendar.recurrence.patterns.weeklyoro.calendar.recurrence.patterns.end_date'
            ]
        ];
    }
}
