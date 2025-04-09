<?php

namespace Oro\Bundle\CurrencyBundle\Converter;

/**
 * The representation of Currency converter for cross rate converters.
 */
interface CrossRateConverterInterface
{
    /**
     * Returns the conversion rate to convert $currencyFrom to $currencyTo
     */
    public function getCrossConversionRate(string $currencyFrom, string $currencyTo): ?float;
}
