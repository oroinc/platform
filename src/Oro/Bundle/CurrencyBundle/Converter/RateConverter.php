<?php

namespace Oro\Bundle\CurrencyBundle\Converter;

use Oro\Bundle\CurrencyBundle\Entity\MultiCurrency;

/**
 * The representation of Currency converter for CE
 */
class RateConverter implements RateConverterInterface
{
    #[\Override]
    public function getBaseCurrencyAmount(MultiCurrency $currency): float
    {
        return $currency->getValue();
    }

    #[\Override]
    public function getCrossConversionRate(string $currencyFrom, string $currencyTo): ?float
    {
        return null;
    }
}
