<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Model\Recurrence;

use Oro\Bundle\CalendarBundle\Entity;
use Oro\Bundle\CalendarBundle\Model\Recurrence;

abstract class AbstractTestStrategy extends \PHPUnit_Framework_TestCase
{
    /** @var \Oro\Bundle\CalendarBundle\Model\Recurrence\StrategyInterface */
    protected $strategy;

    /** @var \DateTimeZone */
    protected $timeZone;

    /**
     * @return array
     */
    abstract public function propertiesDataProvider();

    /**
     * @return string
     */
    abstract protected function getType();

    /**
     * @param array $params
     * @param array $expected
     *
     * @dataProvider propertiesDataProvider
     */
    public function testGetOccurrences(array $params, array $expected)
    {
        $timeZone = $this->getTimeZone();
        $expected = array_map(
            function ($date) use ($timeZone) {
                return new \DateTime($date, $timeZone);
            },
            $expected
        );
        $recurrence = new Entity\Recurrence();
        $recurrence->setRecurrenceType($this->getType());
        if (array_key_exists('startTime', $params)) {
            $recurrence->setStartTime(new \DateTime($params['startTime'], $timeZone));
        }
        if (array_key_exists('endTime', $params)) {
            $recurrence->setEndTime(new \DateTime($params['endTime'], $timeZone))
                ->setCalculatedEndTime(new \DateTime($params['endTime'], $timeZone));
        }
        if (array_key_exists('interval', $params)) {
            $recurrence->setInterval($params['interval']);
        }
        if (array_key_exists('instance', $params)) {
            $recurrence->setInstance($params['instance']);
        }
        if (array_key_exists('occurrences', $params)) {
            $recurrence->setOccurrences($params['occurrences']);
        }
        if (array_key_exists('daysOfWeek', $params)) {
            $recurrence->setDayOfWeek($params['daysOfWeek']);
        }
        if (array_key_exists('dayOfMonth', $params)) {
            $recurrence->setDayOfMonth($params['dayOfMonth']);
        }
        if (array_key_exists('monthOfYear', $params)) {
            $recurrence->setMonthOfYear($params['monthOfYear']);
        }
        $result = $this->strategy->getOccurrences(
            $recurrence,
            new \DateTime($params['start'], $timeZone),
            new \DateTime($params['end'], $timeZone)
        );
        $this->assertEquals($expected, $result);
    }

    /**
     * @return \DateTimeZone
     */
    protected function getTimeZone()
    {
        if ($this->timeZone === null) {
            $this->timeZone = new \DateTimeZone('UTC');
        }

        return $this->timeZone;
    }
}
