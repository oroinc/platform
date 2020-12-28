<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Functional\Manager;

use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class FileManagerTest extends WebTestCase
{
    /** @var string */
    private $someFile;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
    }

    protected function tearDown(): void
    {
        unlink($this->someFile);
        parent::tearDown();
    }

    public function testTemporaryFileIsRemovedWhenEntityIsDestroyed(): void
    {
        $cachePath = self::getContainer()->getParameter('kernel.cache_dir');
        $this->someFile = tempnam($cachePath, 'tmp');

        /** @var FileManager $fileManager */
        $fileManager = self::getContainer()->get('oro_attachment.file_manager');

        $fileEntity = $fileManager->createFileEntity($this->someFile);
        $tmpFilePath = $fileEntity->getFile()->getPathname();

        self::assertFileExists($tmpFilePath);

        $fileEntity = null;

        self::assertFileDoesNotExist($tmpFilePath);
    }
}
