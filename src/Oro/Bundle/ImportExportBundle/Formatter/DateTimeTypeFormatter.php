<?php

namespace Oro\Bundle\ImportExportBundle\Formatter;

use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter;

/**
 * Formats date and time values for export in various formats.
 *
 * This formatter handles the conversion of DateTime objects to formatted strings
 * for export, supporting three types: datetime (full date and time), date (date only),
 * and time (time only). It uses the locale-aware DateTimeFormatter from the LocaleBundle
 * to ensure proper formatting according to user locale settings.
 */
class DateTimeTypeFormatter extends DateTimeFormatter implements TypeFormatterInterface
{
    const TYPE_DATETIME = 'datetime';
    const TYPE_DATE     = 'date';
    const TYPE_TIME     = 'time';

    #[\Override]
    public function formatType($value, $type)
    {
        switch ($type) {
            case self::TYPE_DATETIME:
                return $this->format($value);
            case self::TYPE_DATE:
                // Date data does not contain time and timezone information.
                return $this->formatDate($value, null, null, 'UTC');
            case self::TYPE_TIME:
                // Time data does not contain date and timezone information.
                return $this->formatTime($value, null, null, 'UTC');
            default:
                throw new InvalidArgumentException(sprintf('Couldn\'t format "%s" type', $type));
        }
    }
}
