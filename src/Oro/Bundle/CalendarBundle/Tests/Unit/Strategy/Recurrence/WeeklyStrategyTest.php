<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Model\Recurrence;

use Oro\Bundle\CalendarBundle\Entity\Recurrence;
use Oro\Bundle\CalendarBundle\Strategy\Recurrence\Helper\StrategyHelper;
use Oro\Bundle\CalendarBundle\Strategy\Recurrence\WeeklyStrategy;

class WeeklyStrategyTest extends \PHPUnit_Framework_TestCase
{
    /** @var WeeklyStrategy  */
    protected $strategy;

    protected function setUp()
    {
        $helper = new StrategyHelper();
        /** @var \PHPUnit_Framework_MockObject_MockObject|\Symfony\Component\Translation\TranslatorInterface */
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

        $this->strategy = new WeeklyStrategy($helper, $translator, $dateTimeFormatter);
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

    /**
     * @expectedException \RuntimeException
     */
    public function testGetOccurrencesWithWrongIntervalValue()
    {
        $recurrence = new Recurrence();
        $recurrence->setDayOfWeek([
            'sunday',
            'monday',
        ]);
        $recurrence->setInterval(-1.5);
        $this->strategy->getOccurrences(
            $recurrence,
            new \DateTime(),
            new \DateTime()
        );
    }

    /**
     * @param array $params
     * @param array $expected
     *
     * @dataProvider propertiesDataProvider
     */
    public function testGetOccurrences(array $params, array $expected)
    {
        // @TODO move method body to abstract test class
        $expected = array_map(
            function ($date) {
                return new \DateTime($date);
            }, $expected
        );
        $recurrence = new Recurrence();
        $recurrence->setRecurrenceType(Recurrence::TYPE_WEEKLY)
            ->setInterval($params['interval'])
            ->setDayOfWeek($params['daysOfWeek'])
            ->setStartTime(new \DateTime($params['startTime']))
            ->setEndTime(new \DateTime($params['endTime']));
        if ($params['occurrences']) {
            $recurrence->setOccurrences($params['occurrences']);
        }
        $result = $this->strategy->getOccurrences(
            $recurrence,
            new \DateTime($params['start']),
            new \DateTime($params['end'])
        );
        $this->assertEquals($expected, $result);
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
     * @param $recurrenceData
     * @param $expected
     *
     * @dataProvider recurrenceLastOccurrenceDataProvider
     */
    public function testGetLastOccurrenceDate($recurrenceData, $expected)
    {
        $recurrence = new Recurrence();
        $recurrence->setRecurrenceType(Recurrence::TYPE_WEEKLY)
            ->setInterval($recurrenceData['interval'])
            ->setDayOfWeek($recurrenceData['dayOfWeek'])
            ->setStartTime(new \DateTime($recurrenceData['startTime']))
            ->setOccurrences($recurrenceData['occurrences']);

        if (!empty($recurrenceData['endTime'])) {
            $recurrence->setEndTime(new \DateTime($recurrenceData['endTime']));
        }

        $this->assertEquals($expected, $this->strategy->getLastOccurrenceDate($recurrence));
    }

    /**
     * @return array
     */
    public function propertiesDataProvider()
    {
        return [
            /**
             * |-----|
             *         |-----|
             */
            'start < end < startTime < endTime' => [
                'params' => [
                    'daysOfWeek' => [
                        'sunday',
                        'monday',
                    ],
                    'interval' => 2,
                    'occurrences' => null,
                    'start' => '2016-03-28',
                    'end' => '2016-04-18',
                    'startTime' => '2016-04-25',
                    'endTime' => '2016-06-10',
                ],
                'expected' => [
                ],
            ],
            /**
             * |-----|
             *   |-----|
             */
            'start < startTime < end < endTime' => [
                'params' => [
                    'daysOfWeek' => [
                        'sunday',
                        'monday',
                    ],
                    'interval' => 2,
                    'occurrences' => null,
                    'start' => '2016-03-28',
                    'end' => '2016-05-01',
                    'startTime' => '2016-04-25',
                    'endTime' => '2016-06-10',
                ],
                'expected' => [
                    '2016-04-25',
                ],
            ],
            /**
             * |-----|
             *   |-|
             */
            'start < startTime < endTime < end' => [
                'params' => [
                    'daysOfWeek' => [
                        'sunday',
                        'monday',
                    ],
                    'interval' => 2,
                    'occurrences' => null,
                    'start' => '2016-03-28',
                    'end' => '2016-07-20',
                    'startTime' => '2016-04-25',
                    'endTime' => '2016-06-13',
                ],
                'expected' => [
                    '2016-04-25',
                    '2016-05-08',
                    '2016-05-09',
                    '2016-05-22',
                    '2016-05-23',
                    '2016-06-05',
                    '2016-06-06',
                ],
            ],
            /**
             *     |-----|
             * |-----|
             */
            'startTime < start < endTime < end after x occurrences' => [
                'params' => [
                    'daysOfWeek' => [
                        'sunday',
                        'monday',
                    ],
                    'interval' => 2,
                    'occurrences' => 4,
                    'start' => '2016-05-01',
                    'end' => '2016-07-03',
                    'startTime' => '2016-04-25',
                    'endTime' => '2016-06-10',
                ],
                'expected' => [
                    '2016-05-08',
                    '2016-05-09',
                    '2016-05-22',
                ],
            ],
            /**
             *         |-----|
             * |-----|
             */
            'startTime < endTime < start < end' => [
                'params' => [
                    'daysOfWeek' => [
                        'sunday',
                        'monday',
                    ],
                    'interval' => 2,
                    'occurrences' => null,
                    'start' => '2016-06-12',
                    'end' => '2016-07-20',
                    'startTime' => '2016-04-25',
                    'endTime' => '2016-06-10',
                ],
                'expected' => [
                ],
            ],

            'start = end = startTime = endTime' => [
                'params' => [
                    'daysOfWeek' => [
                        'sunday',
                        'monday',
                    ],
                    'interval' => 2,
                    'occurrences' => null,
                    'start' => '2016-04-25',
                    'end' => '2016-04-25',
                    'startTime' => '2016-04-25',
                    'endTime' => '2016-04-25',
                ],
                'expected' => [
                    '2016-04-25',
                ],
            ],
            'start = end = (startTime - 1 day) = (endTime - 1 day)' => [
                'params' => [
                    'daysOfWeek' => [
                        'sunday',
                        'monday',
                    ],
                    'interval' => 2,
                    'occurrences' => null,
                    'start' => '2016-04-25',
                    'end' => '2016-04-25',
                    'startTime' => '2016-04-24',
                    'endTime' => '2016-04-24',
                ],
                'expected' => [
                ],
            ],
            'startTime = endTime = (start + interval) = (end + interval)' => [
                'params' => [
                    'daysOfWeek' => [
                        'sunday',
                        'monday',
                    ],
                    'interval' => 2,
                    'occurrences' => null,
                    'start' => '2016-04-25',
                    'end' => '2016-04-25',
                    'startTime' => '2016-05-08',
                    'endTime' => '2016-0-08',
                ],
                'expected' => [
                ],
            ],
            'startTime < start < end < endTime' => [
                'params' => [
                    'daysOfWeek' => [
                        'sunday',
                        'monday',
                    ],
                    'interval' => 2,
                    'occurrences' => null,
                    'start' => '2016-05-01',
                    'end' => '2016-05-27',
                    'startTime' => '2016-04-25',
                    'endTime' => '2016-06-10',
                ],
                'expected' => [
                    '2016-05-08',
                    '2016-05-09',
                    '2016-05-22',
                    '2016-05-23',
                ],
            ],
            'startTime < start < end < endTime after x occurrences' => [
                'params' => [
                    'daysOfWeek' => [
                        'sunday',
                        'monday',
                    ],
                    'interval' => 2,
                    'occurrences' => 4,
                    'start' => '2016-05-01',
                    'end' => '2016-05-27',
                    'startTime' => '2016-04-25',
                    'endTime' => '2016-06-10',
                ],
                'expected' => [
                    '2016-05-08',
                    '2016-05-09',
                    '2016-05-22',
                ],
            ],
            'no endTime' => [
                'params' => [
                    'daysOfWeek' => [
                        'sunday',
                        'monday',
                    ],
                    'interval' => 2,
                    'occurrences' => 4,
                    'start' => '2016-05-01',
                    'end' => '2016-07-03',
                    'startTime' => '2016-04-25',
                    'endTime' => '9999-12-31',
                ],
                'expected' => [
                    '2016-05-08',
                    '2016-05-09',
                    '2016-05-22',
                ],
            ],
        ];
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

    /**
     * @return array
     */
    public function recurrenceLastOccurrenceDataProvider()
    {
        return [
            'without_end_date' => [
                'params' => [
                    'interval' => 2,
                    'dayOfWeek' => ['monday'],
                    'startTime' => '2016-04-28',
                    'endTime' => null,
                    'occurrences' => null,
                ],
                'expected' => new \DateTime(Recurrence::MAX_END_DATE)
            ],
            'with_end_date' => [
                'params' => [
                    'interval' => 2,
                    'dayOfWeek' => ['monday'],
                    'startTime' => '2016-04-28',
                    'endTime' => '2016-05-12',
                    'occurrences' => null,
                ],
                'expected' => new \DateTime('2016-05-12')
            ],
            'with_occurrences' => [
                'params' => [
                    'interval' => 2,
                    'dayOfWeek' => ['monday', 'tuesday'],
                    'startTime' => '2016-04-25',
                    'endTime' => null,
                    'occurrences' => 5,
                ],
                'expected' => new \DateTime('2016-05-23')
            ],
            'with_occurrences_1' => [
                'params' => [
                    'interval' => 2,
                    'dayOfWeek' => ['saturday', 'sunday', 'monday'],
                    'startTime' => '2016-04-26',
                    'endTime' => null,
                    'occurrences' => 5,
                ],
                'expected' => new \DateTime('2016-05-22')
            ],
            'with_occurrences_2' => [
                'params' => [
                    'interval' => 2,
                    'dayOfWeek' => ['wednesday', 'friday'],
                    'startTime' => '2016-05-02',
                    'endTime' => null,
                    'occurrences' => 8,
                ],
                'expected' => new \DateTime('2016-06-17')
            ],
            'with_occurrences_3' => [
                'params' => [
                    'interval' => 3,
                    'dayOfWeek' => ['sunday', 'saturday'],
                    'startTime' => '2016-05-02',
                    'endTime' => null,
                    'occurrences' => 111,
                ],
                'expected' => new \DateTime('2019-07-06')
            ]
        ];
    }
}
