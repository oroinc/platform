<?php

namespace Oro\Bundle\SearchBundle\Formatter;

use Oro\Component\Exception\UnexpectedTypeException;

/**
 * Convert DateTime objects to search index representation
 */
class DateTimeFormatter implements ValueFormatterInterface
{
    const DATETIME_FORMAT = 'Y-m-d H:i:s';

    /**
     * Format \DateTime object into string format by specific format in DateTimeFormatter::DATETIME_FORMAT
     *
     * @param \DateTime $value
     * @return string
     */
    public function format($value) : string
    {
        if (!$value instanceof \DateTime) {
            throw new UnexpectedTypeException($value, '\DateTime');
        }

        $dateTimeValueToFormat = clone $value;
        $dateTimeValueToFormat->setTimezone(new \DateTimeZone('UTC'));
        $dateTimeString = $dateTimeValueToFormat->format(self::DATETIME_FORMAT);

        unset($dateTimeValueToFormat);

        return $dateTimeString;
    }
}
