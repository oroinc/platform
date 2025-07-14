<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\Utils;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Provider\ViewTypeProviderInterface;
use Oro\Bundle\CurrencyBundle\Tests\Unit\Provider\CurrencyListProviderStub;
use Oro\Bundle\CurrencyBundle\Utils\CurrencyNameHelper;
use Oro\Bundle\LocaleBundle\Configuration\LocaleConfigurationProvider;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\LocaleBundle\Model\CalendarFactory;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\ThemeBundle\Model\ThemeRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CurrencyNameHelperTest extends TestCase implements ViewTypeProviderInterface
{
    private string $viewType;
    private NumberFormatter&MockObject $formatter;

    #[\Override]
    protected function setUp(): void
    {
        $this->formatter = $this->createMock(NumberFormatter::class);
    }

    public function testGetCurrencyName(): void
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

    public function testGetCurrencyNameWithFullName(): void
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

    public function testGetCurrencyNameForFrenchLocale(): void
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

    public function testGetCurrencyNameForLocalCurrencies(): void
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

    public function testGetCurrencyChoices(): void
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

    #[\Override]
    public function getViewType(): string
    {
        return $this->viewType;
    }

    private function getLocaleSettings(string $localeCode): LocaleSettings
    {
        $configManager = $this->createMock(ConfigManager::class);
        $configManager->expects($this->any())
            ->method('get')
            ->with('oro_locale.default_localization')
            ->willReturn(42);

        $calendarFactory = $this->createMock(CalendarFactory::class);

        $localizationManager = $this->createMock(LocalizationManager::class);
        $localizationManager->expects($this->any())
            ->method('getLocalizationData')
            ->with(42)
            ->willReturn(['id' => 42, 'formattingCode' => $localeCode]);

        $localeConfigProvider = $this->createMock(LocaleConfigurationProvider::class);

        $themeRegistry = $this->createMock(ThemeRegistry::class);

        return new LocaleSettings(
            $configManager,
            $calendarFactory,
            $localizationManager,
            $localeConfigProvider,
            $themeRegistry
        );
    }

    /**
     * @dataProvider formatCurrencyDataProvider
     */
    public function testFormatCurrency(Price $price, array $options, string $expected): void
    {
        $currencyNameHelper = new CurrencyNameHelper(
            $this->getLocaleSettings('en'),
            $this->formatter,
            $this,
            new CurrencyListProviderStub()
        );

        $this->formatter->expects($this->once())
            ->method('formatCurrency')
            ->with(
                $price->getValue(),
                $price->getCurrency(),
                $options['attributes'],
                $options['textAttributes'],
                $options['symbols'],
                $options['locale']
            )
            ->willReturn($expected);

        $this->assertEquals($expected, $currencyNameHelper->formatPrice($price, $options));
    }

    public function formatCurrencyDataProvider(): array
    {
        return [
            '$1,234.5' => [
                'price' => Price::create(1234.5, 'USD'),
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
