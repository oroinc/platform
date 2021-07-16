<?php

namespace Oro\Component\Testing\Unit;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\AddressBundle\Form\EventListener\AddressIdentifierSubscriber;
use Oro\Bundle\AddressBundle\Form\Type\AddressType;
use Oro\Bundle\AddressBundle\Form\Type\CountryType;
use Oro\Bundle\AddressBundle\Form\Type\RegionType;
use Oro\Bundle\FormBundle\Form\Extension\AdditionalAttrExtension;
use Oro\Bundle\FormBundle\Tests\Unit\Stub\StripTagsExtensionStub;
use Oro\Component\Testing\Unit\Form\EventListener\Stub\AddressCountryAndRegionSubscriberStub;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FormType;

/**
 * Base extension class for address form
 */
abstract class AddressFormExtensionTestCase extends FormIntegrationTestCase
{
    const COUNTRY_WITHOUT_REGION = 'US';
    const COUNTRY_WITH_REGION = 'RO';
    const REGION_WITH_COUNTRY = 'RO-MS';

    /**
     * @var Country
     */
    private $validCountry;

    /**
     * @var Country
     */
    private $noRegionsCountry;

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $countryType = new EntityType($this->getCountryChoices(), 'oro_country', ['configs' => []]);
        $regionType = new EntityType($this->getRegionChoices(), 'oro_region', ['configs' => []]);

        return [
            new PreloadedExtension(
                [
                    AddressType::class => new AddressType(
                        new AddressCountryAndRegionSubscriberStub(),
                        new AddressIdentifierSubscriber()
                    ),
                    CountryType::class => $countryType,
                    RegionType::class => $regionType
                ],
                [
                    FormType::class => [
                        new AdditionalAttrExtension(),
                        new StripTagsExtensionStub($this),
                    ],
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
