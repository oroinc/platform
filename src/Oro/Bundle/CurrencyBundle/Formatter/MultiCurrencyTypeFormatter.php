<?php

namespace Oro\Bundle\CurrencyBundle\Formatter;

use Oro\Bundle\ImportExportBundle\Formatter\TypeFormatterInterface;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;

/**
 * Formats value by provided type.
 */
class MultiCurrencyTypeFormatter implements TypeFormatterInterface
{
    protected $formatter;

    /**
     * MultiCurrencyTypeFormatter constructor.
     *
     * @param NumberFormatter $formatter
     */
    public function __construct(NumberFormatter $formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * {@inheritdoc}
     */
    public function formatType($value, $type)
    {
        if ('' === $value) {
            return $value;
        }

        $currency = substr($value, 0, 3);
        $columnValue = substr($value, 3);

        return $this->formatter->formatCurrency($columnValue, $currency);
    }
}
