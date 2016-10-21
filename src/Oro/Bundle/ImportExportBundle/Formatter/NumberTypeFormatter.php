<?php

namespace Oro\Bundle\ImportExportBundle\Formatter;

use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;

class NumberTypeFormatter implements TypeFormatterInterface
{
    const TYPE_CURRENCY = 'currency';
    const TYPE_PERCENT  = 'percent';
    const TYPE_INTEGER  = 'integer';
    const TYPE_DECIMAL  = 'decimal';

    protected $numberFormatter;

    /**
     * NumberTypeFormatter constructor.
     *
     * @param NumberFormatter $numberFormatter
     */
    public function __construct(NumberFormatter $numberFormatter)
    {
        $this->numberFormatter = $numberFormatter;
    }

    /**
     * {@inheritdoc}
     */
    public function formatType($value, $type)
    {
        switch ($type) {
            case self::TYPE_CURRENCY:
                return $this->numberFormatter->formatCurrency($value);
            case self::TYPE_PERCENT:
                return $this->numberFormatter->formatPercent($value);
            case self::TYPE_DECIMAL:
                return $this->numberFormatter->formatDecimal($value);
            case self::TYPE_INTEGER:
                return $this->numberFormatter->formatDecimal($value);
            default:
                throw new InvalidArgumentException(sprintf('Couldn\'t format "%s" type', $type));
        }
    }
}
