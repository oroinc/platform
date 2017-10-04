<?php

namespace Oro\Bundle\SearchBundle\Formatter;

class DateTimeFormatter
{
    const DATETIME_FORMAT = 'Y-m-d H:i:s';

    public function format(\DateTime $dateTimeValue)
    {
        $dateTimeValueToFormat = clone $dateTimeValue;
        $dateTimeValueToFormat->setTimezone(new \DateTimeZone('UTC'));
        $dateTimeString = $dateTimeValueToFormat->format(self::DATETIME_FORMAT);

        unset($dateTimeValueToFormat);

        return $dateTimeString;
    }
}
