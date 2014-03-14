<?php

namespace Oro\Bundle\ReminderBundle\Model;

/**
 * Represents remind interval, a pair of number and unit of interval.
 */
class ReminderInterval
{
    const UNIT_MINUTE = 'M';
    const UNIT_HOUR   = 'H';
    const UNIT_DAY    = 'D';
    const UNIT_WEEK   = 'W';

    /**
     * @var int
     */
    protected $number;

    /**
     * @var string
     */
    protected $unit;

    /**
     * @param int    $number
     * @param string $unit
     */
    public function __construct($number = null, $unit = null)
    {
        if (null !== $number) {
            $this->setNumber($number);
        }
        if (null !== $unit) {
            $this->setUnit($unit);
        }
    }

    /**
     * @return int
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @param int $number
     * @return ReminderInterval
     */
    public function setNumber($number)
    {
        $this->number = (int)$number;

        return $this;
    }

    /**
     * @return string
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * @param string $unit
     * @return ReminderInterval
     */
    public function setUnit($unit)
    {
        $this->unit = $unit;

        if (!in_array($this->unit, array(self::UNIT_MINUTE, self::UNIT_HOUR, self::UNIT_DAY, self::UNIT_WEEK))) {
            $this->unit = self::UNIT_MINUTE;
        }

        return $this;
    }

    /**
     * Creates DateInterval that match reminder interval params
     *
     * @return \DateInterval
     */
    public function createDateInterval()
    {
        $unit = $this->getUnit();

        if ($unit == self::UNIT_MINUTE || $unit == self::UNIT_HOUR) {
            $format = 'PT%d%s';
        } else {
            $format = 'P%d%s';
        }

        try {
            return new \DateInterval(sprintf($format, $this->getNumber(), $this->getUnit()));
        } catch (\Exception $e) {
            return new \DateInterval('P0D');
        }
    }
}
