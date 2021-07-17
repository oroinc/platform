<?php

namespace Oro\Bundle\LocaleBundle\Configuration;

use Oro\Bundle\ConfigBundle\Provider\Value\ValueProviderInterface;

/**
 * The configuration value provider that is used to get default currency code for the specified country.
 * @see \Oro\Bundle\LocaleBundle\DependencyInjection\OroLocaleExtension::prepareSettings
 */
class DefaultCurrencyValueProvider implements ValueProviderInterface
{
    /** @var string */
    private $country;

    /** @var LocaleDataConfigurationProvider */
    private $localeDataConfigProvider;

    public function __construct(string $country, LocaleDataConfigurationProvider $localeDataConfigProvider)
    {
        $this->country = $country;
        $this->localeDataConfigProvider = $localeDataConfigProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue()
    {
        $localeData = $this->localeDataConfigProvider->getConfiguration();

        return $localeData[$this->country]['currency_code'] ?? null;
    }
}
