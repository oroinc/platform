<?php

namespace Oro\Bundle\LocaleBundle\Model;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\DependencyInjection\Configuration as CurrencyConfig;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Symfony\Component\Intl\Intl;

/**
 * Provides locale related information such as locale's language and currency and also holds some helper methods.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class LocaleSettings
{
    const ADDRESS_FORMAT_KEY  = 'format';
    const PHONE_PREFIX_KEY    = 'phone_prefix';
    const DEFAULT_LOCALE_KEY  = 'default_locale';
    const CURRENCY_CODE_KEY   = 'currency_code';
    const CURRENCY_SYMBOL_KEY = 'symbol';

    /**
     * @var string[]
     */
    protected static $locales;

    /**
     * @var string
     */
    protected $locale;

    /**
     * @var string
     */
    protected $language;

    /**
     * @var string
     */
    protected $country;

    /**
     * @var string
     */
    protected $currency;

    /**
     * @var string
     */
    protected $timeZone;

    /**
     * Format placeholders (lowercase and uppercase):
     * - %prefix%      / %PREFIX%
     * - %first_name%  / %FIRST_NAME%
     * - %middle_name% / %MIDDLE_NAME%
     * - %last_name%   / %LAST_NAME%
     * - %suffix%      / %SUFFIX%
     *
     * Array format:
     * array(
     *     '<locale>' => '<formatString>',
     *     ...
     * )
     *
     * @var array
     */
    protected $nameFormats = [];

    /**
     * Format placeholders (lowercase and uppercase):
     * - %postal_code%  / %POSTAL_CODE%
     * - %name%         / %NAME%
     * - %organization% / %ORGANIZATION%
     * - %street%       / %STREET%
     * - %street1%      / %STREET1%
     * - %street2%      / %STREET2%
     * - %city%         / %CITY%
     * - %region%       / %REGION%
     * - %region_code%  / %REGION_CODE%
     * - %country%      / %COUNTRY%
     * - %country_iso2% / %COUNTRY_ISO2%
     * - %country_iso3% / %COUNTRY_ISO3%
     *
     * Array format:
     * array(
     *     '<countryCode>' => array(
     *          'format' => '<formatString>',
     *          ...
     *     ),
     *     ...
     * )
     *
     * @var array
     */
    protected $addressFormats = [];

    /**
     * Array format:
     * array(
     *     '<countryCode>' => array(
     *          'default_locale' => '<defaultLocaleString>',
     *          'currency_code'  => '<currencyIso3SymbolsCode>',
     *          'phone_prefix'   => '<phonePrefixString>', // optional
     *     ),
     * )
     *
     * @var array
     */
    protected $localeData = [];

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var CalendarFactoryInterface
     */
    protected $calendarFactory;

    /**
     * @var LocalizationManager
     */
    protected $localizationManager;

    /**
     * @param ConfigManager $configManager
     * @param CalendarFactoryInterface $calendarFactory
     * @param LocalizationManager $localizationManager
     */
    public function __construct(
        ConfigManager $configManager,
        CalendarFactoryInterface $calendarFactory,
        LocalizationManager $localizationManager
    ) {
        $this->configManager = $configManager;
        $this->calendarFactory = $calendarFactory;
        $this->localizationManager = $localizationManager;
    }

    /**
     * Adds name formats.
     *
     * @param array $formats
     */
    public function addNameFormats(array $formats)
    {
        $this->nameFormats = array_merge($this->nameFormats, $formats);
    }

    /**
     * Get name formats.
     *
     * @return array
     */
    public function getNameFormats()
    {
        return $this->nameFormats;
    }

    /**
     * Adds address formats.
     *
     * @param array $formats
     */
    public function addAddressFormats(array $formats)
    {
        $this->addressFormats = array_merge($this->addressFormats, $formats);
    }

    /**
     * Get address formats.
     *
     * @return array
     */
    public function getAddressFormats()
    {
        return $this->addressFormats;
    }

    /**
     * Adds locale data.
     *
     * @param array $data
     */
    public function addLocaleData(array $data)
    {
        $this->localeData = array_merge($this->localeData, $data);
    }

    /**
     * Get locale data.
     *
     * @return array
     */
    public function getLocaleData()
    {
        return $this->localeData;
    }

    /**
     * @return boolean
     */
    public function isFormatAddressByAddressCountry()
    {
        return (bool)$this->configManager->get('oro_locale.format_address_by_address_country');
    }

    /**
     * Gets locale by country
     *
     * @param string $country Country code
     * @return string
     */
    public function getLocaleByCountry($country)
    {
        if (isset($this->localeData[$country][self::DEFAULT_LOCALE_KEY])) {
            return $this->localeData[$country][self::DEFAULT_LOCALE_KEY];
        }
        return $this->getLocale();
    }

    /**
     * Get locale
     *
     * @return string
     */
    public function getLocale()
    {
        if (null === $this->locale) {
            $localization = $this->localizationManager->getLocalizationData(
                (int)$this->configManager->get(Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION))
            );

            $this->locale = $localization['formattingCode'] ?? Configuration::DEFAULT_LOCALE;
        }
        return $this->locale;
    }

    /**
     * Get language
     *
     * @return string
     */
    public function getLanguage()
    {
        if (null === $this->language) {
            $this->language = $this->getLanguageConfigurationValue();
        }

        return $this->language;
    }

    /**
     * @return string
     */
    public function getActualLanguage()
    {
        return $this->getLanguageConfigurationValue();
    }

    /**
     * Get default country
     *
     * @return string
     */
    public function getCountry()
    {
        if (null === $this->country) {
            $this->country = $this->configManager->get('oro_locale.country');
            if (!$this->country) {
                $this->country = self::getCountryByLocale($this->getLocale());
            }
        }
        return $this->country;
    }

    /**
     * Get currency
     *
     * @return string
     */
    public function getCurrency()
    {
        if (null === $this->currency) {
            $this->currency = $this->configManager->get(
                CurrencyConfig::getConfigKeyByName(CurrencyConfig::KEY_DEFAULT_CURRENCY)
            );
            if (!$this->currency) {
                $this->currency = CurrencyConfig::DEFAULT_CURRENCY;
            }
        }
        return $this->currency;
    }

    /**
     * @param string|null $currencyCode
     * @param string|null $locale
     *
     * @return bool|string The symbol value or false on error
     */
    public function getCurrencySymbolByCurrency(string $currencyCode = null, string $locale = null)
    {
        if (!$currencyCode) {
            $currencyCode = $this->getCurrency();
        }

        if (!$locale) {
            $locale = $this->getLocale();
        }

        $formatter = new \NumberFormatter($locale . '@currency=' . $currencyCode, \NumberFormatter::CURRENCY);

        return $formatter->getSymbol(\NumberFormatter::CURRENCY_SYMBOL) ?: $currencyCode;
    }

    /**
     * Get time zone
     *
     * @return string
     */
    public function getTimeZone()
    {
        if (null === $this->timeZone) {
            $this->timeZone = $this->configManager->get('oro_locale.timezone');
            if (!$this->timeZone) {
                $this->timeZone = date_default_timezone_get();
            }
        }
        return $this->timeZone;
    }

    /**
     * Get calendar instance
     *
     * @param string|null $locale
     * @param string|null $language
     * @return Calendar
     */
    public function getCalendar($locale = null, $language = null)
    {
        return $this->calendarFactory->getCalendar(
            $locale ?: $this->getLocale(),
            $language ?: $this->getLanguage()
        );
    }

    /**
     * Try to parse locale and return it in format "language"_"region",
     * if locale is empty or cannot be parsed then return locale
     *
     * @param string $locale
     * @return string
     * @throws \RuntimeException
     */
    public static function getValidLocale($locale = null)
    {
        if (!$locale) {
            $locale = Configuration::DEFAULT_LOCALE;
        }

        $localeParts = \Locale::parseLocale($locale);
        $lang = null;
        $script = null;
        $region = null;

        if (isset($localeParts[\Locale::LANG_TAG])) {
            $lang = $localeParts[\Locale::LANG_TAG];
        }
        if (isset($localeParts[\Locale::SCRIPT_TAG])) {
            $script = $localeParts[\Locale::SCRIPT_TAG];
        }
        if (isset($localeParts[\Locale::REGION_TAG])) {
            $region = $localeParts[\Locale::REGION_TAG];
        }

        $variants = [
            [$lang, $script, $region],
            [$lang, $region],
            [$lang, $script, Configuration::DEFAULT_COUNTRY],
            [$lang, Configuration::DEFAULT_COUNTRY],
            [$lang],
            [Configuration::DEFAULT_LOCALE, Configuration::DEFAULT_COUNTRY],
            [Configuration::DEFAULT_LOCALE],
        ];

        $locales = self::getLocales();
        foreach ($variants as $elements) {
            $locale = implode('_', array_filter($elements));
            if (isset($locales[$locale])) {
                return $locale;
            }
        }

        throw new \RuntimeException(sprintf('Cannot validate locale "%s"', $locale));
    }

    /**
     * Returns list of all locales
     *
     * @return string[]
     */
    public static function getLocales()
    {
        if (null === self::$locales) {
            self::$locales = [];
            foreach (Intl::getLocaleBundle()->getLocales() as $locale) {
                self::$locales[$locale] = $locale;
            }
        }
        return self::$locales;
    }

    /**
     * Get country by locale if country is supported, otherwise return default country (US)
     *
     * @param string $locale
     * @return string
     */
    public static function getCountryByLocale($locale)
    {
        $region = \Locale::getRegion($locale);
        $countries = Intl::getRegionBundle()->getCountryNames();
        if (array_key_exists($region, $countries)) {
            return $region;
        }

        return Configuration::DEFAULT_COUNTRY;
    }

    /**
     * Get locale and return currency
     * If locale have 2 symbol like "eq"
     * method has returned default currency
     *
     * @param string $locale
     * @return string currency ISO code
     */
    public static function getCurrencyByLocale($locale)
    {
        if (strlen($locale) === 2) {
            $locale = sprintf("%s_%s", $locale, self::getCountryByLocale($locale));
        }
        $formatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);
        return $formatter->getTextAttribute(\NumberFormatter::CURRENCY_CODE);
    }

    /**
     * Get locale code according to ISO 15897
     *
     * We use this locale to receive
     * right currency symbols
     *
     * @return string
     */
    public function getLocaleWithRegion()
    {
        $locale = $this->getLocale();
        if (strlen($locale) > 2) {
            return $locale;
        }

        $country = $this->getCountry();
        return $this->getLocaleByCountry($country);
    }

    /**
     * @param string $settingName
     *
     * @return mixed
     */
    public function get($settingName)
    {
        return $this->configManager->get($settingName);
    }

    /**
     * @param array  $codes
     * @param string $locale
     *
     * @return array
     */
    public function getLocalesByCodes(array $codes, $locale = 'en')
    {
        $localeLabels = Intl::getLocaleBundle()->getLocaleNames($locale);

        return array_intersect_key($localeLabels, array_combine($codes, $codes));
    }

    /**
     * @return int
     */
    public function getFirstQuarterMonth()
    {
        return $this->get('oro_locale.quarter_start')['month'];
    }

    /**
     * @return int
     */
    public function getFirstQuarterDay()
    {
        return $this->get('oro_locale.quarter_start')['day'];
    }

    /**
     * @return string
     */
    private function getLanguageConfigurationValue(): string
    {
        $localization = $this->localizationManager->getLocalizationData(
            (int)$this->configManager->get(Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION))
        );

        return $localization['languageCode'] ?? Configuration::DEFAULT_LANGUAGE;
    }
}
