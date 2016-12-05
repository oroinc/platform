<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\Utils;

use Symfony\Component\Intl\Intl;

use Oro\Bundle\CurrencyBundle\Utils\CurrencyNameHelper;

class CurrencyNameHelperStub extends CurrencyNameHelper
{
    public function __construct()
    {
        $this->intlCurrencyBundle = Intl::getCurrencyBundle();
    }

    /**
     * @param string $currencyIsoCode
     * @param string|null $nameViewStyle
     * @return string
     */
    public function getCurrencyName($currencyIsoCode, $nameViewStyle = null)
    {
        return sprintf('%s-%s', $currencyIsoCode, $nameViewStyle);
    }

    /**
     * @param string|null $nameViewStyle
     * @return array
     */
    public function getCurrencyChoices($nameViewStyle = null)
    {
        return ['USD', 'EUR'];
    }
}
