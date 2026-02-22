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

class CurrencyNameHelperTest extends TestCase
{
    private NumberFormatter&MockObject $formatter;
    private ViewTypeProviderInterface&MockObject $viewTypeProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->formatter = $this->createMock(NumberFormatter::class);
        $this->viewTypeProvider = $this->createMock(ViewTypeProviderInterface::class);
    }

    private function getCurrencyNameHelper(string $localeCode): CurrencyNameHelper
    {
        return new CurrencyNameHelper(
            $this->getLocaleSettings($localeCode),
            $this->formatter,
            $this->viewTypeProvider,
            new CurrencyListProviderStub()
        );
    }

    private function getLocaleSettings(string $localeCode): LocaleSettings
    {
        $configManager = $this->createMock(ConfigManager::class);
        $configManager->expects(self::any())
            ->method('get')
            ->with('oro_locale.default_localization')
            ->willReturn(42);

        $calendarFactory = $this->createMock(CalendarFactory::class);

        $localizationManager = $this->createMock(LocalizationManager::class);
        $localizationManager->expects(self::any())
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

    public function testGetCurrencyNameWhenViewTypeIsIsoCode(): void
    {
        $this->viewTypeProvider->expects(self::once())
            ->method('getViewType')
            ->willReturn(ViewTypeProviderInterface::VIEW_TYPE_ISO_CODE);

        $currencyNameHelper = $this->getCurrencyNameHelper('en');
        self::assertEquals('USD', $currencyNameHelper->getCurrencyName('USD'));
    }

    public function testGetCurrencyNameWhenViewTypeIsSymbol(): void
    {
        $this->viewTypeProvider->expects(self::once())
            ->method('getViewType')
            ->willReturn(ViewTypeProviderInterface::VIEW_TYPE_SYMBOL);

        $currencyNameHelper = $this->getCurrencyNameHelper('en');
        self::assertEquals('$', $currencyNameHelper->getCurrencyName('USD'));
    }

    public function testGetCurrencyNameWithFullName(): void
    {
        $this->viewTypeProvider->expects(self::once())
            ->method('getViewType')
            ->willReturn(ViewTypeProviderInterface::VIEW_TYPE_ISO_CODE);

        $currencyNameHelper = $this->getCurrencyNameHelper('en');
        self::assertEquals(
            'US Dollar (USD)',
            $currencyNameHelper->getCurrencyName('USD', ViewTypeProviderInterface::VIEW_TYPE_FULL_NAME)
        );
    }

    public function testGetCurrencyNameForFrenchLocaleWhenViewTypeIsIsoCode(): void
    {
        $this->viewTypeProvider->expects(self::once())
            ->method('getViewType')
            ->willReturn(ViewTypeProviderInterface::VIEW_TYPE_ISO_CODE);

        $currencyNameHelper = $this->getCurrencyNameHelper('fr');
        self::assertEquals('USD', $currencyNameHelper->getCurrencyName('USD'));
    }

    public function testGetCurrencyNameForFrenchLocaleWhenViewTypeIsSymbol(): void
    {
        $this->viewTypeProvider->expects(self::once())
            ->method('getViewType')
            ->willReturn(ViewTypeProviderInterface::VIEW_TYPE_SYMBOL);

        $currencyNameHelper = $this->getCurrencyNameHelper('fr');
        self::assertEquals('$US', $currencyNameHelper->getCurrencyName('USD'));
    }

    public function testGetCurrencyNameForLocalCurrencies(): void
    {
        $this->viewTypeProvider->expects(self::once())
            ->method('getViewType')
            ->willReturn(ViewTypeProviderInterface::VIEW_TYPE_SYMBOL);

        $currencyNameHelper = $this->getCurrencyNameHelper('en');
        self::assertEquals('UAH', $currencyNameHelper->getCurrencyName('UAH'));
    }

    public function testGetCurrencyChoices(): void
    {
        $this->viewTypeProvider->expects(self::atLeastOnce())
            ->method('getViewType')
            ->willReturn(ViewTypeProviderInterface::VIEW_TYPE_SYMBOL);

        $currencyNameHelper = $this->getCurrencyNameHelper('en');
        self::assertEquals(['$' => 'USD', '€' => 'EUR'], $currencyNameHelper->getCurrencyChoices());
    }

    /**
     * @dataProvider formatCurrencyDataProvider
     */
    public function testFormatCurrency(Price $price, array $options, string $expected): void
    {
        $this->formatter->expects(self::once())
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

        $currencyNameHelper = $this->getCurrencyNameHelper('en');
        self::assertEquals($expected, $currencyNameHelper->formatPrice($price, $options));
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
