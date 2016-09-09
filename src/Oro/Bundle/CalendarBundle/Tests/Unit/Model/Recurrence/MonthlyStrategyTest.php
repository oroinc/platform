<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Model\Recurrence;

use Oro\Bundle\CalendarBundle\Entity;
use Oro\Bundle\CalendarBundle\Model\Recurrence;
use Oro\Bundle\CalendarBundle\Model\Recurrence\MonthlyStrategy;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;

class MonthlyStrategyTest extends AbstractTestStrategy
{
    /** @var MonthlyStrategy  */
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

        $this->strategy = new MonthlyStrategy($translator, $dateTimeFormatter, $localeSettings);
    }

    public function testGetName()
    {
        $this->assertEquals($this->strategy->getName(), 'recurrence_monthly');
    }

    public function testSupports()
    {
        $recurrence = new Entity\Recurrence();
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
    public function testGetTextValue($recurrenceData, $expected)
    {
        $recurrence = new Entity\Recurrence();
        $recurrence->setRecurrenceType(Recurrence::TYPE_MONTHLY)
            ->setInterval($recurrenceData['interval'])
            ->setDayOfMonth($recurrenceData['dayOfMonth'])
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
        $recurrence->setRecurrenceType(Recurrence::TYPE_MONTHLY)
            ->setInterval($recurrenceData['interval'])
            ->setDayOfMonth($recurrenceData['dayOfMonth'])
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
                    'interval' => 2,
                    'dayOfMonth' => 25,
                    'occurrences' => null,
                    'start' => '2016-03-28',
                    'end' => '2016-05-01',
                    'startTime' => '2016-05-25',
                    'endTime' => '2016-07-30',
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
                    'interval' => 2,
                    'dayOfMonth' => 25,
                    'occurrences' => null,
                    'start' => '2016-03-28',
                    'end' => '2016-05-01',
                    'startTime' => '2016-04-25',
                    'endTime' => '2016-06-30',
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
                    'interval' => 2,
                    'dayOfMonth' => 25,
                    'occurrences' => null,
                    'start' => '2016-03-01',
                    'end' => '2016-10-01',
                    'startTime' => '2016-04-25',
                    'endTime' => '2016-08-01',
                ],
                'expected' => [
                    '2016-04-25',
                    '2016-06-25',
                ],
            ],
            /**
             *     |-----|
             * |-----|
             */
            'startTime < start < endTime < end' => [
                'params' => [
                    'interval' => 2,
                    'dayOfMonth' => 25,
                    'occurrences' => null,
                    'start' => '2016-05-30',
                    'end' => '2016-07-03',
                    'startTime' => '2016-04-25',
                    'endTime' => '2016-06-30',
                ],
                'expected' => [
                    '2016-06-25',
                ],
            ],
            /**
             *         |-----|
             * |-----|
             */
            'startTime < endTime < start < end' => [
                'params' => [
                    'interval' => 5,
                    'dayOfMonth' => 25,
                    'occurrences' => null,
                    'start' => '2016-07-25',
                    'end' => '2016-09-04',
                    'startTime' => '2016-04-25',
                    'endTime' => '2016-06-30',
                ],
                'expected' => [
                ],
            ],

            'startTime < start < end < endTime with X occurrences' => [
                'params' => [
                    'interval' => 2,
                    'dayOfMonth' => 25,
                    'occurrences' => 3,
                    'start' => '2016-07-25',
                    'end' => '2016-09-04',
                    'startTime' => '2016-04-25',
                    'endTime' => '2016-12-31',
                ],
                'expected' => [
                    '2016-08-25',
                ],
            ],
            'startTime < start < end < endTime with no matches' => [
                'params' => [
                    'interval' => 2,
                    'dayOfMonth' => 25,
                    'occurrences' => 3,
                    'start' => '2016-09-06',
                    'end' => '2016-11-06',
                    'startTime' => '2016-04-25',
                    'endTime' => '2016-12-31',
                ],
                'expected' => [
                ],
            ],
            /**
             * |-----|
             *   |-|
             */
            'start < startTime < endTime < end with 31 day' => [
                'params' => [
                    'interval' => 1,
                    'dayOfMonth' => 31,
                    'occurrences' => null,
                    'start' => '2016-01-01',
                    'end' => '2016-10-01',
                    'startTime' => '2016-01-25',
                    'endTime' => '2016-08-01',
                ],
                'expected' => [
                    '2016-01-31',
                    '2016-02-29',
                    '2016-03-31',
                    '2016-04-30',
                    '2016-05-31',
                    '2016-06-30',
                    '2016-07-31',
                ],
            ],
            /**
             * |-----|
             *   |-|
             */
            'start < startTime < endTime < end with 30 day and february' => [
                'params' => [
                    'interval' => 1,
                    'dayOfMonth' => 30,
                    'occurrences' => null,
                    'start' => '2015-01-01',
                    'end' => '2015-05-01',
                    'startTime' => '2015-01-20',
                    'endTime' => '2015-04-01',
                ],
                'expected' => [
                    '2015-01-30',
                    '2015-02-28',
                    '2015-03-30',
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
                    'dayOfMonth' => 10,
                    'startTime' => '2016-04-28',
                    'endTime' => null,
                    'occurrences' => null,
                    'timeZone' => 'UTC',
                ],
                'expected' => 'oro.calendar.recurrence.patterns.monthly210'
            ],
            'with_occurrences' => [
                'params' => [
                    'interval' => 2,
                    'dayOfMonth' => 10,
                    'startTime' => '2016-04-28',
                    'endTime' => null,
                    'occurrences' => 3,
                    'timeZone' => 'UTC',
                ],
                'expected' => 'oro.calendar.recurrence.patterns.monthly210oro.calendar.recurrence.patterns.occurrences3'
            ],
            'with_end_date' => [
                'params' => [
                    'interval' => 2,
                    'dayOfMonth' => 10,
                    'startTime' => '2016-04-28',
                    'endTime' => '2016-06-10',
                    'occurrences' => null,
                    'timeZone' => 'UTC',
                ],
                'expected' => 'oro.calendar.recurrence.patterns.monthly210oro.calendar.recurrence.patterns.end_date'
            ],
            'with_timezone' => [
                'params' => [
                    'interval' => 2,
                    'dayOfMonth' => 10,
                    'startTime' => '2016-04-28T04:00:00+00:00',
                    'endTime' => null,
                    'occurrences' => null,
                    'timeZone' => 'America/Los_Angeles',
                ],
                'expected' => 'oro.calendar.recurrence.patterns.monthly210oro.calendar.recurrence.patterns.timezone'
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
                    'dayOfMonth' => 10,
                    'startTime' => '2016-04-28',
                    'endTime' => null,
                    'occurrences' => null,
                ],
                'expected' => new \DateTime(Recurrence::MAX_END_DATE, $this->getTimeZone())
            ],
            'with_end_date' => [
                'params' => [
                    'interval' => 2,
                    'dayOfMonth' => 10,
                    'startTime' => '2016-04-28',
                    'endTime' => '2016-05-12',
                    'occurrences' => null,
                ],
                'expected' => new \DateTime('2016-05-12', $this->getTimeZone())
            ],
            'with_occurrences' => [
                'params' => [
                    'interval' => 2,
                    'dayOfMonth' => 18,
                    'startTime' => '2016-04-14T00:00:00+00:00',
                    'endTime' => null,
                    'occurrences' => 5,
                ],
                'expected' => new \DateTime('2016-12-18T00:00:00+00:00', $this->getTimeZone())
            ],
            'with_occurrences_1' => [
                'params' => [
                    'interval' => 2,
                    'dayOfMonth' => 10,
                    'startTime' => '2016-04-14T00:00:00+00:00',
                    'endTime' => null,
                    'occurrences' => 3,
                ],
                'expected' => new \DateTime('2016-10-10T00:00:00+00:00', $this->getTimeZone())
            ],
            'with_occurrences_2' => [
                'params' => [
                    'interval' => 2,
                    'dayOfMonth' => 14,
                    'startTime' => '2016-04-14T00:00:00+00:00',
                    'endTime' => null,
                    'occurrences' => 3,
                ],
                'expected' => new \DateTime('2016-08-14T00:00:00+00:00', $this->getTimeZone())
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getType()
    {
        return Recurrence::TYPE_MONTHLY;
    }
}
