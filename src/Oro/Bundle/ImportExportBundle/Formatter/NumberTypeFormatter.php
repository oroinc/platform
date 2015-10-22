<?php

namespace Oro\Bundle\ImportExportBundle\Formatter;

use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;

class NumberTypeFormatter extends NumberFormatter implements TypeFormatterInterface
{
    const TYPE_CURRENCY = 'currency';
    const TYPE_PERCENT  = 'percent';
    const TYPE_INTEGER  = 'integer';
    const TYPE_DECIMAL  = 'decimal';

    /**
     * {@inheritdoc}
     */
    public function formatType($value, $type)
    {
        switch ($type) {
            case self::TYPE_CURRENCY:
                return $this->formatCurrency($value);
                break;
            case self::TYPE_PERCENT:
                return $this->formatPercent($value);
                break;
            case self::TYPE_DECIMAL:
                return $this->formatDecimal($value);
                break;
            case self::TYPE_INTEGER:
                return $this->formatDecimal($value);
                break;
            default:
                throw new InvalidArgumentException(sprintf('Couldn\'t format "%s" type', $type));
        }
    }
}
