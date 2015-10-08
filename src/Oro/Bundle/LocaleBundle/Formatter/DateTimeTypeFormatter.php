<?php

namespace Oro\Bundle\LocaleBundle\Formatter;

use Oro\Bundle\ImportExportBundle\Formatter\TypeFormatterInterface;
use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;

class DateTimeTypeFormatter extends DateTimeFormatter implements TypeFormatterInterface
{
    const FORMAT_TYPE_DATETIME = 'format_type_datetime';
    const FORMAT_TYPE_DATE     = 'format_type_date';
    const FORMAT_TYPE_TIME     = 'format_type_time';

    /**
     * {@inheritdoc}
     */
    public function formatType($value, $type)
    {
        switch ($type) {
            case self::FORMAT_TYPE_DATETIME:
                return $this->format($value);
                break;
            case self::FORMAT_TYPE_DATE:
                return $this->formatDate($value);
                break;
            case self::FORMAT_TYPE_TIME:
                return $this->formatTime($value);
                break;
            default:
                throw new InvalidArgumentException(sprintf('Couldn\'t format %s type', $type));
        }
    }
}
