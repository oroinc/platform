<?php

namespace Oro\Bundle\CurrencyBundle\Entity;

/**
 * Defines the contract for entities that manage multi-currency field synchronization.
 *
 * Implement this interface for entities that store monetary values in multiple currencies
 * and need to synchronize between the multi-currency value objects and the entity's
 * persistent fields. This is typically used with Doctrine lifecycle callbacks to ensure
 * data consistency during entity persistence and hydration.
 */
interface MultiCurrencyHolderInterface
{
    /**
     * Synchronize values from multi-currency fields to entity fields
     *
     * @return void
     */
    public function updateMultiCurrencyFields();

    /**
     * Initialize multi-currency fields on entity load
     *
     * @return void
     */
    public function loadMultiCurrencyFields();
}
