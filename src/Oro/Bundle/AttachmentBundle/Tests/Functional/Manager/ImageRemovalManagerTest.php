<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Functional\Manager;

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
}
