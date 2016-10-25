<?php

namespace Oro\Bundle\CurrencyBundle\Formatter;

use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter as BaseFormatter;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\CurrencyBundle\Config\CurrencyConfigManager;

class NumberFormatter extends BaseFormatter
{
    protected $currencyConfigManager;

    /**
     * NumberFormatter constructor.
     *
     * @param LocaleSettings        $localeSettings
     * @param CurrencyConfigManager $currencyConfigManager
     */
    public function __construct(LocaleSettings $localeSettings, CurrencyConfigManager $currencyConfigManager)
    {
        parent::__construct($localeSettings);
        $this->currencyConfigManager = $currencyConfigManager;
    }

    /**
     * {@inheritdoc}
     */
    public function formatCurrency(
        $value,
        $currency = null,
        array $attributes = array(),
        array $textAttributes = array(),
        array $symbols = array(),
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

        if ($this->currencyConfigManager->getViewType() === CurrencyConfigManager::VIEW_TYPE_ISO_CODE) {
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
