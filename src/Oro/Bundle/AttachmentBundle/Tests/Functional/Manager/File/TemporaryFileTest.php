<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Functional\Manager\File;

use Oro\Bundle\AttachmentBundle\Manager\File\TemporaryFile;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\TempDirExtension;

class TemporaryFileTest extends WebTestCase
{
    use TempDirExtension;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
    }

    public function testFileIsRemoved(): void
    {
        $filePath = $this->getTempFile('attachment_temporary_file');
        touch($filePath);

        self::assertFileExists($filePath);

        $file = new TemporaryFile($filePath);
        $file = null;

        self::assertFileDoesNotExist($filePath);
    }
}
