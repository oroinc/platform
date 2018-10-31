<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Functional\Manager\File;

use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class FileManagerTest extends WebTestCase
{
    /**
     * @var string
     */
    private $someFile;

    protected function setUp()
    {
        $this->initClient([], self::generateBasicAuthHeader());
    }

    protected function tearDown()
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

        self::assertTrue(file_exists($tmpFilePath));

        $fileEntity = null;

        self::assertFalse(file_exists($tmpFilePath));
    }
}
