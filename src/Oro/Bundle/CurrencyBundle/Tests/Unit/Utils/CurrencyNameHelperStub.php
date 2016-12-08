<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\Utils;

use Symfony\Component\Intl\Intl;

use Oro\Bundle\CurrencyBundle\Provider\ViewTypeProviderInterface;
use Oro\Bundle\CurrencyBundle\Utils\CurrencyNameHelper;

class CurrencyNameHelperStub extends CurrencyNameHelper
{
    public function __construct()
    {
        $this->intlCurrencyBundle = Intl::getCurrencyBundle();
    }

    public function getCurrencyName($currencyIsoCode, $nameViewStyle = null)
    {
        if (ViewTypeProviderInterface::VIEW_TYPE_ISO_CODE === $nameViewStyle) {
            return $currencyIsoCode;
        }
        return $this->intlCurrencyBundle->getCurrencyName($currencyIsoCode);
    }

    public function getCurrencyChoices($nameViewStyle = null)
    {
        return ['USD', 'EUR'];
    }
}
