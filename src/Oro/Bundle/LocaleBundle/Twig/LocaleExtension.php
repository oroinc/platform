<?php

namespace Oro\Bundle\LocaleBundle\Twig;

use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Psr\Container\ContainerInterface;
use Symfony\Component\Intl\Currencies;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions to retrieve various localization-related settings:
 *   - oro_currency_name
 *   - oro_locale
 *   - oro_language
 *   - oro_is_rtl_mode
 *   - oro_country
 *   - oro_currency_symbol
 *   - oro_currency
 *   - oro_timezone
 *   - oro_timezone_offset
 *   - oro_format_address_by_address_country
 */
class LocaleExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private ContainerInterface $container;
    private ?LocaleSettings $localeSettings = null;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('oro_currency_name', [$this, 'getCurrencyName']),
            new TwigFunction('oro_locale', [$this, 'getLocale']),
            new TwigFunction('oro_language', [$this, 'getLanguage']),
            new TwigFunction('oro_is_rtl_mode', [$this, 'isRtlMode']),
            new TwigFunction('oro_country', [$this, 'getCountry']),
            new TwigFunction('oro_currency_symbol', [$this, 'getCurrencySymbolByCurrency']),
            new TwigFunction('oro_currency', [$this, 'getCurrency']),
            new TwigFunction('oro_timezone', [$this, 'getTimeZone']),
            new TwigFunction('oro_timezone_offset', [$this, 'getTimeZoneOffset']),
            new TwigFunction(
                'oro_format_address_by_address_country',
                [$this, 'isFormatAddressByAddressCountry']
            ),
        ];
    }

    /**
     * @param string      $currency
     * @param string|null $displayLocale
     *
     * @return string|null
     */
    public function getCurrencyName($currency, $displayLocale = null)
    {
        return Currencies::getName($currency, $displayLocale);
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->getLocaleSettings()->getLocale();
    }

    /**
     * @return string
     */
    public function getLanguage()
    {
        return $this->getLocaleSettings()->getLanguage();
    }

    public function isRtlMode(): bool
    {
        return $this->getLocaleSettings()->isRtlMode();
    }

    /**
     * @return string
     */
    public function getCountry()
    {
        return $this->getLocaleSettings()->getCountry();
    }

    /**
     * @param string|null $currencyCode
     *
     * @return string|null
     */
    public function getCurrencySymbolByCurrency($currencyCode = null)
    {
        return $this->getLocaleSettings()->getCurrencySymbolByCurrency($currencyCode);
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->getLocaleSettings()->getCurrency();
    }

    /**
     * @return string
     */
    public function getTimeZone()
    {
        return $this->getLocaleSettings()->getTimeZone();
    }

    /**
     * @return string
     */
    public function getTimeZoneOffset()
    {
        $date = new \DateTime('now', new \DateTimeZone($this->getLocaleSettings()->getTimeZone()));

        return $date->format('P');
    }

    /**
     * @return bool
     */
    public function isFormatAddressByAddressCountry()
    {
        return $this->getLocaleSettings()->isFormatAddressByAddressCountry();
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            LocaleSettings::class,
        ];
    }

    private function getLocaleSettings(): LocaleSettings
    {
        if (null === $this->localeSettings) {
            $this->localeSettings = $this->container->get(LocaleSettings::class);
        }

        return $this->localeSettings;
    }
}
