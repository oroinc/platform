<?php

namespace Oro\Bundle\SearchBundle\Formatter;

class DateTimeFormatter
{
    const DATETIME_FORMAT = 'Y-m-d H:i:s';

    public function format(\DateTime $dateTimeValue)
    {
        $dateTimeValue->setTimezone(new \DateTimeZone('UTC'));
        $dateTimeString = $dateTimeValue->format(self::DATETIME_FORMAT);
        return $dateTimeString;
    }
}
