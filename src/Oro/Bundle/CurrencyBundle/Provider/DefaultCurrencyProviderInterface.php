<?php

namespace Oro\Bundle\CurrencyBundle\Provider;

/**
 * Defines the contract for providers that supply the system's default currency.
 *
 * Implement this interface to create providers that return the default currency
 * configured in the system. This is used throughout the application when a currency
 * needs to be determined but no specific currency has been selected or specified.
 */
interface DefaultCurrencyProviderInterface
{
    /**
     * Returned default currency from system config
     *
     * @return string
     */
    public function getDefaultCurrency();
}
