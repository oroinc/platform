<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\Utils;

use Oro\Bundle\CurrencyBundle\Provider\ViewTypeProviderInterface;
use Oro\Bundle\CurrencyBundle\Utils\CurrencyNameHelper;
use Symfony\Component\Intl\Intl;

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

        if (ViewTypeProviderInterface::VIEW_TYPE_FULL_NAME === $nameViewStyle) {
            return $this->intlCurrencyBundle->getCurrencyName($currencyIsoCode);
        }

        return $this->intlCurrencyBundle->getCurrencySymbol($currencyIsoCode);
    }

    public function getCurrencyChoices($nameViewStyle = null)
    {
        return ['$' => 'USD', '€' => 'EUR'];
    }
}
