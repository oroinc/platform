<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Model\Recurrence;

use Oro\Bundle\CalendarBundle\Entity;
use Oro\Bundle\CalendarBundle\Model\Recurrence;
use Oro\Bundle\CalendarBundle\Model\Recurrence\WeeklyStrategy;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;

class WeeklyStrategyTest extends AbstractTestStrategy
{
    /** @var WeeklyStrategy  */
    protected $strategy;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $validator;

    protected function setUp()
    {
        $this->validator = $this->getMockBuilder('Symfony\Component\Validator\Validator\ValidatorInterface')
            ->getMock();
        /** @var \PHPUnit_Framework_MockObject_MockObject|\Symfony\Component\Translation\TranslatorInterface */
        $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $translator->expects($this->any())
            ->method('transChoice')
            ->will(
                $this->returnCallback(
                    function ($id, $count, array $parameters = []) {
                        return $id . implode($parameters);
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

        /** @var LocaleSettings|\PHPUnit_Framework_MockObject_MockObject $localeSettings */
        $localeSettings = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Model\LocaleSettings')
            ->disableOriginalConstructor()
            ->setMethods(['getTimezone'])
            ->getMock();
        $localeSettings->expects($this->any())
            ->method('getTimezone')
            ->will($this->returnValue('UTC'));

        $this->strategy = new WeeklyStrategy($translator, $dateTimeFormatter, $localeSettings);
    }

    public function testGetName()
    {
        $this->assertEquals($this->strategy->getName(), 'recurrence_weekly');
    }

    public function testSupports()
    {
        $recurrence = new Entity\Recurrence();
        $recurrence->setRecurrenceType(Recurrence::TYPE_WEEKLY);
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
    public function testGetTextValue($recurrenceData, $expected)
    {
        $recurrence = new Entity\Recurrence();
        $recurrence->setRecurrenceType(Recurrence::TYPE_WEEKLY)
            ->setInterval($recurrenceData['interval'])
            ->setDayOfWeek($recurrenceData['dayOfWeek'])
            ->setStartTime(new \DateTime($recurrenceData['startTime'], $this->getTimeZone()))
            ->setTimeZone($recurrenceData['timeZone'])
            ->setEndTime($recurrenceData['endTime'] === null
                ? null
                : new \DateTime($recurrenceData['endTime'], $this->getTimeZone()))
            ->setOccurrences($recurrenceData['occurrences']);

        $calendarEvent = new Entity\CalendarEvent();
        $calendarEvent->setStart(new \DateTime($recurrenceData['startTime']));
        $recurrence->setCalendarEvent($calendarEvent);

        $this->assertEquals($expected, $this->strategy->getTextValue($recurrence));
    }

    /**
     * @param $recurrenceData
     * @param $expected
     *
     * @dataProvider recurrenceLastOccurrenceDataProvider
     */
    public function testGetCalculatedEndTime($recurrenceData, $expected)
    {
        $recurrence = new Entity\Recurrence();
        $recurrence->setRecurrenceType(Recurrence::TYPE_WEEKLY)
            ->setInterval($recurrenceData['interval'])
            ->setDayOfWeek($recurrenceData['dayOfWeek'])
            ->setStartTime(new \DateTime($recurrenceData['startTime'], $this->getTimeZone()))
            ->setTimeZone('UTC')
            ->setOccurrences($recurrenceData['occurrences']);

        if (!empty($recurrenceData['endTime'])) {
            $recurrence->setEndTime(new \DateTime($recurrenceData['endTime'], $this->getTimeZone()));
        }

        $this->assertEquals($expected, $this->strategy->getCalculatedEndTime($recurrence));
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
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
                    '2016-05-23',
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
                    '2016-05-23',
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
                    '2016-05-23',
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
                    'endTime' => null,
                    'occurrences' => null,
                    'timeZone' => 'UTC'
                ],
                'expected' => 'oro.calendar.recurrence.patterns.weekly2oro.calendar.recurrence.days.monday'
            ],
            'with_occurrences' => [
                'params' => [
                    'interval' => 2,
                    'dayOfWeek' => ['monday'],
                    'startTime' => '2016-04-28',
                    'endTime' => null,
                    'occurrences' => 3,
                    'timeZone' => 'UTC'
                ],
                'expected' => 'oro.calendar.recurrence.patterns.weekly2oro.calendar.recurrence.days'
                    . '.mondayoro.calendar.recurrence.patterns.occurrences3'
            ],
            'with_end_date' => [
                'params' => [
                    'interval' => 2,
                    'dayOfWeek' => ['monday'],
                    'startTime' => '2016-04-28',
                    'endTime' => '2016-06-10',
                    'occurrences' => null,
                    'timeZone' => 'UTC'
                ],
                'expected' => 'oro.calendar.recurrence.patterns.weekly2oro.calendar.recurrence.days'
                    . '.mondayoro.calendar.recurrence.patterns.end_date'
            ],
            'with_weekdays' => [
                'params' => [
                    'interval' => 1,
                    'dayOfWeek' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                    'startTime' => '2016-04-28',
                    'endTime' => '2016-06-10',
                    'occurrences' => null,
                    'timeZone' => 'UTC'
                ],
                'expected' => 'oro.calendar.recurrence.patterns.weekdayoro.calendar.recurrence.patterns.end_date'
            ],
            'with_timezone' => [
                'params' => [
                    'interval' => 2,
                    'dayOfWeek' => ['monday'],
                    'startTime' => '2016-04-28T04:00:00+00:00',
                    'endTime' => null,
                    'occurrences' => null,
                    'timeZone' => 'America/Los_Angeles'
                ],
                'expected' => 'oro.calendar.recurrence.patterns.weekly2oro.calendar.recurrence.days.monday'
                    . 'oro.calendar.recurrence.patterns.timezone'
            ],
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
                'expected' => new \DateTime(Recurrence::MAX_END_DATE, $this->getTimeZone())
            ],
            'with_end_date' => [
                'params' => [
                    'interval' => 2,
                    'dayOfWeek' => ['monday'],
                    'startTime' => '2016-04-28',
                    'endTime' => '2016-05-12',
                    'occurrences' => null,
                ],
                'expected' => new \DateTime('2016-05-12', $this->getTimeZone())
            ],
            'with_occurrences' => [
                'params' => [
                    'interval' => 2,
                    'dayOfWeek' => ['monday', 'tuesday'],
                    'startTime' => '2016-04-25',
                    'endTime' => null,
                    'occurrences' => 5,
                ],
                'expected' => new \DateTime('2016-05-23', $this->getTimeZone())
            ],
            'with_occurrences_1' => [
                'params' => [
                    'interval' => 2,
                    'dayOfWeek' => ['saturday', 'sunday', 'monday'],
                    'startTime' => '2016-04-26',
                    'endTime' => null,
                    'occurrences' => 5,
                ],
                'expected' => new \DateTime('2016-05-22', $this->getTimeZone())
            ],
            'with_occurrences_2' => [
                'params' => [
                    'interval' => 2,
                    'dayOfWeek' => ['wednesday', 'friday'],
                    'startTime' => '2016-05-02',
                    'endTime' => null,
                    'occurrences' => 8,
                ],
                'expected' => new \DateTime('2016-06-17', $this->getTimeZone())
            ],
            'with_occurrences_3' => [
                'params' => [
                    'interval' => 3,
                    'dayOfWeek' => ['sunday', 'saturday'],
                    'startTime' => '2016-05-02',
                    'endTime' => null,
                    'occurrences' => 111,
                ],
                'expected' => new \DateTime('2019-07-06', $this->getTimeZone())
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getType()
    {
        return Recurrence::TYPE_WEEKLY;
    }
}
