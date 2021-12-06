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

    public function testGetCountries()
    {
        /** @var Country[] $expected */
        $expected = [
            $this->getReference(LoadCountryData::COUNTRY_USA),
            $this->getReference(LoadCountryData::COUNTRY_MEXICO),
            $this->getReference(LoadCountryData::COUNTRY_GERMANY)
        ];

        $countries = $this->getRepository()->getCountries();
        $this->assertGreaterThanOrEqual(3, count($countries));

        foreach ($expected as $expectedCountry) {
            foreach ($countries as $country) {
                if ($country->getIso2Code() === $expectedCountry->getIso2Code()) {
                    $this->assertEquals($expectedCountry->getName(), $country->getName());
                }
            }
        }
    }

    public function testGetAllCountryNamesArray()
    {
        /** @var Country $testCountry */
        $testCountry = $this->getReference(LoadCountryData::COUNTRY_MEXICO);
        $countries = $this->getRepository()->getAllCountryNamesArray();
        $this->assertContains(
            [
                'iso2Code' => $testCountry->getIso2Code(),
                'iso3Code' => $testCountry->getIso3Code(),
                'name' => $testCountry->getName(),
            ],
            $countries
        );
    }

    public function testGetAllIdentities()
    {
        $result = $this->getRepository()->getAllIdentities();

        $this->assertGreaterThanOrEqual(3, count($result));

        /** @var Country $usa */
        $usa = $this->getReference(LoadCountryData::COUNTRY_USA);
        /** @var Country $mexico */
        $mexico = $this->getReference(LoadCountryData::COUNTRY_MEXICO);
        /** @var Country $germany */
        $germany = $this->getReference(LoadCountryData::COUNTRY_GERMANY);

        $this->assertContains($usa->getIso2Code(), $result);
        $this->assertContains($mexico->getIso2Code(), $result);
        $this->assertContains($germany->getIso2Code(), $result);
    }

    public function testUpdateTranslations()
    {
        $this->getRepository()->updateTranslations(
            [
                'US' => 'États Unis',
                'DE' => 'Allemagne',
            ]
        );
        $this->getRepository()->clear();

        $actual = $this->getRepository()->findBy(['iso2Code' => ['US', 'DE']]);

        $this->assertCount(2, $actual);
        $this->assertTranslationExists('US', 'États Unis', $actual);
        $this->assertTranslationExists('DE', 'Allemagne', $actual);
    }

    private function assertTranslationExists(string $expectedCode, string $expectedTranslation, array $entities): void
    {
        $actual = null;
        /** @var Country $entity */
        foreach ($entities as $entity) {
            if ($entity->getIso2Code() === $expectedCode) {
                $actual = $entity;
                break;
            }
        }

        $this->assertNotNull($actual);
        $this->assertInstanceOf(Country::class, $actual);
        $this->assertEquals($expectedTranslation, $actual->getName());
    }
}
