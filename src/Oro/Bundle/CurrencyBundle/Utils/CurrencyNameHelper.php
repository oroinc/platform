<?php

namespace Oro\Bundle\CurrencyBundle\Utils;

use Symfony\Component\Intl\Intl;

use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;
use Oro\Bundle\CurrencyBundle\Provider\ViewTypeProviderInterface;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;

class CurrencyNameHelper
{
    /** @var ViewTypeProviderInterface  */
    protected $viewTypeProvider;

    /** @var \Symfony\Component\Intl\ResourceBundle\CurrencyBundleInterface  */
    protected $intlCurrencyBundle;

    public function __construct(
        LocaleSettings $localeSettings,
        ViewTypeProviderInterface $viewTypeProvider,
        CurrencyProviderInterface $currencyProvider
    ) {
        $this->viewTypeProvider = $viewTypeProvider;
        $this->localeSettings = $localeSettings;
        $this->currencyProvider = $currencyProvider;
        $this->intlCurrencyBundle = Intl::getCurrencyBundle();
    }

    /**
     * Return list of available currencies which can be used to present them to user
     *
     * @param string $nameViewStyle
     * @return array returns currency ISO codes with correct symbol (ex. USD => $, EUR => €)
     *               or name (USD => US Dollar, EUR => Euro)
     */
    public function getCurrencyChoices($nameViewStyle = null)
    {
        $currencyChoiceList = [];

        foreach ($this->currencyProvider->getCurrencyList() as $currencyIsoCode) {
            $currencyChoiceList[$currencyIsoCode] = $this->getCurrencyName($currencyIsoCode, $nameViewStyle);
        }

        return $currencyChoiceList;
    }

    /**
     * @param string $currencyIsoCode currency ISO code (ex. USD, EUR)
     * @param string $nameViewStyle one of the constants from ViewTypeProviderInterface. Optional
     *
     * @return string
     */
    public function getCurrencyName($currencyIsoCode, $nameViewStyle = null)
    {
        $locale = $this->localeSettings->getLocale();

        if (null === $nameViewStyle) {
            $nameViewStyle = $this->viewTypeProvider->getViewType();
        }

        switch ($nameViewStyle) {
            case ViewTypeProviderInterface::VIEW_TYPE_SYMBOL:
                $currencyName = $this->intlCurrencyBundle->getCurrencySymbol($currencyIsoCode, $locale);
                if ($currencyName === $currencyIsoCode) {
                    $currencyName = $this->localeSettings->getCurrencySymbolByCurrency($currencyIsoCode);
                }
                break;
            case ViewTypeProviderInterface::VIEW_TYPE_FULL_NAME:
                $currencyName = $this->intlCurrencyBundle->getCurrencyName($currencyIsoCode, $locale);
                $currencyName = sprintf(
                    "%s (%s)",
                    $currencyName,
                    $this->getCurrencyName($currencyIsoCode, $this->viewTypeProvider->getViewType())
                );
                break;
            case ViewTypeProviderInterface::VIEW_TYPE_ISO_CODE:
            default:
                $currencyName = $currencyIsoCode;
        }

        return $currencyName;
    }

    /**
     * Filter out outdated currencies
     * for example Belarusian New Ruble (1994–1999)
     *
     * @return string[] list of currencies ISO codes
     */
    public function getCurrencyFilteredList()
    {
        $collection = $this->intlCurrencyBundle->getCurrencyNames();

        return array_filter($collection, function ($value) {
            return (false === stripos($value, '('));
        });
    }
}
