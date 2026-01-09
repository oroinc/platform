<?php

namespace Oro\Bundle\CurrencyBundle\Entity;

/**
 * Defines the contract for entities that have an associated currency.
 *
 * Implement this interface for entities that need to store and manage a currency code.
 * This is commonly used for entities that represent monetary values, prices, or any
 * data that is currency-specific.
 */
interface CurrencyAwareInterface
{
    /**
     * @return string
     */
    public function getCurrency();

    /**
     * @param string $currency
     * @return $this
     */
    public function setCurrency($currency);
}
