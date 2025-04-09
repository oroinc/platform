<?php

namespace Oro\Bundle\CurrencyBundle\Converter;

use Oro\Bundle\CurrencyBundle\Entity\MultiCurrency;

/**
 * The representation of Currency converter for CE
 */
class RateConverter implements RateConverterInterface, CrossRateConverterInterface
{
    /**
     * @param MultiCurrency $currency
     *
     * @return float
     */
    public function getBaseCurrencyAmount(MultiCurrency $currency)
    {
        return $currency->getValue();
    }

    /**
     * {@inheritdoc}
     */
    public function getCrossConversionRate(string $currencyFrom, string $currencyTo): ?float
    {
        return null;
    }
}
