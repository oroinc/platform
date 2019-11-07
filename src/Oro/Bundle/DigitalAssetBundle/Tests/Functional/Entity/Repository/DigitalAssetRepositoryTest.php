<?php

namespace Oro\Bundle\DigitalAssetBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Oro\Bundle\DigitalAssetBundle\Entity\Repository\DigitalAssetRepository;
use Oro\Bundle\DigitalAssetBundle\Tests\Functional\DataFixtures\LoadDigitalAssetData;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class DigitalAssetRepositoryTest extends WebTestCase
{
    /** @var AclHelper */
    private $aclHelper;

    /** @var DigitalAssetRepository */
    private $repository;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadDigitalAssetData::class]);

        $container = $this->getContainer();
        $this->aclHelper = $container->get('oro_security.acl_helper');
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

    public function testFindForEntityField(): void
    {
        $this->assertEquals(
            [
                $this->getReference(LoadDigitalAssetData::DIGITAL_ASSET_1_CHILD_1),
                $this->getReference(LoadDigitalAssetData::DIGITAL_ASSET_2_CHILD_1),
            ],
            $this->repository->findForEntityField(\stdClass::class, 1, 'attachmentFieldA', $this->aclHelper)
        );
    }

    public function testFindBySourceFiles(): void
    {
        $digitalAsset1 = $this->getReference(LoadDigitalAssetData::DIGITAL_ASSET_1);
        $digitalAsset3 = $this->getReference(LoadDigitalAssetData::DIGITAL_ASSET_3);

        $this->assertEquals(
            [
                $digitalAsset1->getId() => $digitalAsset1,
                $digitalAsset3->getId() => $digitalAsset3,
            ],
            $this->repository->findByIds(
                [$digitalAsset1->getId(), $digitalAsset3->getId()],
                $this->aclHelper
            )
        );
    }
}
