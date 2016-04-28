<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Model\Recurrence;

use Oro\Bundle\CalendarBundle\Entity\Recurrence;
use Oro\Bundle\CalendarBundle\Strategy\Recurrence\DailyStrategy;

class DailyStrategyTest extends \PHPUnit_Framework_TestCase
{
    /** @var DailyStrategy */
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
        $recurrence->setRecurrenceType(Recurrence::TYPE_DAILY)
            ->setInterval($params['interval'])
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
                    'interval' => 5,
                    'occurrences' => null,
                    'start' => '2016-03-28',
                    'end' => '2016-04-17',
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
                    'interval' => 5,
                    'occurrences' => null,
                    'start' => '2016-03-28',
                    'end' => '2016-05-01',
                    'startTime' => '2016-04-25',
                    'endTime' => '2016-06-10',
                ],
                'expected' => [
                    '2016-04-25',
                    '2016-04-30',
                ],
            ],
            /**
             * |-----|
             *   |-|
             */
            'start < startTime < endTime < end' => [
                'params' => [
                    'interval' => 5,
                    'occurrences' => null,
                    'start' => '2016-05-30',
                    'end' => '2016-07-03',
                    'startTime' => '2016-04-25',
                    'endTime' => '2016-06-10',
                ],
                'expected' => [
                    '2016-05-30',
                    '2016-06-04',
                    '2016-06-09',
                ],
            ],
            /**
             *     |-----|
             * |-----|
             */
            'startTime < start < endTime < end' => [
                'params' => [
                    'interval' => 5,
                    'occurrences' => null,
                    'start' => '2016-04-30',
                    'end' => '2016-05-30',
                    'startTime' => '2016-04-25',
                    'endTime' => '2016-05-10',
                ],
                'expected' => [
                    '2016-04-30',
                    '2016-05-05',
                    '2016-05-10',
                ],
            ],
            /**
             *         |-----|
             * |-----|
             */
            'startTime < endTime < start < end' => [
                'params' => [
                    'interval' => 5,
                    'occurrences' => null,
                    'start' => '2016-06-11',
                    'end' => '2016-07-03',
                    'startTime' => '2016-04-25',
                    'endTime' => '2016-06-10',
                ],
                'expected' => [
                ],
            ],

            'start = end = startTime = endTime' => [
                'params' => [
                    'interval' => 5,
                    'occurrences' => null,
                    'start' => '2016-04-24',
                    'end' => '2016-04-24',
                    'startTime' => '2016-04-24',
                    'endTime' => '2016-04-24',
                ],
                'expected' => [
                    '2016-04-24',
                ],
            ],
            'start = end = (startTime + 1 day) = (endTime + 1 day)' => [
                'params' => [
                    'interval' => 5,
                    'occurrences' => null,
                    'start' => '2016-04-24',
                    'end' => '2016-04-24',
                    'startTime' => '2016-04-25',
                    'endTime' => '2016-04-25',
                ],
                'expected' => [
                ],
            ],
            'startTime = endTime = (start + interval days) = (end + interval days)' => [
                'params' => [
                    'interval' => 5,
                    'occurrences' => null,
                    'start' => '2016-04-30',
                    'end' => '2016-04-30',
                    'startTime' => '2016-04-25',
                    'endTime' => '2016-04-25',
                ],
                'expected' => [
                ],
            ],
            'start < startTime < endTime < end after x occurrences' => [
                'params' => [
                    'interval' => 5,
                    'occurrences' => 8,
                    'start' => '2016-05-30',
                    'end' => '2016-07-03',
                    'startTime' => '2016-04-25',
                    'endTime' => '2016-06-10',
                ],
                'expected' => [
                    '2016-05-30',
                ],
            ],
            'start < startTime < endTime < end after y occurrences' => [
                'params' => [
                    'interval' => 5,
                    'occurrences' => 7,
                    'start' => '2016-05-30',
                    'end' => '2016-07-03',
                    'startTime' => '2016-04-25',
                    'endTime' => '2016-06-10',
                ],
                'expected' => [
                ],
            ],
            'no endTime' => [
                'params' => [
                    'interval' => 5,
                    'occurrences' => 8,
                    'start' => '2016-05-30',
                    'end' => '2016-07-03',
                    'startTime' => '2016-04-25',
                    'endTime' => '9999-12-31',
                ],
                'expected' => [
                    '2016-05-30',
                ],
            ],
        ];
    }
}
