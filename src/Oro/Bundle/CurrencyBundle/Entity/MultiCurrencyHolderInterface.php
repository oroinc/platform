<?php

namespace Oro\Bundle\CurrencyBundle\Entity;

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
