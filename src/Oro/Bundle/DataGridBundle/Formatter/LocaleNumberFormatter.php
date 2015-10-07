<?php

namespace Oro\Bundle\DataGridBundle\Formatter;

use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;
use Oro\Bundle\ImportExportBundle\Converter\TypeFormatterInterface;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;

class LocaleNumberFormatter extends NumberFormatter implements TypeFormatterInterface
{
    const FORMAT_TYPE_CURRENCY = 'format_type_currency';
    const FORMAT_TYPE_PERCENT  = 'format_type_percent';
    const FORMAT_TYPE_INTEGER  = 'format_type_integer';
    const FORMAT_TYPE_DECIMAL  = 'format_type_decimal';

    /**
     * {@inheritdoc}
     */
    public function formatType($value, $type)
    {
        switch ($type) {
            case self::FORMAT_TYPE_CURRENCY:
                return $this->formatCurrency($value);
                break;
            case self::FORMAT_TYPE_PERCENT:
                return $this->formatPercent($value);
                break;
            case self::FORMAT_TYPE_DECIMAL:
                return $this->formatDecimal($value);
                break;
            case self::FORMAT_TYPE_INTEGER:
                return $this->formatDecimal($value);
                break;
            default:
                throw new InvalidArgumentException(sprintf('Couldn\'t format %s type', $type));
        }
    }
}
