<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Model\Recurrence;

use Oro\Bundle\CalendarBundle\Entity\Recurrence;
use Oro\Bundle\CalendarBundle\Model\Recurrence\DailyStrategy;

class DailyStrategyTest extends \PHPUnit_Framework_TestCase
{
    /** @var DailyStrategy  */
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
     * @param array $params
     * @param array $expected
     *
     * @dataProvider propertiesDataProvider
     */
    public function testGetOccurrences(array $params, array $expected)
    {
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
            'start < end < startTime < endTime' => [
                'params' => [
                    'start' => '2016-03-28',
                    'end' => '2016-04-17',
                    'interval' => 5,
                    'startTime' => '2016-04-25',
                    'endTime' => '2016-06-10',
                    'occurrences' => null,
                ],
                'expected' => [
                ],
            ],
            'start < startTime < end < endTime' => [
                'params' => [
                    'start' => '2016-03-28',
                    'end' => '2016-05-01',
                    'interval' => 5,
                    'startTime' => '2016-04-25',
                    'endTime' => '2016-06-10',
                    'occurrences' => null,
                ],
                'expected' => [
                    '2016-04-25',
                    '2016-04-30',
                ],
            ],
            'start < startTime < endTime < end' => [
                'params' => [
                    'start' => '2016-05-30',
                    'end' => '2016-07-03',
                    'interval' => 5,
                    'startTime' => '2016-04-25',
                    'endTime' => '2016-06-10',
                    'occurrences' => null,
                ],
                'expected' => [
                    '2016-05-30',
                    '2016-06-04',
                    '2016-06-09',
                ],
            ],
            'startTime < endTime < start < end' => [
                'params' => [
                    'start' => '2016-06-11',
                    'end' => '2016-07-03',
                    'interval' => 5,
                    'startTime' => '2016-04-25',
                    'endTime' => '2016-06-10',
                    'occurrences' => null,
                ],
                'expected' => [
                ],
            ],
            'start < startTime < endTime < end after x occurrences' => [
                'params' => [
                    'start' => '2016-05-30',
                    'end' => '2016-07-03',
                    'interval' => 5,
                    'startTime' => '2016-04-25',
                    'endTime' => '2016-06-10',
                    'occurrences' => 8,
                ],
                'expected' => [
                    '2016-05-30',
                ],
            ],
        ];
    }
}
