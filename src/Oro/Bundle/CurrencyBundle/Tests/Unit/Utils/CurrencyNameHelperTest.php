<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\Utils;

use Oro\Bundle\CurrencyBundle\Tests\Unit\Provider\CurrencyStubProvider;
use Oro\Bundle\CurrencyBundle\Utils\CurrencyNameHelper;
use Oro\Bundle\CurrencyBundle\Provider\ViewTypeProviderInterface;

class CurrencyNameHelperTest extends \PHPUnit_Framework_TestCase implements ViewTypeProviderInterface
{
    /** @var  string */
    private $viewType;

    public function testGetCurrencyName()
    {
        $currencyNameHelper = new CurrencyNameHelper($this->getLocaleStub('en'), $this, new CurrencyStubProvider());

        $this->viewType = ViewTypeProviderInterface::VIEW_TYPE_ISO_CODE;
        $this->assertEquals('USD', $currencyNameHelper->getCurrencyName('USD'));

        $this->viewType = ViewTypeProviderInterface::VIEW_TYPE_SYMBOL;
        $this->assertEquals('$', $currencyNameHelper->getCurrencyName('USD'));
    }

    public function testGetCurrencyNameForFrenchLocale()
    {
        $currencyNameHelper = new CurrencyNameHelper($this->getLocaleStub('fr'), $this, new CurrencyStubProvider());

        $this->viewType = ViewTypeProviderInterface::VIEW_TYPE_ISO_CODE;
        $this->assertEquals('USD', $currencyNameHelper->getCurrencyName('USD'));

        $this->viewType = ViewTypeProviderInterface::VIEW_TYPE_SYMBOL;
        $this->assertEquals('$US', $currencyNameHelper->getCurrencyName('USD'));
    }

    public function testGetCurrencyNameForLocalCurrencies()
    {
        $currencyNameHelper = new CurrencyNameHelper($this->getLocaleStub('en'), $this, new CurrencyStubProvider());

        $this->viewType = ViewTypeProviderInterface::VIEW_TYPE_SYMBOL;
        $this->assertEquals('₴', $currencyNameHelper->getCurrencyName('UAH'));
    }

    public function testGetCurrencyChoices()
    {
        $this->viewType = ViewTypeProviderInterface::VIEW_TYPE_SYMBOL;
        $currencyProvider = new CurrencyStubProvider();
        $currencyProvider->setCurrencyList(['USD', 'EUR']);

        $currencyNameHelper = new CurrencyNameHelper($this->getLocaleStub('en'), $this, $currencyProvider);

        $this->assertEquals(['USD' => '$', 'EUR' => '€'], $currencyNameHelper->getCurrencyChoices());
    }

    public function getViewType()
    {
        return $this->viewType;
    }

    private function getLocaleStub($localeCode)
    {
        $localeSettings = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Model\LocaleSettings')
            ->disableOriginalConstructor()
            ->setMethods(['getLocale', 'getCurrencySymbolByCurrency'])
            ->getMock();

        $localeSettings
            ->expects($this->any())
            ->method('getLocale')
            ->willReturn($localeCode);

        $localeSettings
            ->expects($this->any())
            ->method('getCurrencySymbolByCurrency')
            ->will($this->returnValueMap([
                ['USD', '$'],
                ['UAH', '₴'],
            ]));

        return $localeSettings;
    }
}
