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
 *   - oro_entity_do_not_lowercase_noun_locales
 */
class LocaleExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly ?array $localesNotInLowercase
    ) {
    }

    #[\Override]
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
            new TwigFunction('oro_format_address_by_address_country', [$this, 'isFormatAddressByAddressCountry']),
            new TwigFunction('oro_entity_do_not_lowercase_noun_locales', [$this, 'isNotNeedToLowerCaseNounLocale']),
        ];
    }

    public function getCurrencyName(string $currency, ?string $displayLocale = null): ?string
    {
        return Currencies::getName($currency, $displayLocale);
    }

    public function getLocale(): string
    {
        return $this->getLocaleSettings()->getLocale();
    }

    public function getLanguage(): string
    {
        return $this->getLocaleSettings()->getLanguage();
    }

    public function isRtlMode(): bool
    {
        return $this->getLocaleSettings()->isRtlMode();
    }

    public function getCountry(): string
    {
        return $this->getLocaleSettings()->getCountry();
    }

    public function getCurrencySymbolByCurrency(?string $currencyCode = null): ?string
    {
        return $this->getLocaleSettings()->getCurrencySymbolByCurrency($currencyCode);
    }

    public function isNotNeedToLowerCaseNounLocale(): bool
    {
        return \in_array($this->getLocale(), $this->localesNotInLowercase, true);
    }

    public function getCurrency(): string
    {
        return $this->getLocaleSettings()->getCurrency();
    }

    public function getTimeZone(): string
    {
        return $this->getLocaleSettings()->getTimeZone();
    }

    public function getTimeZoneOffset(): string
    {
        return (new \DateTime('now', new \DateTimeZone($this->getLocaleSettings()->getTimeZone())))->format('P');
    }

    public function isFormatAddressByAddressCountry(): bool
    {
        return $this->getLocaleSettings()->isFormatAddressByAddressCountry();
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            LocaleSettings::class
        ];
    }

    private function getLocaleSettings(): LocaleSettings
    {
        return $this->container->get(LocaleSettings::class);
    }
}
