<?php

namespace Oro\Bundle\DigitalAssetBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Tests\Functional\DataFixtures\LoadFileData;
use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Oro\Bundle\DigitalAssetBundle\Entity\Repository\DigitalAssetRepository;
use Oro\Bundle\DigitalAssetBundle\Tests\Functional\DataFixtures\LoadDigitalAssetData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class DigitalAssetRepositoryTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadFileData::class, LoadDigitalAssetData::class]);
    }

    private function getRepository(): DigitalAssetRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(DigitalAsset::class);
    }

    private function getDigitalAsset(string $reference): DigitalAsset
    {
        return $this->getReference($reference);
    }

    private function getFile(string $reference): File
    {
        return $this->getReference($reference);
    }

    private function getFileIds(array $files): array
    {
        $ids = array_map(function (File $file) {
            return $file->getId();
        }, $files);
        sort($ids);

        return $ids;
    }

    public function testFindChildFilesByDigitalAssetId(): void
    {
        $digitalAsset = $this->getDigitalAsset(LoadDigitalAssetData::DIGITAL_ASSET_1);
        self::assertEquals(
            $digitalAsset->getChildFiles()->toArray(),
            $this->getRepository()->findChildFilesByDigitalAssetId($digitalAsset->getId())
        );
    }

    public function testFindChildFilesByDigitalAssetIdWhenMissing(): void
    {
        self::assertEquals([], $this->getRepository()->findChildFilesByDigitalAssetId(99999));
    }

    public function testFindSourceFile(): void
    {
        $digitalAsset = $this->getDigitalAsset(LoadDigitalAssetData::DIGITAL_ASSET_1);

        self::assertEquals(
            $digitalAsset->getSourceFile(),
            $this->getRepository()->findSourceFile($digitalAsset->getId())
        );
    }

    public function testFindForEntityField(): void
    {
        $foundFiles = $this->getRepository()->findForEntityField(\stdClass::class, 1, 'attachmentFieldA');
        $expectedFiles = [
            $this->getFile(LoadDigitalAssetData::DIGITAL_ASSET_1_CHILD_1),
            $this->getFile(LoadDigitalAssetData::DIGITAL_ASSET_2_CHILD_1),
        ];

        self::assertSame(
            $this->getFileIds($expectedFiles),
            $this->getFileIds($foundFiles)
        );
    }

    public function testFindByIds(): void
    {
        $digitalAsset1 = $this->getDigitalAsset(LoadDigitalAssetData::DIGITAL_ASSET_1);
        $digitalAsset3 = $this->getDigitalAsset(LoadDigitalAssetData::DIGITAL_ASSET_3);

        self::assertEquals(
            [
                $digitalAsset1->getId() => $digitalAsset1,
                $digitalAsset3->getId() => $digitalAsset3,
            ],
            $this->getRepository()->findByIds(
                [$digitalAsset1->getId(), $digitalAsset3->getId()],
                self::getContainer()->get('oro_security.acl_helper')
            )
        );
    }

    public function testGetFileDataForTwigTagWhenNotExists(): void
    {
        self::assertSame([], $this->getRepository()->getFileDataForTwigTag(999999));
    }

    /**
     * @dataProvider getFileDataForTwigTagDataProvider
     */
    public function testGetFileDataForTwigTag(string $referenceName): void
    {
        $file = $this->getFile($referenceName);
        self::assertEquals(
            [
                'uuid' => $file->getUuid(),
                'parentEntityClass' => $file->getParentEntityClass(),
                'parentEntityId' => $file->getParentEntityId(),
                'parentEntityFieldName' => $file->getParentEntityFieldName(),
                'digitalAssetId' => $file->getDigitalAsset()?->getId(),
                'extension' => $file->getExtension(),
            ],
            $this->getRepository()->getFileDataForTwigTag($file->getId())
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
