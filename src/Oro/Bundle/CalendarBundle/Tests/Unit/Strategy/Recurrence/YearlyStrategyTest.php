<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Model\Recurrence;

use Oro\Bundle\CalendarBundle\Entity\Recurrence;
use Oro\Bundle\CalendarBundle\Strategy\Recurrence\Helper\StrategyHelper;
use Oro\Bundle\CalendarBundle\Strategy\Recurrence\YearlyStrategy;

class YearlyStrategyTest extends \PHPUnit_Framework_TestCase
{
    /** @var YearlyStrategy  */
    protected $strategy;

    protected function setUp()
    {
        $helper = new StrategyHelper();
        $this->strategy = new YearlyStrategy($helper);
    }

    public function testGetName()
    {
        $this->assertEquals($this->strategy->getName(), 'recurrence_yearly');
    }

    public function testSupports()
    {
        $recurrence = new Recurrence();
        $recurrence->setRecurrenceType(Recurrence::TYPE_YEARLY);
        $this->assertTrue($this->strategy->supports($recurrence));

        $recurrence->setRecurrenceType('Test');
        $this->assertFalse($this->strategy->supports($recurrence));
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
        $recurrence->setRecurrenceType(Recurrence::TYPE_DAILY)
            ->setInterval($params['interval'])
            ->setDayOfMonth($params['dayOfMonth'])
            ->setMonthOfYear($params['monthOfYear'])
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
                    'interval' => 12, // number of months, which is a multiple of 12
                    'dayOfMonth' => 25,
                    'monthOfYear' => 4,
                    'occurrences' => null,
                    'start' => '2015-03-01',
                    'end' => '2015-05-01',
                    'startTime' => '2016-04-25',
                    'endTime' => '2016-06-30',
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
                    'interval' => 12, // number of months, which is a multiple of 12
                    'dayOfMonth' => 25,
                    'monthOfYear' => 4,
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
                    'interval' => 12, // number of months, which is a multiple of 12
                    'dayOfMonth' => 25,
                    'monthOfYear' => 4,
                    'occurrences' => null,
                    'start' => '2015-03-01',
                    'end' => '2017-05-01',
                    'startTime' => '2016-04-25',
                    'endTime' => '2016-06-30',
                ],
                'expected' => [
                    '2016-04-25',
                ],
            ],
            /**
             *     |-----|
             * |-----|
             */
            'startTime < start < endTime < end' => [
                'params' => [
                    'interval' => 12, // number of months, which is a multiple of 12
                    'dayOfMonth' => 25,
                    'monthOfYear' => 4,
                    'occurrences' => null,
                    'start' => '2018-01-01',
                    'end' => '2019-05-01',
                    'startTime' => '2016-04-25',
                    'endTime' => '2018-06-30',
                ],
                'expected' => [
                    '2018-04-25',
                ],
            ],
            /**
             *         |-----|
             * |-----|
             */
            'startTime < endTime < start < end' => [
                'params' => [
                    'interval' => 12, // number of months, which is a multiple of 12
                    'dayOfMonth' => 25,
                    'monthOfYear' => 4,
                    'occurrences' => null,
                    'start' => '2021-03-28',
                    'end' => '2021-05-01',
                    'startTime' => '2016-04-25',
                    'endTime' => '2016-06-30',
                ],
                'expected' => [
                ],
            ],

            'startTime < start < end < endTime with X occurrences' => [
                'params' => [
                    'interval' => 12, // number of months, which is a multiple of 12
                    'dayOfMonth' => 25,
                    'monthOfYear' => 4,
                    'occurrences' => 2,
                    'start' => '2017-03-28',
                    'end' => '2017-05-01',
                    'startTime' => '2016-04-25',
                    'endTime' => '2026-12-31',
                ],
                'expected' => [
                    '2017-04-25',
                ],
            ],
            'startTime < start < endTime < end without matching' => [
                'params' => [
                    'interval' => 12, // number of months, which is a multiple of 12
                    'dayOfMonth' => 25,
                    'monthOfYear' => 4,
                    'occurrences' => null,
                    'start' => '2016-05-30',
                    'end' => '2016-07-03',
                    'startTime' => '2016-04-25',
                    'endTime' => '2016-06-30',
                ],
                'expected' => [
                ],
            ],
        ];
    }
}
