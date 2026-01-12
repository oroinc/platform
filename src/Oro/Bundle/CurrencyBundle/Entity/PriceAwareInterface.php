<?php

namespace Oro\Bundle\CurrencyBundle\Entity;

/**
 * Defines the contract for entities that have an associated price.
 *
 * Implement this interface for entities that need to expose a Price value object.
 * This provides a standardized way to access pricing information across different
 * entity types in the application.
 */
interface PriceAwareInterface
{
    /**
     * @return Price
     */
    public function getPrice();
}
