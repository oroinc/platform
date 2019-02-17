<?php

namespace Oro\Bundle\LocaleBundle\Configuration;

/**
 * The provider for configuration that is loaded from the following files:
 * * Resources/config/oro/name_format.yml
 * * Resources/config/oro/address_format.yml
 * * Resources/config/oro/locale_data.yml
 */
class LocaleConfigurationProvider
{
    /** @var NameFormatConfigurationProvider */
    private $nameFormatConfigProvider;

    /** @var AddressFormatConfigurationProvider */
    private $addressFormatConfigProvider;

    /** @var LocaleDataConfigurationProvider */
    private $localeDataConfigProvider;

    /**
     * @param NameFormatConfigurationProvider    $nameFormatConfigProvider
     * @param AddressFormatConfigurationProvider $addressFormatConfigProvider
     * @param LocaleDataConfigurationProvider    $localeDataConfigProvider
     */
    public function __construct(
        NameFormatConfigurationProvider $nameFormatConfigProvider,
        AddressFormatConfigurationProvider $addressFormatConfigProvider,
        LocaleDataConfigurationProvider $localeDataConfigProvider
    ) {
        $this->nameFormatConfigProvider = $nameFormatConfigProvider;
        $this->addressFormatConfigProvider = $addressFormatConfigProvider;
        $this->localeDataConfigProvider = $localeDataConfigProvider;
    }

    /**
     * Gets name formats configuration.
     *
     * @return array
     */
    public function getNameFormats(): array
    {
        return $this->nameFormatConfigProvider->getConfiguration();
    }

    /**
     * Gets address formats configuration.
     *
     * @return array
     */
    public function getAddressFormats(): array
    {
        return $this->addressFormatConfigProvider->getConfiguration();
    }

    /**
     * Gets locale data configuration.
     *
     * @return array
     */
    public function getLocaleData(): array
    {
        return $this->localeDataConfigProvider->getConfiguration();
    }
}
