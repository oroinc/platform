<?php

namespace Oro\Bundle\CurrencyBundle\Utils;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Provider\CurrencyListProviderInterface;
use Oro\Bundle\CurrencyBundle\Provider\ViewTypeProviderInterface;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Symfony\Component\Intl\Intl;

/**
 * Contains handy methods for working with currencies.
 */
class CurrencyNameHelper
{
    /** @var ViewTypeProviderInterface  */
    protected $viewTypeProvider;

    /** @var \Symfony\Component\Intl\ResourceBundle\CurrencyBundleInterface  */
    protected $intlCurrencyBundle;

    /** @var NumberFormatter */
    protected $formatter;

    /**
     * @param LocaleSettings $localeSettings
     * @param NumberFormatter $formatter
     * @param ViewTypeProviderInterface $viewTypeProvider
     * @param CurrencyListProviderInterface $currencyListProvider
     */
    public function __construct(
        LocaleSettings $localeSettings,
        NumberFormatter $formatter,
        ViewTypeProviderInterface $viewTypeProvider,
        CurrencyListProviderInterface $currencyListProvider
    ) {
        $this->viewTypeProvider = $viewTypeProvider;
        $this->localeSettings = $localeSettings;
        $this->formatter = $formatter;
        $this->currencyProvider = $currencyListProvider;
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
            $currencyChoiceList[$this->getCurrencyName($currencyIsoCode, $nameViewStyle)] = $currencyIsoCode;
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
                $currencyName = $this->localeSettings->getCurrencySymbolByCurrency($currencyIsoCode, $locale);
                break;
            case ViewTypeProviderInterface::VIEW_TYPE_FULL_NAME:
                $currencyName = $this->intlCurrencyBundle->getCurrencyName($currencyIsoCode, $locale);
                $currencyName = sprintf(
                    "%s (%s)",
                    $currencyName,
                    $this->getCurrencyName($currencyIsoCode, $this->viewTypeProvider->getViewType())
                );
                break;
            case ViewTypeProviderInterface::VIEW_TYPE_NAME:
                $currencyName = $this->intlCurrencyBundle->getCurrencyName($currencyIsoCode, $locale);
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

    /**
     * Formats currency number according to locale settings.
     *
     * Options format:
     * array(
     *     'attributes' => array(
     *          <attribute> => <value>,
     *          ...
     *      ),
     *     'textAttributes' => array(
     *          <attribute> => <value>,
     *          ...
     *      ),
     *     'symbols' => array(
     *          <symbol> => <value>,
     *          ...
     *      ),
     *     'locale' => <locale>
     * )
     *
     * @param Price $price
     * @param array $options
     * @return string
     */
    public function formatPrice(Price $price, array $options = [])
    {
        $value = $price->getValue();
        $currency = $price->getCurrency();

        $attributes = (array)$this->getOption($options, 'attributes', []);
        $textAttributes = (array)$this->getOption($options, 'textAttributes', []);
        $symbols = (array)$this->getOption($options, 'symbols', []);
        $locale = $this->getOption($options, 'locale');

        return $this->formatter->formatCurrency($value, $currency, $attributes, $textAttributes, $symbols, $locale);
    }

    /**
     * Gets option or default value if option not exist
     *
     * @param array $options
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    protected function getOption(array $options, $name, $default = null)
    {
        return isset($options[$name]) ? $options[$name] : $default;
    }
}
