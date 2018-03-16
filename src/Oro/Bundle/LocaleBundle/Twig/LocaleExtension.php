<?php

namespace Oro\Bundle\LocaleBundle\Twig;

use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Intl\Intl;

class LocaleExtension extends \Twig_Extension
{
    /** @var ContainerInterface */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return LocaleSettings
     */
    protected function getLocaleSettings()
    {
        return $this->container->get('oro_locale.settings');
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_locale';
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('oro_currency_name', [$this, 'getCurrencyName']),
            new \Twig_SimpleFunction('oro_locale', [$this, 'getLocale']),
            new \Twig_SimpleFunction('oro_language', [$this, 'getLanguage']),
            new \Twig_SimpleFunction('oro_country', [$this, 'getCountry']),
            new \Twig_SimpleFunction('oro_currency_symbol', [$this, 'getCurrencySymbolByCurrency']),
            new \Twig_SimpleFunction('oro_currency', [$this, 'getCurrency']),
            new \Twig_SimpleFunction('oro_timezone', [$this, 'getTimeZone']),
            new \Twig_SimpleFunction('oro_timezone_offset', [$this, 'getTimeZoneOffset']),
            new \Twig_SimpleFunction(
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
        return Intl::getCurrencyBundle()->getCurrencyName($currency, $displayLocale);
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
}
