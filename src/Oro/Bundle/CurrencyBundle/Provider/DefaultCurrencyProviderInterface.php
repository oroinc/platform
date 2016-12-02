<?php

namespace Oro\Bundle\CurrencyBundle\Provider;

interface DefaultCurrencyProviderInterface
{
    /**
     * Returned default currency from system config
     *
     * @return string
     */
    public function getDefaultCurrency();
}
