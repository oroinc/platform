<?php

namespace Oro\Bundle\AddressBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Repository\CountryRepository;
use Oro\Bundle\AddressBundle\Tests\Functional\DataFixtures\LoadCountryData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CountryRepositoryTest extends WebTestCase
{
    /** @var CountryRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->initClient();
        $this->loadFixtures([LoadCountryData::class]);

        $this->repository = $this->getContainer()->get('doctrine')->getRepository(Country::class);
    }

    public function testGetCountries()
    {
        /** @var Country[] $expected */
        $expected = [
            $this->getReference(LoadCountryData::COUNTRY_USA),
            $this->getReference(LoadCountryData::COUNTRY_MEXICO),
            $this->getReference(LoadCountryData::COUNTRY_GERMANY)
        ];

        $countries = $this->repository->getCountries();
        $this->assertGreaterThanOrEqual(3, count($countries));

        foreach ($expected as $expectedCountry) {
            foreach ($countries as $country) {
                if ($country->getIso2Code() === $expectedCountry->getIso2Code()) {
                    $this->assertEquals($expectedCountry->getName(), $country->getName());
                }
            }
        }
    }
}
