<?php

namespace Oro\Bundle\CurrencyBundle\Converter;

use Oro\Bundle\CurrencyBundle\Entity\MultiCurrency;

interface RateConverterInterface
{
    /**
    * @param MultiCurrency $currency
    * @return float
    */
    public function getBaseCurencyAmount(MultiCurrency $currency);
}
