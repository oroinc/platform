<?php

namespace Oro\Bundle\AddressBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;

class LoadCountriesAndRegionsDataForUpdate extends AbstractFixture
{
    const COUNTRY_YY = 'country.YY';
    const COUNTRY_XX = 'country.XX';
    const COUNTRY_QQ = 'country.QQ';

    const REGION_YY_YY = 'region.YYYY';
    const REGION_XX_XX = 'region.XXXX';
    const REGION_QQ_QQ = 'region.QQQQ';

    /** @var array */
    private $countries = [
        self::COUNTRY_YY => [
            'iso2Code' => 'YY',
            'iso3Code' => 'YYY',
            'name' => 'SomeY',
            'locale' => 'en',
            'regions' => [

            ]
        ],
        self::COUNTRY_XX => [
            'iso2Code' => 'XX',
            'iso3Code' => 'XXX',
            'name' => 'SomeX',
            'locale' => 'en'
        ],
        self::COUNTRY_QQ => [
            'iso2Code' => 'QQ',
            'iso3Code' => 'QQQ',
            'name' => 'SomeQ',
            'locale' => 'de'
        ]
    ];

    /** @var array */
    private $regions = [
        self::REGION_YY_YY => [
            'combinedCode' => 'YY-YY',
            'code' => 'YY',
            'name' => 'Region YY',
        ],
        self::REGION_XX_XX => [
            'combinedCode' => 'XX-XX',
            'code' => 'XX',
            'name' => 'Region XX',
        ],
        self::REGION_QQ_QQ => [
            'combinedCode' => 'QQ-QQ',
            'code' => 'QQ',
            'name' => 'Region QQ',
        ],
    ];
    /**
     * Load address types
     */
    #[\Override]
    public function load(ObjectManager $manager)
    {
        $repository = $manager->getRepository(Country::class);

        foreach ($this->countries as $reference => $data) {
            /** @var Country $country */
            $country = $repository->find($data['iso2Code']);
            if (!$country) {
                $country = new Country($data['iso2Code']);

                $regionKey = sprintf('%s.%s%s', 'region', $data['iso2Code'], $data['iso2Code']);
                $region = new Region($this->regions[$regionKey]['combinedCode']);
                $region->setName($this->regions[$regionKey]['name'])
                    ->setCode($this->regions[$regionKey]['code'])
                    ->setCountry($country);

                $country->setIso3Code($data['iso3Code']);
                $country->addRegion($region);
            }

            $country->setLocale($data['locale'])->setName($data['name']);

            $this->setReference($reference, $country);

            $manager->persist($country);
        }

        $manager->flush();
    }
}
