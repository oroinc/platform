<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Model;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\DependencyInjection\Configuration as CurrencyConfiguration;
use Oro\Bundle\LocaleBundle\Configuration\LocaleConfigurationProvider;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration as LocaleConfiguration;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\LocaleBundle\Model\Calendar;
use Oro\Bundle\LocaleBundle\Model\CalendarFactoryInterface;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\ThemeBundle\Model\Theme;
use Oro\Bundle\ThemeBundle\Model\ThemeRegistry;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class LocaleSettingsTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var CalendarFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $calendarFactory;

    /** @var LocalizationManager|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationManager;

    /** @var LocaleConfigurationProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $localeConfigProvider;

    /** @var ThemeRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $themeRegistry;

    /** @var LocaleSettings */
    private $localeSettings;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->calendarFactory = $this->createMock(CalendarFactoryInterface::class);
        $this->localizationManager = $this->createMock(LocalizationManager::class);
        $this->localeConfigProvider = $this->createMock(LocaleConfigurationProvider::class);
        $this->themeRegistry = $this->createMock(ThemeRegistry::class);

        $this->localeSettings = new LocaleSettings(
            $this->configManager,
            $this->calendarFactory,
            $this->localizationManager,
            $this->localeConfigProvider,
            $this->themeRegistry
        );
    }

    public function testGetNameFormats()
    {
        $enFormat = '%first_name% %middle_name% %last_name%';

        $this->localeConfigProvider->expects($this->once())
            ->method('getNameFormats')
            ->willReturn(['en' => $enFormat]);

        $this->assertEquals(
            ['en' => $enFormat],
            $this->localeSettings->getNameFormats()
        );
    }

    public function testAddNameFormats()
    {
        $enFormat = '%first_name% %middle_name% %last_name%';
        $enFormatModified = '%prefix% %%first_name% %middle_name% %last_name% %suffix%';
        $ruFormat = '%last_name% %first_name% %middle_name%';

        $this->localeConfigProvider->expects($this->once())
            ->method('getNameFormats')
            ->willReturn(['en' => $enFormat]);

        $this->localeSettings->addNameFormats(['en' => $enFormatModified, 'ru' => $ruFormat]);
        $this->assertEquals(
            ['en' => $enFormatModified, 'ru' => $ruFormat],
            $this->localeSettings->getNameFormats()
        );
    }

    public function testGetAddressFormats()
    {
        $usFormat = [
            LocaleSettings::ADDRESS_FORMAT_KEY
            => '%name%\n%organization%\n%street%\n%CITY% %REGION% %COUNTRY% %postal_code%'
        ];

        $this->localeConfigProvider->expects($this->once())
            ->method('getAddressFormats')
            ->willReturn(['US' => $usFormat]);

        $this->assertEquals(
            ['US' => $usFormat],
            $this->localeSettings->getAddressFormats()
        );
    }

    public function testAddAddressFormats()
    {
        $usFormat = [
            LocaleSettings::ADDRESS_FORMAT_KEY
            => '%name%\n%organization%\n%street%\n%CITY% %REGION% %COUNTRY% %postal_code%'
        ];
        $usFormatModified = [
            LocaleSettings::ADDRESS_FORMAT_KEY
            => '%name%\n%organization%\n%street%\n%CITY% %REGION_CODE% %COUNTRY% %postal_code%'
        ];
        $ruFormat = [
            LocaleSettings::ADDRESS_FORMAT_KEY
            => '%postal_code% %COUNTRY% %CITY%\n%STREET%\n%organization%\n%name%'
        ];

        $this->localeConfigProvider->expects($this->once())
            ->method('getAddressFormats')
            ->willReturn(['US' => $usFormat]);

        $this->localeSettings->addAddressFormats(['US' => $usFormatModified, 'RU' => $ruFormat]);
        $this->assertEquals(
            ['US' => $usFormatModified, 'RU' => $ruFormat],
            $this->localeSettings->getAddressFormats()
        );
    }

    public function testGetLocaleData()
    {
        $usData = [LocaleSettings::DEFAULT_LOCALE_KEY => 'en_US'];

        $this->localeConfigProvider->expects($this->once())
            ->method('getLocaleData')
            ->willReturn(['US' => $usData]);

        $this->assertEquals(
            ['US' => $usData],
            $this->localeSettings->getLocaleData()
        );
    }

    public function testAddLocaleData()
    {
        $usData = [LocaleSettings::DEFAULT_LOCALE_KEY => 'en_US'];
        $usDataModified = [LocaleSettings::DEFAULT_LOCALE_KEY => 'en'];
        $ruData = [LocaleSettings::DEFAULT_LOCALE_KEY => 'ru'];

        $this->localeConfigProvider->expects($this->once())
            ->method('getLocaleData')
            ->willReturn(['US' => $usData]);

        $this->localeSettings->addLocaleData(['US' => $usDataModified, 'RU' => $ruData]);
        $this->assertEquals(
            ['US' => $usDataModified, 'RU' => $ruData],
            $this->localeSettings->getLocaleData()
        );
    }

    /**
     * @dataProvider getValidLocaleDataProvider
     */
    public function testGetValidLocale(?string $locale, string $expectedLocale): void
    {
        $this->assertEquals($expectedLocale, LocaleSettings::getValidLocale($locale));
    }

    public function getValidLocaleDataProvider(): array
    {
        return [
            ['ru_RU', 'ru_RU'],
            ['en', LocaleConfiguration::DEFAULT_LOCALE],
            [null, LocaleConfiguration::DEFAULT_LOCALE],
            ['ru', 'ru'],
            ['en_Hans_CN_nedis_rozaj_x_prv1_prv2', 'en_US'],
            ['en_Hans_CA_nedis_rozaj_x_prv1_prv2', 'en_CA'],
            ['unknown', 'en_US'],
        ];
    }

    /**
     * @dataProvider getCountryByLocaleDataProvider
     */
    public function testGetCountryByLocale(string $locale, string $expectedCountry): void
    {
        $this->assertEquals($expectedCountry, LocaleSettings::getCountryByLocale($locale));
    }

    public function getCountryByLocaleDataProvider(): array
    {
        return [
            ['EN', LocaleConfiguration::DEFAULT_COUNTRY],
            ['RU', LocaleConfiguration::DEFAULT_COUNTRY],
            ['en_US', 'US'],
            ['en_XX', LocaleConfiguration::DEFAULT_COUNTRY],
        ];
    }

    /**
     * @dataProvider getLocaleByCountryDataProvider
     */
    public function testGetLocaleByCountry(
        array $localeData,
        string $countryCode,
        string $expectedLocale,
        ?string $defaultLocale = null
    ) {
        $this->localeSettings->addLocaleData($localeData);

        if (null !== $defaultLocale) {
            $this->configManager->expects($this->once())
                ->method('get')
                ->with('oro_locale.default_localization')
                ->willReturn(42);

            $this->localizationManager->expects($this->once())
                ->method('getLocalizationData')
                ->with(42)
                ->willReturn(['id' => 42, 'formattingCode' => $defaultLocale]);
        } else {
            $this->configManager->expects($this->never())->method($this->anything());
        }

        $this->assertEquals($expectedLocale, $this->localeSettings->getLocaleByCountry($countryCode));
    }

    public function getLocaleByCountryDataProvider(): array
    {
        return [
            [
                ['GB' => [LocaleSettings::DEFAULT_LOCALE_KEY => 'en_GB']],
                'GB',
                'en_GB'
            ],
            [
                [],
                'GB',
                'en_US',
                'en_US'
            ],
        ];
    }

    /**
     * @dataProvider getLocaleDataProvider
     */
    public function testGetLocale(string $expectedValue, ?string $configurationValue)
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_locale.default_localization')
            ->willReturn(42);

        $this->localizationManager->expects($this->once())
            ->method('getLocalizationData')
            ->with(42)
            ->willReturn(['id' => 42, 'formattingCode' => $configurationValue]);

        $this->assertEquals($expectedValue, $this->localeSettings->getLocale());
        $this->assertEquals($expectedValue, $this->localeSettings->getLocale());
    }

    public function getLocaleDataProvider(): array
    {
        return [
            'configuration value' => [
                'expectedValue' => 'ru_RU',
                'configurationValue' => 'ru_RU',
            ],
            'default value' => [
                'expectedValue' => LocaleConfiguration::DEFAULT_LOCALE,
                'configurationValue' => null,
            ],
        ];
    }

    public function testGetCountry()
    {
        $expectedCountry = 'CA';

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_locale.country')
            ->willReturn($expectedCountry);

        $this->assertEquals($expectedCountry, $this->localeSettings->getCountry());
        $this->assertEquals($expectedCountry, $this->localeSettings->getCountry());
    }

    public function testGetCountryDefault()
    {
        $expectedCountry = 'US';

        $this->configManager->expects($this->any())
            ->method('get')
            ->willReturnMap(
                [
                    ['oro_locale.country', false, false, null, null],
                    ['oro_locale.default_localization', false, false, null, 42],
                ]
            );

        $this->localizationManager->expects($this->once())
            ->method('getLocalizationData')
            ->with(42)
            ->willReturn(['id' => 42, 'formattingCode' => 'en_US']);

        $this->assertEquals($expectedCountry, $this->localeSettings->getCountry());
        $this->assertEquals($expectedCountry, $this->localeSettings->getCountry());
    }

    /**
     * @dataProvider getTimeZoneDataProvider
     */
    public function testGetTimeZone(string $expectedValue, ?string $configurationValue)
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_locale.timezone', false)
            ->willReturn($configurationValue);

        $this->assertEquals($expectedValue, $this->localeSettings->getTimeZone());
        $this->assertEquals($expectedValue, $this->localeSettings->getTimeZone());
    }

    public function getTimeZoneDataProvider(): array
    {
        return [
            'configuration value' => [
                'expectedValue' => 'America/Los_Angeles',
                'configurationValue' => 'America/Los_Angeles',
            ],
            'default value' => [
                'expectedValue' => date_default_timezone_get(),
                'configurationValue' => null,
            ],
        ];
    }

    public function testGetCurrency()
    {
        $expectedCurrency = 'GBP';

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_currency.default_currency')
            ->willReturn($expectedCurrency);

        $this->assertEquals($expectedCurrency, $this->localeSettings->getCurrency());
        $this->assertEquals($expectedCurrency, $this->localeSettings->getCurrency());
    }

    public function testGetCurrencyDefault()
    {
        $expectedCurrency = CurrencyConfiguration::DEFAULT_CURRENCY;

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_currency.default_currency')
            ->willReturn(null);

        $this->assertEquals($expectedCurrency, $this->localeSettings->getCurrency());
        $this->assertEquals($expectedCurrency, $this->localeSettings->getCurrency());
    }

    public function testGetCalendarDefaultLocaleAndLanguage()
    {
        $expectedLocale = 'ru_RU';
        $expectedLanguage = 'fr_CA';

        $this->configManager->expects($this->atLeastOnce())
            ->method('get')
            ->with('oro_locale.default_localization')
            ->willReturn(42);

        $this->localizationManager->expects($this->atLeastOnce())
            ->method('getLocalizationData')
            ->with(42)
            ->willReturn(['id' => 42, 'formattingCode' => $expectedLocale, 'languageCode' => $expectedLanguage]);

        $calendar = $this->createMock(Calendar::class);

        $this->calendarFactory->expects($this->once())->method('getCalendar')
            ->with($expectedLocale, $expectedLanguage)
            ->willReturn($calendar);

        $this->assertSame($calendar, $this->localeSettings->getCalendar());
    }

    public function testGetCalendarSpecificLocale()
    {
        $locale = 'ru_RU';
        $language = 'fr_CA';

        $this->configManager->expects($this->never())->method($this->anything());

        $calendar = $this->createMock(Calendar::class);

        $this->calendarFactory->expects($this->once())->method('getCalendar')
            ->with($locale, $language)
            ->willReturn($calendar);

        $this->assertSame($calendar, $this->localeSettings->getCalendar($locale, $language));
    }

    public function testIsFormatAddressByAddressCountry()
    {
        $this->configManager->expects($this->exactly(2))
            ->method('get')
            ->with('oro_locale.format_address_by_address_country')
            ->willReturnOnConsecutiveCalls(
                '',
                '1'
            );

        $this->assertFalse($this->localeSettings->isFormatAddressByAddressCountry());
        $this->assertTrue($this->localeSettings->isFormatAddressByAddressCountry());
    }

    /**
     * @dataProvider getLanguageDataProvider
     */
    public function testGetLanguage(string $expectedValue, ?string $configurationValue)
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_locale.default_localization')
            ->willReturn(42);

        if ($configurationValue) {
            $this->localizationManager->expects($this->once())
                ->method('getLocalizationData')
                ->with(42)
                ->willReturn(['id' => 42, 'languageCode' => $configurationValue]);
        }

        $this->assertEquals($expectedValue, $this->localeSettings->getLanguage());
        $this->assertEquals($expectedValue, $this->localeSettings->getLanguage());
    }

    public function getLanguageDataProvider(): array
    {
        return [
            'configuration value' => [
                'expectedValue' => 'ru',
                'configurationValue' => 'ru',
            ],
            'default value' => [
                'expectedValue' => LocaleConfiguration::DEFAULT_LANGUAGE,
                'configurationValue' => null,
            ],
        ];
    }

    public function testIsRtlModeWhenNoActiveTheme(): void
    {
        $this->themeRegistry->expects(self::any())
            ->method('getActiveTheme')
            ->willReturn(null);

        $this->configManager->expects(self::any())
            ->method('get')
            ->with('oro_locale.default_localization')
            ->willReturn(42);

        $this->localizationManager->expects(self::any())
            ->method('getLocalizationData')
            ->with(42)
            ->willReturn(['rtlMode' => true]);

        self::assertFalse($this->localeSettings->isRtlMode());
    }

    public function testIsRtlModeNoLocalization(): void
    {
        $theme = new Theme('test');
        $theme->setRtlSupport(true);

        $this->themeRegistry->expects(self::any())
            ->method('getActiveTheme')
            ->willReturn($theme);

        $this->configManager->expects(self::any())
            ->method('get')
            ->with('oro_locale.default_localization')
            ->willReturn(42);

        $this->localizationManager->expects(self::any())
            ->method('getLocalizationData')
            ->with(42)
            ->willReturn([]);

        self::assertFalse($this->localeSettings->isRtlMode());
    }

    public function testIsRtlModeWhenThemeWithoutRtl(): void
    {
        $theme = new Theme('test');
        $theme->setRtlSupport(false);

        $this->themeRegistry->expects(self::any())
            ->method('getActiveTheme')
            ->willReturn($theme);

        $this->configManager->expects(self::any())
            ->method('get')
            ->with('oro_locale.default_localization')
            ->willReturn(42);

        $this->localizationManager->expects(self::any())
            ->method('getLocalizationData')
            ->with(42)
            ->willReturn(['rtlMode' => true]);

        self::assertFalse($this->localeSettings->isRtlMode());
    }

    public function testIsRtlModeWhenLocalizationWithoutRtl(): void
    {
        $theme = new Theme('test');
        $theme->setRtlSupport(true);

        $this->themeRegistry->expects(self::any())
            ->method('getActiveTheme')
            ->willReturn($theme);

        $this->configManager->expects(self::any())
            ->method('get')
            ->with('oro_locale.default_localization')
            ->willReturn(42);

        $this->localizationManager->expects(self::any())
            ->method('getLocalizationData')
            ->with(42)
            ->willReturn(['rtlMode' => false]);

        self::assertFalse($this->localeSettings->isRtlMode());
    }

    public function testIsRtlMode(): void
    {
        $theme = new Theme('test');
        $theme->setRtlSupport(true);

        $this->themeRegistry->expects(self::any())
            ->method('getActiveTheme')
            ->willReturn($theme);

        $this->configManager->expects(self::any())
            ->method('get')
            ->with('oro_locale.default_localization')
            ->willReturn(42);

        $this->localizationManager->expects(self::any())
            ->method('getLocalizationData')
            ->with(42)
            ->willReturn(['rtlMode' => true]);

        self::assertTrue($this->localeSettings->isRtlMode());
    }

    public function testGetActualLanguage()
    {
        $en = 'en';
        $fr = 'fr';

        $this->configManager->expects($this->exactly(2))
            ->method('get')
            ->with('oro_locale.default_localization')
            ->willReturn(42);

        $this->localizationManager->expects($this->exactly(2))
            ->method('getLocalizationData')
            ->with(42)
            ->willReturnOnConsecutiveCalls(
                ['id' => 42, 'languageCode' => $en],
                ['id' => 42, 'languageCode' => $fr]
            );

        $this->assertEquals($en, $this->localeSettings->getActualLanguage());
        $this->assertEquals($fr, $this->localeSettings->getActualLanguage());
    }

    public function testGetCurrencySymbolByCurrency()
    {
        $existingCurrencyCode = 'USD';
        $existingCurrencySymbol = '$';
        $notExistingCurrencyCode = 'UAK';

        $this->assertEquals(
            $existingCurrencySymbol,
            $this->localeSettings->getCurrencySymbolByCurrency($existingCurrencyCode)
        );
        $this->assertEquals(
            $notExistingCurrencyCode,
            $this->localeSettings->getCurrencySymbolByCurrency($notExistingCurrencyCode)
        );
        $this->assertEquals(
            $existingCurrencySymbol,
            $this->localeSettings->getCurrencySymbolByCurrency()
        );
    }

    public function testGetLocaleByCode()
    {
        $locales = $this->localeSettings->getLocalesByCodes(['en', 'fr']);
        $this->assertCount(2, $locales);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('test');
        $this->localeSettings->get('test');
    }

    /**
     * @dataProvider localeProvider
     */
    public function testGetCountryByLocal(string $locale, string $expectedCurrency)
    {
        $currency = LocaleSettings::getCurrencyByLocale($locale);

        $this->assertEquals($expectedCurrency, $currency);
    }

    /**
     * The USD is default currency
     */
    public function localeProvider(): array
    {
        return [
            [
                'en',
                'USD'
            ],
            [
                'en_CA',
                $this->getCurrencyBuLocale('en_CA')
            ],
            [
                'it',
                'USD'
            ],
            [
                'it_IT',
                $this->getCurrencyBuLocale('it_IT')
            ],
            [
                'ua',
                'USD'
            ],
            [
                'ru_UA',
                $this->getCurrencyBuLocale('ru_UA')
            ]
        ];
    }

    private function getCurrencyBuLocale(string $locale): string
    {
        $formatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);

        return $formatter->getTextAttribute(\NumberFormatter::CURRENCY_CODE);
    }
}
