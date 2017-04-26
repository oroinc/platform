<?php

namespace Oro\Bundle\AddressBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\AddressBundle\Entity\Country;

class LoadCountryData extends AbstractFixture
{
    const COUNTRY_USA = 'country.usa';
    const COUNTRY_MEXICO = 'country.mexico';
    const COUNTRY_GERMANY = 'country.germany';

    /** @var array */
    protected $countries = [
        self::COUNTRY_USA => [
            'iso2Code' => 'US',
            'iso3Code' => 'USA',
            'name' => 'United States (DE)',
            'locale' => 'de'
        ],
        self::COUNTRY_MEXICO => [
            'iso2Code' => 'MX',
            'iso3Code' => 'MEX',
            'name' => 'Mexico (DE)',
            'locale' => 'de'
        ],
        self::COUNTRY_GERMANY => [
            'iso2Code' => 'DE',
            'iso3Code' => 'DEU',
            'name' => 'Germany (DE)',
            'locale' => 'de'
        ]
    ];

    /**
     * Load address types
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $repository = $manager->getRepository(Country::class);

        foreach ($this->countries as $reference => $data) {
            /** @var $country Country */
            $country = $repository->find($data['iso2Code']);
            if (!$country) {
                $country = new Country($data['iso2Code']);
                $country->setIso3Code($data['iso3Code']);
            }

            $country->setLocale($data['locale'])->setName($data['name']);

            $this->setReference($reference, $country);

            $manager->persist($country);
        }

        $manager->flush();
    }
}
