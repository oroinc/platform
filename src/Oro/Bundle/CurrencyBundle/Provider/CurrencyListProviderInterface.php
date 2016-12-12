<?php

namespace Oro\Bundle\CurrencyBundle\Provider;

interface CurrencyListProviderInterface
{
    /**
     * Returns list of currencies which available in system
     *
     * @return string[] list of currencies ISO codes
     */
    public function getCurrencyList();
}
