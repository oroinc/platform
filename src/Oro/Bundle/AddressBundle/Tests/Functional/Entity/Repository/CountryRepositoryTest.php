<?php

namespace Oro\Bundle\AddressBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Repository\CountryRepository;
use Oro\Bundle\AddressBundle\Tests\Functional\DataFixtures\LoadCountryData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class CountryRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([
            LoadCountryData::class,
        ]);
    }

    public function testGetAllCountryNamesArray()
    {
        $repository = $this->getContainer()->get('doctrine')->getRepository(Country::class);
        $this->assertInstanceOf(CountryRepository::class, $repository);

        /** @var Country $testCountry */
        $testCountry = $this->getReference(LoadCountryData::TEST_COUNTRY);
        $countries = $repository->getAllCountryNamesArray();
        $this->assertContains(
            [
                'iso2Code' => $testCountry->getIso2Code(),
                'iso3Code' => $testCountry->getIso3Code(),
                'name' => $testCountry->getName(),
            ],
            $countries
        );
    }
}
