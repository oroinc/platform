<?php

namespace Oro\Bundle\ImportExportBundle\Formatter;

use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter;

class DateTimeTypeFormatter extends DateTimeFormatter implements TypeFormatterInterface
{
    const TYPE_DATETIME = 'datetime';
    const TYPE_DATE     = 'date';
    const TYPE_TIME     = 'time';

    /**
     * {@inheritdoc}
     */
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
