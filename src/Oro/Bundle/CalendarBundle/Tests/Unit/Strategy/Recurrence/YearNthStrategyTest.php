<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Model\Recurrence;

use Oro\Bundle\CalendarBundle\Entity\Recurrence;
use Oro\Bundle\CalendarBundle\Strategy\Recurrence\Helper\StrategyHelper;
use Oro\Bundle\CalendarBundle\Strategy\Recurrence\YearNthStrategy;

class YearNthStrategyTest extends \PHPUnit_Framework_TestCase
{
    /** @var YearNthStrategy  */
    protected $strategy;

    protected function setUp()
    {
        $helper = new StrategyHelper();
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

    /**
     * @expectedException \RuntimeException
     */
    public function testGetOccurrencesWithWrongIntervalValue()
    {
        $recurrence = new Recurrence();
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
        $recurrence->setRecurrenceType(Recurrence::TYPE_YEAR_N_TH)
            ->setInstance($params['instance'])
            ->setInterval($params['interval'])
            ->setMonthOfYear($params['monthOfYear'])
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
        ];
    }
}
