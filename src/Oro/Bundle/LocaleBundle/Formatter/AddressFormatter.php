<?php

namespace Oro\Bundle\LocaleBundle\Formatter;

use Oro\Bundle\AddressBundle\Provider\PhoneProvider;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration as LocaleConfiguration;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Encapsulates methods for address formatting.
 */
class AddressFormatter
{
    /**
     * @var LocaleSettings
     */
    protected $localeSettings;

    /**
     * @var NameFormatter
     */
    protected $nameFormatter;

    /**
     * @var PropertyAccessorInterface
     */
    protected $propertyAccessor;

    /**
     * @var PhoneProvider
     */
    protected $phoneProvider;

    public function __construct(
        LocaleSettings $localeSettings,
        NameFormatter $nameFormatter,
        PropertyAccessorInterface $propertyAccessor
    ) {
        $this->localeSettings = $localeSettings;
        $this->nameFormatter = $nameFormatter;
        $this->propertyAccessor = $propertyAccessor;
    }

    public function setPhoneProvider(PhoneProvider $phoneProvider)
    {
        $this->phoneProvider = $phoneProvider;
    }

    /**
     * Format address
     *
     * @param AddressInterface $address
     * @param null|string $country
     * @param string $newLineSeparator
     * @return string
     */
    public function format(AddressInterface $address, $country = null, $newLineSeparator = "\n")
    {
        if (!$country) {
            $country = $this->getCountry($address);
        }

        $format = $this->getAddressFormat($country);
        $parts = $this->getAddressParts($address, $format, $country);
        $formatted = str_replace(array_keys($parts), array_values($parts), $format);

        $formatted = str_replace(
            $newLineSeparator . $newLineSeparator,
            $newLineSeparator,
            str_replace('\n', $newLineSeparator, $formatted)
        );
        $formatted = trim($formatted, $newLineSeparator);
        $formatted = preg_replace('/ +/', ' ', $formatted);
        $formatted = preg_replace('/ +\n/', "\n", $formatted);

        return trim($formatted);
    }

    /**
     * Provides address parts according for the address format corresponding to the locale settings.
     * Look into Resources/config/oro/address_format.yml for available formats.
     *
     * @param AddressInterface $address
     * @param string $addressFormat The address format to get parts for.
     * @param null|string $country
     * @return array
     *  [
     *      // '%part_name%' => 'part value',
     *      '%country%' => 'US'
     *  ]
     */
    public function getAddressParts(AddressInterface $address, string $addressFormat, ?string $country = null): array
    {
        if (!$country) {
            $country = $this->getCountry($address);
        }

        $countryLocale = $this->localeSettings->getLocaleByCountry($country);
        preg_match_all('/%(\w+)%/', $addressFormat, $partsKeys);
        $parts = [];
        $partsMap = [
            'street1' => 'street',
            'country' => 'countryName',
            'region' => 'regionName',
        ];
        foreach ($partsKeys[1] ?? [] as $i => $partKey) {
            $lowercasePartKey = strtolower($partKey);
            if ('name' === $lowercasePartKey) {
                $value = $this->nameFormatter->format($address, $countryLocale);
            } elseif ('street' === $lowercasePartKey) {
                $value = trim($this->getValue($address, 'street') . ' ' . $this->getValue($address, 'street2'));
            } elseif ('phone' === $lowercasePartKey && $this->phoneProvider) {
                $value = $this->phoneProvider->getPhoneNumber($address);
            } elseif ('region_code' === $lowercasePartKey) {
                $value = $this->getRegionCode($address);
            } else {
                $value = $this->getValue($address, $partsMap[$lowercasePartKey] ?? $lowercasePartKey);
            }

            if ($partKey !== $lowercasePartKey) {
                $value = strtoupper((string)$value);
            }

            $parts[$partsKeys[0][$i]] = trim((string)$value);
        }

        return $parts;
    }

    private function getRegionCode(AddressInterface $address): string
    {
        return (string) ($this->getValue($address, 'regionCode') ?: $this->getValue($address, 'regionName'));
    }

    /**
     * Get address format based on locale or region, if argument is not passed locale from
     * system configuration will be used.
     *
     * @param string|null $localeOrRegion
     * @throws \RuntimeException
     */
    public function getAddressFormat($localeOrRegion = null)
    {
        if (!$localeOrRegion) {
            $localeOrRegion = $this->localeSettings->getLocale();
        }

        $addressFormats = $this->localeSettings->getAddressFormats();

        // matched by country (for example - "RU")
        if (isset($addressFormats[$localeOrRegion][LocaleSettings::ADDRESS_FORMAT_KEY])) {
            return $addressFormats[$localeOrRegion][LocaleSettings::ADDRESS_FORMAT_KEY];
        }

        // matched by locale region - "CA"
        $localeParts = \Locale::parseLocale($localeOrRegion);
        if (isset($localeParts[\Locale::REGION_TAG])) {
            $match = $localeParts[\Locale::REGION_TAG];
            if (isset($match, $addressFormats[$match][LocaleSettings::ADDRESS_FORMAT_KEY])) {
                return $addressFormats[$match][LocaleSettings::ADDRESS_FORMAT_KEY];
            }
        }

        // match by default country in system configuration settings
        $match = $this->localeSettings->getCountry();
        if ($match !== $localeOrRegion && isset($addressFormats[$match][LocaleSettings::ADDRESS_FORMAT_KEY])) {
            return $addressFormats[$match][LocaleSettings::ADDRESS_FORMAT_KEY];
        }

        // fallback to default country
        $match = LocaleConfiguration::DEFAULT_COUNTRY;
        if (isset($addressFormats[$match][LocaleSettings::ADDRESS_FORMAT_KEY])) {
            return $addressFormats[$match][LocaleSettings::ADDRESS_FORMAT_KEY];
        }

        throw new \RuntimeException(sprintf('Cannot get address format for "%s"', $localeOrRegion));
    }

    /**
     * @param object $obj
     * @param string $property
     * @return mixed|null
     */
    protected function getValue($obj, $property)
    {
        try {
            $value = $this->propertyAccessor->getValue($obj, $property);
        } catch (NoSuchPropertyException $e) {
            $value = null;
        }
        return $value;
    }

    /**
     * Provides country name or country ISO2 code for the given $address depending on locale settings.
     *
     * @param AddressInterface $address
     *
     * @return string Country name, ISO2 code or default country ISO2 code if country was not fetched from $address.
     */
    public function getCountry(AddressInterface $address)
    {
        $country = null;
        if ($this->localeSettings->isFormatAddressByAddressCountry()) {
            $country = $address->getCountryIso2();
        } else {
            $country = $this->localeSettings->getCountry();
        }
        if (!$country) {
            $country = LocaleConfiguration::DEFAULT_COUNTRY;
        }

        return $country;
    }
}
