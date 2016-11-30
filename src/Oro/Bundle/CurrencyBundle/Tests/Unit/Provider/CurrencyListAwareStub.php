<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\Provider;

use Oro\Bundle\CurrencyBundle\Provider\CurrencyListAwareInterface;

class CurrencyListAwareStub implements CurrencyListAwareInterface
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
     * @param array $currencyList
     */
    public function setCurrencyList($currencyList)
    {
        $this->currencyList = $currencyList;
    }
}
