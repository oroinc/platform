<?php

namespace Oro\Bundle\CurrencyBundle\Converter;

use Oro\Bundle\CurrencyBundle\Entity\MultiCurrency;

/**
 * The representation of Currency converter
 */
interface RateConverterInterface
{
    /**
     * Returns amount in the base currency
     */
    public function getBaseCurrencyAmount(MultiCurrency $currency): float;

    /**
     * Returns the conversion rate to convert $currencyFrom to $currencyTo
     */
    public function getCrossConversionRate(string $currencyFrom, string $currencyTo): ?float;
}
