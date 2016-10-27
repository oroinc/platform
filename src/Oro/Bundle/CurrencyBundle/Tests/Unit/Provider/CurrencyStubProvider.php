<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\Provider;

use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;

class CurrencyStubProvider implements CurrencyProviderInterface
{
    private $currencyList = ['USD', 'EUR'];

    public function getCurrencyList()
    {
        return $this->currencyList;
    }

    /**
     * @param array $currencyList
     */
    public function setCurrencyList($currencyList)
    {
        $this->currencyList = $currencyList;
    }
}
