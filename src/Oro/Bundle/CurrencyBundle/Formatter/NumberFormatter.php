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
        $currencyCode = null,
        array $attributes = [],
        array $textAttributes = [],
        array $symbols = [],
        $locale = null
    ) {
        $result = parent::formatCurrency($value, $currencyCode, $attributes, $textAttributes, $symbols, $locale);

        if ($this->viewTypeProvider->getViewType() === ViewTypeProviderInterface::VIEW_TYPE_ISO_CODE) {
            $toCurrencySymbol = $this->localeSettings->getCurrencySymbolByCurrency($currencyCode);
            // Adds a space before currency ISO code, excludes case with duplication when space is already there.
            $result = trim(str_replace([$toCurrencySymbol, '  '], [$toCurrencySymbol . ' ', ' '], $result), ' ');
        }

        return $result;
    }
}
