<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Functional\Manager\File;

use Oro\Bundle\AttachmentBundle\Manager\File\TemporaryFile;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class TemporaryFileTest extends WebTestCase
{
    /**
     * @var string
     */
    private $tmpFilePath;

    protected function setUp()
    {
        $this->initClient([], self::generateBasicAuthHeader());
    }

    protected function tearDown()
    {
        if (file_exists($this->tmpFilePath)) {
            unlink($this->tmpFilePath);
        }

        parent::tearDown();
    }

    public function testFileIsRemoved(): void
    {
        $cachePath = self::getContainer()->getParameter('kernel.cache_dir');
        $this->tmpFilePath = tempnam($cachePath, 'tmp');

        self::assertTrue(file_exists($this->tmpFilePath));

        $file = new TemporaryFile($this->tmpFilePath);
        $file = null;

        self::assertFalse(file_exists($this->tmpFilePath));
    }
}
