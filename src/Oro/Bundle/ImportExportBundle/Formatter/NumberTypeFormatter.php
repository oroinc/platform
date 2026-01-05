<?php

namespace Oro\Bundle\ImportExportBundle\Formatter;

use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;

class NumberTypeFormatter implements TypeFormatterInterface
{
    public const TYPE_CURRENCY = 'currency';
    public const TYPE_PERCENT  = 'percent';
    public const TYPE_INTEGER  = 'integer';
    public const TYPE_DECIMAL  = 'decimal';

    protected $numberFormatter;

    /**
     * NumberTypeFormatter constructor.
     */
    public function __construct(NumberFormatter $numberFormatter)
    {
        $this->numberFormatter = $numberFormatter;
    }

    #[\Override]
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
