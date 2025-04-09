<?php

namespace Oro\Bundle\CurrencyBundle\Converter;

use Oro\Bundle\CurrencyBundle\Entity\MultiCurrency;

/**
 * The representation of Currency converter
 */
interface RateConverterInterface
{
    /**
     * Returns amount base currency
     * @param MultiCurrency $currency
     *
     * @return float
     */
    public function getBaseCurrencyAmount(MultiCurrency $currency);
}
