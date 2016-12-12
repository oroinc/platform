<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\Provider;

use Oro\Bundle\CurrencyBundle\Provider\CurrencyListProviderInterface;

class CurrencyListProviderStub implements CurrencyListProviderInterface
{
    /**
     * @var array
     */
    private $currencyList = ['USD', 'EUR'];

    /**
     * {@inheritdoc}
     */
    public function getCurrencyList()
    {
        return $this->currencyList;
    }

    /**
     * @param $currencyList
     * @return $this
     */
    public function setCurrencyList($currencyList)
    {
        $this->currencyList = $currencyList;

        return $this;
    }
}
