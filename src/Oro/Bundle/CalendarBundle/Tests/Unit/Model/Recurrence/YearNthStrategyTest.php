<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Model\Recurrence;

use Oro\Bundle\CalendarBundle\Entity;
use Oro\Bundle\CalendarBundle\Model\Recurrence;
use Oro\Bundle\CalendarBundle\Model\Recurrence\YearNthStrategy;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;

class YearNthStrategyTest extends AbstractTestStrategy
{
    /** @var YearNthStrategy  */
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

        $this->strategy = new YearNthStrategy($translator, $dateTimeFormatter, $localeSettings);
    }

    public function testGetName()
    {
        $this->assertEquals($this->strategy->getName(), 'recurrence_yearnth');
    }

    public function testSupports()
    {
        $recurrence = new Entity\Recurrence();
        $recurrence->setRecurrenceType(Recurrence::TYPE_YEAR_N_TH);
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
        $recurrence->setRecurrenceType(Recurrence::TYPE_YEAR_N_TH)
            ->setInterval($recurrenceData['interval'])
            ->setInstance($recurrenceData['instance'])
            ->setDayOfWeek($recurrenceData['dayOfWeek'])
            ->setMonthOfYear($recurrenceData['monthOfYear'])
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
        $recurrence->setRecurrenceType(Recurrence::TYPE_YEAR_N_TH)
            ->setInterval($recurrenceData['interval'])
            ->setInstance($recurrenceData['instance'])
            ->setDayOfWeek($recurrenceData['dayOfWeek'])
            ->setMonthOfYear($recurrenceData['monthOfYear'])
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
                    'instance' => Recurrence::INSTANCE_FIRST,
                    'interval' => 12, // a number of months, which is a multiple of 12
                    'monthOfYear' => 4,
                    'daysOfWeek' => [
                        'monday',
                    ],
                    'occurrences' => null,
                    'start' => '2015-03-28',
                    'end' => '2016-04-01',
                    'startTime' => '2016-04-25',
                    'endTime' => '2020-06-10',
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
                    'instance' => Recurrence::INSTANCE_FIRST,
                    'interval' => 12, // a number of months, which is a multiple of 12
                    'monthOfYear' => 4,
                    'daysOfWeek' => [
                        'monday',
                    ],
                    'occurrences' => null,
                    'start' => '2015-03-01',
                    'end' => '2018-03-01',
                    'startTime' => '2016-04-25',
                    'endTime' => '2020-03-01',
                ],
                'expected' => [
                    '2017-04-03',
                ],
            ],
            /**
             * |-----|
             *   |-|
             */
            'start < startTime < endTime < end' => [
                'params' => [
                    'instance' => Recurrence::INSTANCE_FIRST,
                    'interval' => 12, // a number of months, which is a multiple of 12
                    'monthOfYear' => 4,
                    'daysOfWeek' => [
                        'monday',
                    ],
                    'occurrences' => null,
                    'start' => '2015-03-01',
                    'end' => '2020-03-01',
                    'startTime' => '2016-04-25',
                    'endTime' => '2018-03-01',
                ],
                'expected' => [
                    '2017-04-03',
                ],
            ],
            /**
             *     |-----|
             * |-----|
             */
            'startTime < start < endTime < end' => [
                'params' => [
                    'instance' => Recurrence::INSTANCE_FIRST,
                    'interval' => 12, // a number of months, which is a multiple of 12
                    'monthOfYear' => 4,
                    'daysOfWeek' => [
                        'monday',
                    ],
                    'occurrences' => null,
                    'start' => '2017-03-01',
                    'end' => '2019-08-01',
                    'startTime' => '2016-04-25',
                    'endTime' => '2018-08-01',
                ],
                'expected' => [
                    '2017-04-03',
                    '2018-04-02',
                ],
            ],
            /**
             *         |-----|
             * |-----|
             */
            'startTime < endTime < start < end' => [
                'params' => [
                    'instance' => Recurrence::INSTANCE_FIRST,
                    'interval' => 12, // a number of months, which is a multiple of 12
                    'monthOfYear' => 4,
                    'daysOfWeek' => [
                        'monday',
                    ],
                    'occurrences' => null,
                    'start' => '2022-03-28',
                    'end' => '2022-05-01',
                    'startTime' => '2016-04-25',
                    'endTime' => '2020-06-10',
                ],
                'expected' => [
                ],
            ],

            'start < startTime < end < endTime with no result' => [
                'params' => [
                    'instance' => Recurrence::INSTANCE_FIRST,
                    'interval' => 12, // a number of months, which is a multiple of 12
                    'monthOfYear' => 4,
                    'daysOfWeek' => [
                        'monday',
                    ],
                    'occurrences' => null,
                    'start' => '2016-03-28',
                    'end' => '2016-05-01',
                    'startTime' => '2016-04-25',
                    'endTime' => '2020-06-10',
                ],
                'expected' => [
                ],
            ],
            'startTime < start < end < endTime with X occurrences' => [
                'params' => [
                    'instance' => Recurrence::INSTANCE_LAST,
                    'interval' => 12, // a number of months, which is a multiple of 12
                    'monthOfYear' => 4,
                    'daysOfWeek' => [
                        'monday',
                    ],
                    'occurrences' => 2,
                    'start' => '2018-03-28',
                    'end' => '2022-05-01',
                    'startTime' => '2016-04-25',
                    'endTime' => '2022-12-31',
                ],
                'expected' => [
                ],
            ],
            'startTime < start < end < endTime with Y occurrences' => [
                'params' => [
                    'instance' => Recurrence::INSTANCE_LAST,
                    'interval' => 12, // a number of months, which is a multiple of 12
                    'monthOfYear' => 4,
                    'daysOfWeek' => [
                        'monday',
                    ],
                    'occurrences' => 2,
                    'start' => '2017-03-28',
                    'end' => '2017-05-01',
                    'startTime' => '2016-04-25',
                    'endTime' => '2022-12-31',
                ],
                'expected' => [
                    '2017-04-24',
                ],
            ],
            'start < startTime < end < endTime with last instance' => [
                'params' => [
                    'instance' => Recurrence::INSTANCE_LAST,
                    'interval' => 12, // a number of months, which is a multiple of 12
                    'monthOfYear' => 4,
                    'daysOfWeek' => [
                        'monday',
                    ],
                    'occurrences' => null,
                    'start' => '2016-03-28',
                    'end' => '2016-05-01',
                    'startTime' => '2016-04-25',
                    'endTime' => '2020-06-10',
                ],
                'expected' => [
                    '2016-04-25',
                ],
            ],
            'startTime < start < end < endTime' => [
                'params' => [
                    'instance' => Recurrence::INSTANCE_FIRST,
                    'interval' => 12, // a number of months, which is a multiple of 12
                    'monthOfYear' => 4,
                    'daysOfWeek' => [
                        'monday',
                    ],
                    'occurrences' => null,
                    'start' => '2017-03-28',
                    'end' => '2017-05-01',
                    'startTime' => '2016-04-25',
                    'endTime' => '2020-06-10',
                ],
                'expected' => [
                    '2017-04-03',
                ],
            ],
            'with_weekend_day' => [
                'params' => [
                    'instance' => Recurrence::INSTANCE_FOURTH,
                    'interval' => 12, // a number of months, which is a multiple of 12
                    'monthOfYear' => 4,
                    'daysOfWeek' => [
                        'sunday',
                        'saturday'
                    ],
                    'occurrences' => null,
                    'start' => '2017-03-28',
                    'end' => '2018-05-01',
                    'startTime' => '2016-04-25',
                    'endTime' => '2020-06-10',
                ],
                'expected' => [
                    '2017-04-09',
                    '2018-04-14',
                ],
            ],
            'startTime = start < end < endTime' => [
                'params' => [
                    'instance' => Recurrence::INSTANCE_FIRST,
                    'interval' => 12, // a number of months, which is a multiple of 12
                    'monthOfYear' => 7,
                    'daysOfWeek' => [
                        'friday',
                    ],
                    'occurrences' => 10,
                    'start' => '2016-05-29 07:00:00',
                    'end' => '2016-07-03 07:00:00',
                    'startTime' => '2016-07-01 07:00:00',
                    'endTime' => '2025-07-04 07:00:00',
                ],
                'expected' => [
                    '2016-07-01 07:00:00',
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
                    'instance' => 3,
                    'dayOfWeek' => ['saturday'],
                    'monthOfYear' => 6,
                    'startTime' => '2016-04-28',
                    'endTime' => null,
                    'occurrences' => null,
                    'timeZone' => 'UTC'
                ],
                'expected' => 'oro.calendar.recurrence.patterns.yearnth0oro.calendar.recurrence.days'
                    . '.saturdayoro.calendar.recurrence.instances.third'
            ],
            'with_occurrences' => [
                'params' => [
                    'interval' => 2,
                    'instance' => 3,
                    'dayOfWeek' => ['saturday'],
                    'monthOfYear' => 6,
                    'startTime' => '2016-04-28',
                    'endTime' => null,
                    'occurrences' => 3,
                    'timeZone' => 'UTC'
                ],
                'expected' => 'oro.calendar.recurrence.patterns.yearnth0oro.calendar.recurrence.days' .
                    '.saturdayoro.calendar.recurrence.instances.thirdoro.calendar.recurrence.patterns.occurrences3'
            ],
            'with_end_date' => [
                'params' => [
                    'interval' => 2,
                    'instance' => 3,
                    'dayOfWeek' => ['saturday'],
                    'monthOfYear' => 6,
                    'startTime' => '2016-04-28',
                    'endTime' => '2016-06-10',
                    'occurrences' => null,
                    'timeZone' => 'UTC'
                ],
                'expected' => 'oro.calendar.recurrence.patterns.yearnth0oro.calendar.recurrence.days'
                    . '.saturdayoro.calendar.recurrence.instances.thirdoro.calendar.recurrence.patterns.end_date'
            ],
            'with_timezone' => [
                'params' => [
                    'interval' => 2,
                    'instance' => 3,
                    'dayOfWeek' => ['saturday'],
                    'monthOfYear' => 6,
                    'startTime' => '2016-04-28T04:00:00+00:00',
                    'endTime' => null,
                    'occurrences' => null,
                    'timeZone' => 'America/Los_Angeles'
                ],
                'expected' => 'oro.calendar.recurrence.patterns.yearnth0oro.calendar.recurrence.days'
                    . '.saturdayoro.calendar.recurrence.instances.thirdoro.calendar.recurrence.patterns.timezone'
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
                    'interval' => 12,
                    'instance' => 3,
                    'dayOfWeek' => ['saturday'],
                    'monthOfYear' => 6,
                    'startTime' => '2016-04-28',
                    'endTime' => null,
                    'occurrences' => null,
                ],
                'expected' => new \DateTime(Recurrence::MAX_END_DATE, $this->getTimeZone())
            ],
            'with_end_date' => [
                'params' => [
                    'interval' => 12,
                    'instance' => 3,
                    'dayOfWeek' => ['saturday'],
                    'monthOfYear' => 6,
                    'startTime' => '2016-04-28',
                    'endTime' => '2016-05-12',
                    'occurrences' => null,
                ],
                'expected' => new \DateTime('2016-05-12', $this->getTimeZone())
            ],
            'with_occurrences' => [
                'params' => [
                    'interval' => 24,
                    'instance' => 2,
                    'dayOfWeek' => ['monday'],
                    'monthOfYear' => 6,
                    'startTime' => '2016-04-28',
                    'endTime' => null,
                    'occurrences' => 3,
                ],
                'expected' => new \DateTime('2020-06-08', $this->getTimeZone())
            ],
            'with_occurrences_1' => [
                'params' => [
                    'interval' => 24,
                    'instance' => 3,
                    'dayOfWeek' => ['sunday'],
                    'monthOfYear' => 3,
                    'startTime' => '2016-04-28',
                    'endTime' => null,
                    'occurrences' => 3,
                ],
                'expected' => new \DateTime('2022-03-20', $this->getTimeZone())
            ],
            'with_occurrences_2' => [
                'params' => [
                    'interval' => 24,
                    'instance' => 3,
                    'dayOfWeek' => ['saturday', 'sunday'],
                    'monthOfYear' => 6,
                    'startTime' => '2016-04-28',
                    'endTime' => null,
                    'occurrences' => 3,
                ],
                'expected' => new \DateTime('2020-06-13', $this->getTimeZone())
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getType()
    {
        return Recurrence::TYPE_YEAR_N_TH;
    }
}
