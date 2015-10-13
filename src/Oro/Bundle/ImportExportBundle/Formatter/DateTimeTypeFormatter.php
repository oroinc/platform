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
                break;
            case self::TYPE_DATE:
                return $this->formatDate($value);
                break;
            case self::TYPE_TIME:
                return $this->formatTime($value);
                break;
            default:
                throw new InvalidArgumentException(sprintf('Couldn\'t format "%s" type', $type));
        }
    }
}
