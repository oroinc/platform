<?php

namespace Oro\Bundle\CurrencyBundle\Provider;

interface CurrencyProviderInterface
{
    /**
     * Returns list of unique currency codes
     *
     * @return string[] list of currencies ISO codes
     */
    public function getCurrencyList();
}
