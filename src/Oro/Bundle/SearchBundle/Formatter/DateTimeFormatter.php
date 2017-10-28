<?php

namespace Oro\Bundle\SearchBundle\Formatter;

class DateTimeFormatter
{
    const DATETIME_FORMAT = 'Y-m-d H:i:s';

    /**
     * Format \DateTime object into string format by specific format in DateTimeFormatter::DATETIME_FORMAT
     *
     * @param \DateTime $dateTimeValue
     *
     * @return string
     */
    public function format(\DateTime $dateTimeValue)
    {
        $dateTimeValueToFormat = clone $dateTimeValue;
        $dateTimeValueToFormat->setTimezone(new \DateTimeZone('UTC'));
        $dateTimeString = $dateTimeValueToFormat->format(self::DATETIME_FORMAT);

        unset($dateTimeValueToFormat);

        return $dateTimeString;
    }
}
