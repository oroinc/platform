<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Imagine\Loader;

use Gaufrette\Filesystem;
use Gaufrette\StreamWrapper;
use Oro\Bundle\AttachmentBundle\Imagine\Loader\Loader;
use Oro\Bundle\GaufretteBundle\Adapter\LocalAdapter;
use Oro\Component\Testing\TempDirExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

class LoaderTest extends TestCase
{
    use TempDirExtension;

    private const string TEST_FILE_SYSTEM_NAME = 'testFileSystem';

    private string $directory;
    private SymfonyFilesystem $fileSystem;

    #[\Override]
    protected function setUp(): void
    {
        $this->fileSystem = new SymfonyFilesystem();
        $this->directory = $this->getTempDir('Files');
        $filesystem = new Filesystem(new LocalAdapter($this->directory));
        StreamWrapper::getFilesystemMap()->set(self::TEST_FILE_SYSTEM_NAME, $filesystem);
        StreamWrapper::register();
    }

    #[\Override]
    protected function tearDown(): void
    {
        StreamWrapper::getFilesystemMap()->clear();
    }

    public function testCreateLoaderWithLocalFile(): void
    {
        $this->fileSystem->touch([$this->directory . '/image.jpg']);
        $loader = new Loader(sprintf('gaufrette://%s/%s', self::TEST_FILE_SYSTEM_NAME, 'image.jpg'), 'gaufrette');
        $this->assertTrue($loader->isLocalFile());
    }
}
