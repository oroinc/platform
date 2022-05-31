<?php

namespace Oro\Bundle\AddressBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Repository\CountryRepository;
use Oro\Bundle\AddressBundle\Tests\Functional\DataFixtures\LoadCountryData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CountryRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadCountryData::class]);
    }

    private function getRepository(): CountryRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(Country::class);
    }

    private function assertCountryExists(Country $expected, array $countries): void
    {
        /** @var Country|null $actual */
        $actual = null;
        /** @var Country $country */
        foreach ($countries as $country) {
            if ($country->getIso2Code() === $expected->getIso2Code()) {
                $actual = $country;
                break;
            }
        }

        $this->assertNotNull($actual);
        $this->assertEquals($expected->getName(), $actual->getName());
    }

    public function testGetCountries(): void
    {
        /** @var Country[] $expected */
        $expected = [
            $this->getReference(LoadCountryData::COUNTRY_USA),
            $this->getReference(LoadCountryData::COUNTRY_MEXICO),
            $this->getReference(LoadCountryData::COUNTRY_GERMANY)
        ];

        $countries = $this->getRepository()->getCountries();
        $this->assertGreaterThanOrEqual(count($expected), count($countries));

        foreach ($expected as $expectedCountry) {
            $this->assertCountryExists($expectedCountry, $countries);
        }
    }
}
