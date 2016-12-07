<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Units\Utils;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Provider\ViewTypeProviderInterface;
use Oro\Bundle\CurrencyBundle\Tests\Unit\Provider\CurrencyStubProvider;
use Oro\Bundle\CurrencyBundle\Utils\CurrencyNameHelper;

class CurrencyNameHelperTest extends \PHPUnit_Framework_TestCase implements ViewTypeProviderInterface
{
    /** @var  string */
    private $viewType;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Oro\Bundle\LocaleBundle\Formatter\NumberFormatter
     */
    protected $formatter;

    public function setUp()
    {
        $this->formatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\NumberFormatter')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testGetCurrencyName()
    {
        $currencyNameHelper = new CurrencyNameHelper(
            $this->getLocaleStub('en'),
            $this->formatter,
            $this,
            new CurrencyStubProvider()
        );

        $this->viewType = ViewTypeProviderInterface::VIEW_TYPE_ISO_CODE;
        $this->assertEquals('USD', $currencyNameHelper->getCurrencyName('USD'));

        $this->viewType = ViewTypeProviderInterface::VIEW_TYPE_SYMBOL;
        $this->assertEquals('$', $currencyNameHelper->getCurrencyName('USD'));
    }

    public function testGetCurrencyNameWithFullName()
    {
        $currencyNameHelper = new CurrencyNameHelper(
            $this->getLocaleStub('en'),
            $this->formatter,
            $this,
            new CurrencyStubProvider()
        );

        $this->viewType = ViewTypeProviderInterface::VIEW_TYPE_FULL_NAME;
        $this->assertEquals('US Dollar (USD)', $currencyNameHelper->getCurrencyName('USD'));
    }

    public function testGetCurrencyNameForFrenchLocale()
    {
        $currencyNameHelper = new CurrencyNameHelper(
            $this->getLocaleStub('fr'),
            $this->formatter,
            $this,
            new CurrencyStubProvider()
        );

        $this->viewType = ViewTypeProviderInterface::VIEW_TYPE_ISO_CODE;
        $this->assertEquals('USD', $currencyNameHelper->getCurrencyName('USD'));

        $this->viewType = ViewTypeProviderInterface::VIEW_TYPE_SYMBOL;
        $this->assertEquals('$US', $currencyNameHelper->getCurrencyName('USD'));
    }

    public function testGetCurrencyNameForLocalCurrencies()
    {
        $currencyNameHelper = new CurrencyNameHelper(
            $this->getLocaleStub('en'),
            $this->formatter,
            $this,
            new CurrencyStubProvider()
        );

        $this->viewType = ViewTypeProviderInterface::VIEW_TYPE_SYMBOL;
        $this->assertEquals('₴', $currencyNameHelper->getCurrencyName('UAH'));
    }

    public function testGetCurrencyChoices()
    {
        $this->viewType = ViewTypeProviderInterface::VIEW_TYPE_SYMBOL;
        $currencyProvider = new CurrencyStubProvider();
        $currencyProvider->setCurrencyList(['USD', 'EUR']);

        $currencyNameHelper = new CurrencyNameHelper(
            $this->getLocaleStub('en'),
            $this->formatter,
            $this,
            new CurrencyStubProvider()
        );

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

    /**
     * @param Price $price
     * @param array $options
     * @param string $expected
     * @dataProvider formatCurrencyDataProvider
     */
    public function testFormatCurrency(Price $price, array $options, $expected)
    {
        $currencyNameHelper = new CurrencyNameHelper(
            $this->getLocaleStub('en'),
            $this->formatter,
            $this,
            new CurrencyStubProvider()
        );

        $this->formatter->expects($this->once())->method('formatCurrency')
            ->with(
                $price->getValue(),
                $price->getCurrency(),
                $options['attributes'],
                $options['textAttributes'],
                $options['symbols'],
                $options['locale']
            )
            ->will($this->returnValue($expected));

        $this->assertEquals($expected, $currencyNameHelper->formatPrice($price, $options));
    }

    /**
     * @return array
     */
    public function formatCurrencyDataProvider()
    {
        return [
            '$1,234.5' => [
                'price' => new Price(1234.5, 'USD'),
                'options' => [
                    'attributes' => ['grouping_size' => 3],
                    'textAttributes' => ['grouping_separator_symbol' => ','],
                    'symbols' => ['symbols' => '$'],
                    'locale' => 'en_US'
                ],
                'expected' => '$1,234.5'
            ]
        ];
    }
}
