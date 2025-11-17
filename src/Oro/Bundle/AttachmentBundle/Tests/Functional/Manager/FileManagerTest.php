<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Functional\Manager;

use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\TempDirExtension;

class FileManagerTest extends WebTestCase
{
    use TempDirExtension;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
    }

    public function testTemporaryFileIsRemovedWhenEntityIsDestroyed(): void
    {
        /** @var FileManager $fileManager */
        $fileManager = self::getContainer()->get('oro_attachment.file_manager');

        $filePath = $this->getTempFile('attachment_file_manager');
        touch($filePath);
        $fileEntity = $fileManager->createFileEntity($filePath);
        $tmpFilePath = $fileEntity->getFile()->getPathname();

        self::assertFileExists($tmpFilePath);

        $fileEntity = null;

        self::assertFileDoesNotExist($tmpFilePath);
    }
}
