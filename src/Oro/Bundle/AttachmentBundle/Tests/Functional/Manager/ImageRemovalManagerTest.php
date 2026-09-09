<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Functional\Manager;

use Oro\Bundle\AttachmentBundle\Manager\MediaCacheManagerRegistryInterface;
use Oro\Bundle\AttachmentBundle\Tools\LegacyMediaCachePathHelper;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @dbIsolationPerTest
 */
class ImageRemovalManagerTest extends WebTestCase
{
    use ImageRemovalManagerTestingTrait;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
    }

    public function testRemoveFilesForAclProtectedImage(): void
    {
        $file = $this->createFileEntity();
        $file->setParentEntityClass(User::class);
        $file->setParentEntityId(
            $this->getEntityManager()
                ->getRepository(User::class)
                ->findOneBy(['email' => self::AUTH_USER])->getId()
        );
        $file->setParentEntityFieldName('avatar');
        $this->saveFileEntity($file);

        $this->applyImageFilter($file, 'avatar_med');
        $this->applyImageFilter($file, 'avatar_xsmall');

        $fileNames = $this->getImageFileNames($file);
        self::assertCount(4, $fileNames);

        $this->removeFiles($file);
        $this->assertFilesDoNotExist($file, $fileNames);
    }

    public function testRemoveFilesForNotAclProtectedImage(): void
    {
        $file = $this->createFileEntity();
        $this->saveFileEntity($file);

        $this->applyImageFilter($file, 'avatar_med');
        $this->applyImageFilter($file, 'avatar_xsmall');

        $fileNames = $this->getImageFileNames($file);
        self::assertCount(4, $fileNames);

        $this->removeFiles($file);
        $this->assertFilesDoNotExist($file, $fileNames);
    }

    public function testRemoveFilesForImageWithNonAsciiOriginalFilename(): void
    {
        $file = $this->createFileEntity();
        $file->setOriginalFilename('фото кафе.jpg');
        $this->saveFileEntity($file);

        $this->applyImageFilter($file, 'avatar_med');
        $this->applyImageFilter($file, 'avatar_xsmall');

        $fileNames = $this->getImageFileNames($file);
        self::assertCount(4, $fileNames);
        foreach ($fileNames as $fileName) {
            self::assertStringContainsString('фото-кафе', $fileName);
            self::assertStringNotContainsString('%', $fileName);
        }

        $this->removeFiles($file);
        $this->assertFilesDoNotExist($file, $fileNames);
    }

    public function testRemoveFilesForImageStoredUnderLegacyPath(): void
    {
        $file = $this->createFileEntity();
        $file->setOriginalFilename('фото кафе.jpg');
        $this->saveFileEntity($file);

        $this->applyImageFilter($file, 'avatar_med');

        $fileNames = $this->getImageFileNames($file);
        self::assertCount(2, $fileNames);

        // emulates an image stored under the legacy (percent-encoded) path
        $legacyFileName = LegacyMediaCachePathHelper::getLegacyPath($fileNames[0]);
        self::assertNotEquals($fileNames[0], $legacyFileName);
        /** @var MediaCacheManagerRegistryInterface $registry */
        $registry = self::getContainer()->get('oro_attachment.tests.media_cache_manager_registry');
        $registry->getManagerForFile($file)->writeToStorage('legacy-image', $legacyFileName);

        $fileNames = $this->getImageFileNames($file);
        self::assertCount(3, $fileNames);
        self::assertContains($legacyFileName, $fileNames);

        $this->removeFiles($file);
        $this->assertFilesDoNotExist($file, $fileNames);
    }
}
