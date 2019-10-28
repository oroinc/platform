<?php

namespace Oro\Bundle\DigitalAssetBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Oro\Bundle\DigitalAssetBundle\Tests\Functional\DataFixtures\LoadDigitalAssetData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class DigitalAssetRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures(
            [
                LoadDigitalAssetData::class,
            ]
        );
    }

    public function testFindChildFilesByDigitalAssetId(): void
    {
        $digitalAsset = $this->getReference(LoadDigitalAssetData::DIGITAL_ASSET_1);
        $doctrine = $this->getContainer()->get('doctrine');
        $this->assertEquals(
            $digitalAsset->getChildFiles()->toArray(),
            $doctrine->getRepository(DigitalAsset::class)->findChildFilesByDigitalAssetId($digitalAsset->getId())
        );
    }

    public function testFindChildFilesByDigitalAssetIdWhenMissing(): void
    {
        $doctrine = $this->getContainer()->get('doctrine');
        $this->assertEquals(
            [],
            $doctrine->getRepository(DigitalAsset::class)->findChildFilesByDigitalAssetId(99999)
        );
    }
}
