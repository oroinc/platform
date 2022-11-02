<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\Utils;

use Oro\Bundle\CurrencyBundle\Provider\ViewTypeProviderInterface;
use Oro\Bundle\CurrencyBundle\Utils\CurrencyNameHelper;
use Symfony\Component\Intl\Currencies;

class CurrencyNameHelperStub extends CurrencyNameHelper
{
    public function __construct()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrencyName($currencyIsoCode, $nameViewStyle = null)
    {
        if (ViewTypeProviderInterface::VIEW_TYPE_ISO_CODE === $nameViewStyle) {
            return $currencyIsoCode;
        }

        if (ViewTypeProviderInterface::VIEW_TYPE_FULL_NAME === $nameViewStyle) {
            return Currencies::getName($currencyIsoCode);
        }

        return Currencies::getSymbol($currencyIsoCode);
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrencyChoices($nameViewStyle = null)
    {
        return ['$' => 'USD', '€' => 'EUR'];
    }
}
