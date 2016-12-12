<?php

namespace Oro\Bundle\CurrencyBundle\Formatter;

use Oro\Bundle\CurrencyBundle\Provider\ViewTypeProviderInterface;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter as BaseFormatter;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;

class NumberFormatter extends BaseFormatter
{
    /**
     * @var ViewTypeProviderInterface
     */
    protected $viewTypeProvider;

    /**
     * NumberFormatter constructor.
     * @param LocaleSettings $localeSettings
     * @param ViewTypeProviderInterface $viewTypeProvider
     */
    public function __construct(LocaleSettings $localeSettings, ViewTypeProviderInterface $viewTypeProvider)
    {
        parent::__construct($localeSettings);
        $this->viewTypeProvider = $viewTypeProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function formatCurrency(
        $value,
        $currency = null,
        array $attributes = [],
        array $textAttributes = [],
        array $symbols = [],
        $locale = null
    ) {
        if (!$currency) {
            $currency = $this->localeSettings->getCurrency();
        }

        if (!$locale) {
            $locale = $this->localeSettings->getLocaleWithRegion();
        }

        $formatter = $this->getFormatter($locale, \NumberFormatter::CURRENCY, $attributes, $textAttributes, $symbols);

        $currencyCode = $formatter->getTextAttribute(\NumberFormatter::CURRENCY_CODE);
        $currencySymbol = $formatter->getSymbol(\NumberFormatter::CURRENCY_SYMBOL);
        $currencyIntlSymbol = $formatter->getSymbol(\NumberFormatter::INTL_CURRENCY_SYMBOL);
        $localizedCurrencySymbol = $this->localeSettings->getCurrencySymbolByCurrency($currency);

        $replaceSymbols = array_filter(
            [$currency, $currencySymbol, $currencyIntlSymbol],
            function ($symbol) use ($localizedCurrencySymbol) {
                return $symbol !== $localizedCurrencySymbol;
            }
        );

        if ($this->viewTypeProvider->getViewType() === ViewTypeProviderInterface::VIEW_TYPE_ISO_CODE) {
            $localizedCurrencySymbol =
                $this->isCurrencySymbolPrepend($currencyCode, $locale) ?
                    sprintf('%s ', $localizedCurrencySymbol) : $localizedCurrencySymbol;
        }

        $formattedString = $formatter->formatCurrency(
            $value,
            $currencyCode
        );

        if (!empty($replaceSymbols)) {
            $localizedFormattedString = str_replace(
                $replaceSymbols,
                $localizedCurrencySymbol,
                $formattedString
            );

            return $localizedFormattedString;
        }

        return $formattedString;
    }
}
