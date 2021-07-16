<?php

namespace Oro\Bundle\DigitalAssetBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\AttachmentBundle\Tests\Functional\DataFixtures\LoadFileData;
use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Oro\Bundle\DigitalAssetBundle\Entity\Repository\DigitalAssetRepository;
use Oro\Bundle\DigitalAssetBundle\Tests\Functional\DataFixtures\LoadDigitalAssetData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class DigitalAssetRepositoryTest extends WebTestCase
{
    /** @var DigitalAssetRepository */
    private $repository;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadFileData::class, LoadDigitalAssetData::class]);

        $this->repository = $this->getContainer()->get('doctrine')
            ->getRepository(DigitalAsset::class);
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
            $this->repository->findForEntityField(\stdClass::class, 1, 'attachmentFieldA')
        );
    }

    public function testFindByIds(): void
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
                $this->getContainer()->get('oro_security.acl_helper')
            )
        );
    }

    public function testGetFileDataForTwigTagWhenNotExists(): void
    {
        $this->assertSame([], $this->repository->getFileDataForTwigTag(999999));
    }

    /**
     * @dataProvider getFileDataForTwigTagDataProvider
     */
    public function testGetFileDataForTwigTag(string $referenceName): void
    {
        $file = $this->getReference($referenceName);
        $this->assertEquals(
            [
                'uuid' => $file->getUuid(),
                'parentEntityClass' => $file->getParentEntityClass(),
                'parentEntityId' => $file->getParentEntityId(),
                'digitalAssetId' => $file->getDigitalAsset() ? $file->getDigitalAsset()->getId() : null,
            ],
            $this->repository->getFileDataForTwigTag($file->getId())
        );
    }

    public function getFileDataForTwigTagDataProvider(): array
    {
        return [
            'file is a digital asset source file' => [
                'referenceName' => LoadDigitalAssetData::DIGITAL_ASSET_1_SOURCE,
            ],
            'file is a digital asset child file' => [
                'referenceName' => LoadDigitalAssetData::DIGITAL_ASSET_1_CHILD_1,
            ],
            'file is a regular file' => [
                'referenceName' => LoadFileData::FILE_1,
            ],
        ];
    }
}
