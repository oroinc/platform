<?php

namespace Oro\Bundle\AddressBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\AddressBundle\Entity\Repository\RegionRepository;
use Oro\Bundle\AddressBundle\Tests\Functional\DataFixtures\LoadCountryData;
use Oro\Bundle\AddressBundle\Tests\Functional\DataFixtures\LoadCountryRegionData;
use Oro\Bundle\AddressBundle\Tests\Functional\DataFixtures\LoadRegionData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class RegionRepositoryTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadCountryRegionData::class]);
    }

    private function getRepository(): RegionRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(Region::class);
    }

    private function assertRegionExists(Region $expected, array $regions): void
    {
        /** @var Region|null $actual */
        $actual = null;
        /** @var Region $region */
        foreach ($regions as $region) {
            if ($region->getCombinedCode() === $expected->getCombinedCode()) {
                $actual = $region;
                break;
            }
        }

        $this->assertNotNull($actual);
        $this->assertEquals($expected->getName(), $actual->getName());
    }

    private function assertRegionNotExist(Region $expected, array $regions): void
    {
        $actual  = array_filter(
            $regions,
            fn (Region $region): bool => $region->getCombinedCode() === $expected->getCombinedCode()
        );

        self::assertEmpty($actual);
    }

    public function testGetCountryRegions(): void
    {
        /** @var Country $usa */
        $usa = $this->getReference(LoadCountryData::COUNTRY_USA);
        /** @var Region $usny */
        $usny = $this->getReference(LoadRegionData::REGION_US_NY);

        $regions = $this->getRepository()->getCountryRegions($usa);
        $this->assertGreaterThanOrEqual(1, count($regions));
        $this->assertRegionExists($usny, $regions);
    }

    public function testGetCountryRegionsQueryBuilder(): void
    {
        /** @var Country $usa */
        $usa = $this->getReference(LoadCountryData::COUNTRY_USA);
        /** @var Region $usny */
        $usny = $this->getReference(LoadRegionData::REGION_US_NY);
        $usxx = $this->getReference(LoadCountryRegionData::REGION_US_XX);

        $regions = $this->getRepository()->getCountryRegionsQueryBuilder($usa)->getQuery()->getResult();

        $this->assertRegionNotExist($usxx, $regions);
        $this->assertGreaterThanOrEqual(1, count($regions));
        $this->assertRegionExists($usny, $regions);
    }
}
