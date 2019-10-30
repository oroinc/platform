<?php

namespace Oro\Bundle\DigitalAssetBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Oro\Bundle\DigitalAssetBundle\Entity\Repository\DigitalAssetRepository;
use Oro\Bundle\DigitalAssetBundle\Tests\Functional\DataFixtures\LoadDigitalAssetData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class DigitalAssetRepositoryTest extends WebTestCase
{
    /** @var DigitalAssetRepository */
    private $repository;

    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures(
            [
                LoadDigitalAssetData::class,
            ]
        );

        $container = $this->getContainer();
        $this->repository = $container->get('doctrine')->getRepository(DigitalAsset::class);
    }

    public function testFindChildFilesByDigitalAssetId(): void
    {
        $digitalAsset = $this->getReference(LoadDigitalAssetData::DIGITAL_ASSET_1);
        $this->assertEquals(
            $digitalAsset->getChildFiles()->toArray(),
            $this->repository->findChildFilesByDigitalAssetId($digitalAsset->getId())
        );
    }

    public function testFindChildFilesByDigitalAssetIdWhenMissing(): void
    {
        $this->assertEquals([], $this->repository->findChildFilesByDigitalAssetId(99999));
    }

    public function testFindSourceFile(): void
    {
        /** @var DigitalAsset $digitalAsset */
        $digitalAsset = $this->getReference(LoadDigitalAssetData::DIGITAL_ASSET_1);

        $this->assertEquals(
            $digitalAsset->getSourceFile(),
            $this->repository->findSourceFile($digitalAsset->getId())
        );
    }
}
