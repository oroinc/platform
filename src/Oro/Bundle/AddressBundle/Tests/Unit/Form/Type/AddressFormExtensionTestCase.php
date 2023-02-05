<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\AddressBundle\Form\EventListener\AddressIdentifierSubscriber;
use Oro\Bundle\AddressBundle\Form\Type\AddressType;
use Oro\Bundle\AddressBundle\Form\Type\CountryType;
use Oro\Bundle\AddressBundle\Form\Type\RegionType;
use Oro\Bundle\AddressBundle\Tests\Unit\Form\EventListener\Stub\AddressCountryAndRegionSubscriberStub;
use Oro\Bundle\FormBundle\Form\Extension\AdditionalAttrExtension;
use Oro\Bundle\FormBundle\Tests\Unit\Stub\StripTagsExtensionStub;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;

/**
 * Base class for writing unit tests for address forms.
 */
abstract class AddressFormExtensionTestCase extends FormIntegrationTestCase
{
    protected const COUNTRY_WITHOUT_REGION = 'US';
    protected const COUNTRY_WITH_REGION = 'RO';
    protected const REGION_WITH_COUNTRY = 'RO-MS';

    private ?Country $validCountry = null;
    private ?Country $noRegionsCountry = null;

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    AddressType::class => new AddressType(
                        new AddressCountryAndRegionSubscriberStub(),
                        new AddressIdentifierSubscriber()
                    ),
                    CountryType::class => new EntityTypeStub($this->getCountryChoices(), ['configs' => []]),
                    RegionType::class => new EntityTypeStub($this->getRegionChoices(), ['configs' => []])
                ],
                [
                    FormType::class => [
                        new AdditionalAttrExtension(),
                        new StripTagsExtensionStub($this),
                    ]
                ]
            )
        ];
    }

    protected function getCountryChoices(): array
    {
        $countryAndRegion = $this->getValidCountryAndRegion();
        $country = reset($countryAndRegion);

        return [
            'CA' => $this->getCountryWithoutRegions(),
            self::COUNTRY_WITH_REGION => $country,
            self::COUNTRY_WITHOUT_REGION => new Country(self::COUNTRY_WITHOUT_REGION)
        ];
    }

    protected function getRegionChoices(): array
    {
        $countryAndRegion = $this->getValidCountryAndRegion();
        $region = end($countryAndRegion);

        return [
            self::REGION_WITH_COUNTRY => $region,
            'CA-QC' => (new Region('CA-QC'))->setCountry($this->getCountryWithoutRegions()),
        ];
    }

    protected function getValidCountryAndRegion(): array
    {
        if (!$this->validCountry) {
            $this->validCountry = new Country(self::COUNTRY_WITH_REGION);
            $region = new Region(self::REGION_WITH_COUNTRY);
            $region->setCountry($this->validCountry);
            $this->validCountry->addRegion($region);
        }

        return [$this->validCountry, $this->validCountry->getRegions()->first()];
    }

    private function getCountryWithoutRegions(): Country
    {
        if (!$this->noRegionsCountry) {
            $this->noRegionsCountry = new Country('CA');
        }

        return $this->noRegionsCountry;
    }
}
