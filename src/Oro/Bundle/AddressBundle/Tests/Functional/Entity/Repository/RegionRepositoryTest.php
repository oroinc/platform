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
    /** @var RegionRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->initClient();
        $this->loadFixtures([LoadCountryRegionData::class]);

        $this->repository = $this->getContainer()->get('doctrine')->getRepository(Region::class);
    }

    public function testGetCountryRegions()
    {
        /** @var Country $usa */
        $usa = $this->getReference(LoadCountryData::COUNTRY_USA);

        /** @var Region $usny */
        $usny = $this->getReference(LoadRegionData::REGION_US_NY);

        $this->assertRegionExists($usny, $this->repository->getCountryRegions($usa));
    }

    public function testGetCountryRegionsQueryBuilder()
    {
        /** @var Country $usa */
        $usa = $this->getReference(LoadCountryData::COUNTRY_USA);

        /** @var Region $usny */
        $usny = $this->getReference(LoadRegionData::REGION_US_NY);

        $this->assertRegionExists(
            $usny,
            $this->repository->getCountryRegionsQueryBuilder($usa)->getQuery()->getResult()
        );
    }

    public function testGetAllIdentities()
    {
        $result = $this->repository->getAllIdentities();

        $this->assertGreaterThanOrEqual(4601, count($result));

        /** @var Region $usny */
        $usny = $this->getReference(LoadRegionData::REGION_US_NY);
        /** @var Region $ado7 */
        $ado7 = $this->getReference(LoadRegionData::REGION_AD_07);
        /** @var Region $dk85 */
        $dk85 = $this->getReference(LoadRegionData::REGION_DK_85);

        $this->assertContains($usny->getCombinedCode(), $result);
        $this->assertContains($ado7->getCombinedCode(), $result);
        $this->assertContains($dk85->getCombinedCode(), $result);
    }

    /**
     * @param Region $expected
     * @param array $regions
     */
    private function assertRegionExists(Region $expected, array $regions)
    {
        $this->assertGreaterThanOrEqual(1, count($regions));

        $actual = null;
        foreach ($regions as $region) {
            if ($region->getCombinedCode() === $expected->getCombinedCode()) {
                $actual = $region;
                break;
            }
        }

        $this->assertNotNull($actual);
        $this->assertEquals($expected->getName(), $actual->getName());
    }

    public function testUpdateTranslations()
    {
        $this->repository->updateTranslations(
            [
                'US-FL' => 'Floride',
                'DE-HH' => 'Hambourg',
            ]
        );
        $this->repository->clear();

        $actual = $this->repository->findBy(['combinedCode' => ['US-FL', 'DE-HH']]);

        $this->assertCount(2, $actual);
        $this->assertTranslationExists('US-FL', 'Floride', $actual);
        $this->assertTranslationExists('DE-HH', 'Hambourg', $actual);
    }

    /**
     * @param string $expectedCode
     * @param string $expectedTranslation
     * @param array|Region[] $entities
     */
    private function assertTranslationExists(string $expectedCode, string $expectedTranslation, array $entities)
    {
        $actual = null;
        foreach ($entities as $entity) {
            if ($entity->getCombinedCode() === $expectedCode) {
                $actual = $entity;
                break;
            }
        }

        $this->assertNotNull($actual);
        $this->assertInstanceOf(Region::class, $actual);
        $this->assertEquals($expectedTranslation, $actual->getName());
    }
}
