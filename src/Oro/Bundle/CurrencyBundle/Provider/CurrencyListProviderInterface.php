<?php

namespace Oro\Bundle\CurrencyBundle\Provider;

/**
 * Defines the contract for providers that supply lists of available currencies.
 *
 * Implement this interface to create providers that return the currencies available
 * in the system. This is typically used to populate currency selection dropdowns,
 * validate currency codes, or filter data by available currencies.
 */
interface CurrencyListProviderInterface
{
    /**
     * Returns list of currencies which available in system
     *
     * @return string[] list of currencies ISO codes
     */
    public function getCurrencyList();
}
