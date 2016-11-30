<?php

namespace Oro\Bundle\CurrencyBundle\Provider;

interface DefaultCurrencyAwareInterface
{
    /**
     * Returned default currency from system config
     *
     * @return string
     */
    public function getDefaultCurrency();
}
