<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Units\Utils;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Provider\ViewTypeProviderInterface;
use Oro\Bundle\CurrencyBundle\Tests\Unit\Provider\CurrencyListProviderStub;
use Oro\Bundle\CurrencyBundle\Utils\CurrencyNameHelper;
use Oro\Bundle\LocaleBundle\Model\CalendarFactory;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;

class CurrencyNameHelperTest extends \PHPUnit\Framework\TestCase implements ViewTypeProviderInterface
{
    /**
     * @var string
     */
    private $viewType;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Oro\Bundle\LocaleBundle\Formatter\NumberFormatter
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
            $this->getLocaleSettings('en'),
            $this->formatter,
            $this,
            new CurrencyListProviderStub()
        );

        $this->viewType = ViewTypeProviderInterface::VIEW_TYPE_ISO_CODE;
        $this->assertEquals('USD', $currencyNameHelper->getCurrencyName('USD'));

        $this->viewType = ViewTypeProviderInterface::VIEW_TYPE_SYMBOL;
        $this->assertEquals('$', $currencyNameHelper->getCurrencyName('USD'));
    }

    public function testGetCurrencyNameWithFullName()
    {
        $currencyNameHelper = new CurrencyNameHelper(
            $this->getLocaleSettings('en'),
            $this->formatter,
            $this,
            new CurrencyListProviderStub()
        );

        $this->viewType = ViewTypeProviderInterface::VIEW_TYPE_ISO_CODE;
        $this->assertEquals(
            'US Dollar (USD)',
            $currencyNameHelper->getCurrencyName('USD', ViewTypeProviderInterface::VIEW_TYPE_FULL_NAME)
        );
    }

    public function testGetCurrencyNameForFrenchLocale()
    {
        $currencyNameHelper = new CurrencyNameHelper(
            $this->getLocaleSettings('fr'),
            $this->formatter,
            $this,
            new CurrencyListProviderStub()
        );

        $this->viewType = ViewTypeProviderInterface::VIEW_TYPE_ISO_CODE;
        $this->assertEquals('USD', $currencyNameHelper->getCurrencyName('USD'));

        $this->viewType = ViewTypeProviderInterface::VIEW_TYPE_SYMBOL;
        $this->assertEquals('$US', $currencyNameHelper->getCurrencyName('USD'));
    }

    public function testGetCurrencyNameForLocalCurrencies()
    {
        $currencyNameHelper = new CurrencyNameHelper(
            $this->getLocaleSettings('en'),
            $this->formatter,
            $this,
            new CurrencyListProviderStub()
        );

        $this->viewType = ViewTypeProviderInterface::VIEW_TYPE_SYMBOL;
        $this->assertEquals('UAH', $currencyNameHelper->getCurrencyName('UAH'));
    }

    public function testGetCurrencyChoices()
    {
        $this->viewType = ViewTypeProviderInterface::VIEW_TYPE_SYMBOL;

        $currencyNameHelper = new CurrencyNameHelper(
            $this->getLocaleSettings('en'),
            $this->formatter,
            $this,
            new CurrencyListProviderStub()
        );

        $this->assertEquals(['$' => 'USD', '€' => 'EUR'], $currencyNameHelper->getCurrencyChoices());
    }

    /**
     * @return string
     */
    public function getViewType()
    {
        return $this->viewType;
    }

    /**
     * @param string $localeCode
     * @return LocaleSettings
     */
    private function getLocaleSettings(string $localeCode): LocaleSettings
    {
        /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject $configManager */
        $configManager = $this->createMock(ConfigManager::class);
        $configManager->expects($this->any())
            ->method('get')
            ->with('oro_locale.locale')
            ->willReturn($localeCode);

        /** @var CalendarFactory $calendarFactory */
        $calendarFactory = $this->createMock(CalendarFactory::class);

        return new LocaleSettings($configManager, $calendarFactory);
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
            $this->getLocaleSettings('en'),
            $this->formatter,
            $this,
            new CurrencyListProviderStub()
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
