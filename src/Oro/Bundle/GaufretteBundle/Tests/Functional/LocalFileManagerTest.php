<?php

namespace Oro\Bundle\GaufretteBundle\Tests\Functional;

use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class LocalFileManagerTest extends WebTestCase
{
    /** @var string */
    private $directory;

    /** @var Filesystem */
    private $fileSystem;

    /** @var Finder */
    private $fileFinder;

    /** @var FileManager */
    private $fileManager;

    protected function setUp(): void
    {
        $this->initClient();

        $this->directory = self::getContainer()->getParameter('oro_gaufrette.tests.test_local_adapter_directory');
        $this->fileSystem = new Filesystem();
        $this->clearStorage();
        $this->fileSystem->mkdir($this->directory);

        $this->fileFinder = Finder::create()->in($this->directory);

        $this->fileManager = self::getContainer()->get('oro_gaufrette.tests.local_file_manager');
    }

    protected function tearDown(): void
    {
        $this->clearStorage();
        parent::tearDown();
    }

    private function clearStorage(): void
    {
        if ($this->fileSystem->exists($this->directory)) {
            $this->fileSystem->remove($this->directory);
        }
    }

    /**
     * @return string[]
     */
    private function getFiles(): array
    {
        $files = [];
        foreach ($this->fileFinder as $file) {
            $files[] = str_replace('\\', '/', substr($file->getPathname(), strlen($this->directory) + 1));
        }
        sort($files);

        return $files;
    }

    /**
     * @param string $prefix
     *
     * @return string[]
     */
    private function fileManagerFindFiles(string $prefix = ''): array
    {
        $files = $this->fileManager->findFiles($prefix);
        sort($files);

        return $files;
    }

    public function testGetAdapterDescription()
    {
        self::assertEquals($this->directory, $this->fileManager->getAdapterDescription());
    }

    public function testFindFilesForEmptyStorage()
    {
        self::assertSame([], $this->fileManagerFindFiles());
    }

    public function testFindFilesForNotEmptyStorage()
    {
        $this->fileSystem->mkdir($this->directory . '/dir1');
        $this->fileSystem->touch($this->directory . '/file1.txt');
        $this->fileSystem->touch($this->directory . '/dir1_file.txt');
        $this->fileSystem->touch($this->directory . '/dir1/file2.txt');
        $this->fileSystem->touch($this->directory . '/dir1/test1.txt');

        self::assertSame(
            ['dir1/file2.txt', 'dir1/test1.txt', 'dir1_file.txt', 'file1.txt'],
            $this->fileManagerFindFiles()
        );
        self::assertSame(
            ['dir1/file2.txt', 'dir1/test1.txt', 'dir1_file.txt', 'file1.txt'],
            $this->fileManagerFindFiles('/')
        );

        self::assertSame(
            ['dir1/file2.txt', 'dir1/test1.txt', 'dir1_file.txt'],
            $this->fileManagerFindFiles('dir1')
        );
        self::assertSame(
            ['dir1/file2.txt', 'dir1/test1.txt', 'dir1_file.txt'],
            $this->fileManagerFindFiles('/dir1')
        );
        self::assertSame(
            ['dir1/file2.txt', 'dir1/test1.txt'],
            $this->fileManagerFindFiles('dir1/')
        );
        self::assertSame(
            ['dir1/file2.txt', 'dir1/test1.txt'],
            $this->fileManagerFindFiles('/dir1/')
        );
        self::assertSame(
            ['dir1/file2.txt'],
            $this->fileManagerFindFiles('dir1/file')
        );
    }

    public function testDeleteAllFiles()
    {
        $this->fileSystem->mkdir($this->directory . '/dir1');
        $this->fileSystem->touch($this->directory . '/file1.txt');
        $this->fileSystem->touch($this->directory . '/dir1/file2.txt');
        $this->fileSystem->mkdir($this->directory . '/dir1/dir2');
        $this->fileSystem->touch($this->directory . '/dir1/dir2/file3.txt');

        $this->fileManager->deleteAllFiles();

        self::assertSame([], $this->getFiles());
    }

    public function testDeleteAllFilesBySlashPrefix()
    {
        $this->fileSystem->mkdir($this->directory . '/dir1');
        $this->fileSystem->touch($this->directory . '/file1.txt');
        $this->fileSystem->touch($this->directory . '/dir1_file.txt');
        $this->fileSystem->touch($this->directory . '/dir1/file2.txt');
        $this->fileSystem->mkdir($this->directory . '/dir1/dir2');
        $this->fileSystem->touch($this->directory . '/dir1/dir2/file3.txt');

        $this->fileManager->deleteAllFiles('/');

        self::assertSame([], $this->getFiles());
    }

    public function testDeleteAllFilesByPrefix()
    {
        $this->fileSystem->mkdir($this->directory . '/dir1');
        $this->fileSystem->touch($this->directory . '/file1.txt');
        $this->fileSystem->touch($this->directory . '/dir1_file.txt');
        $this->fileSystem->touch($this->directory . '/dir1/file2.txt');
        $this->fileSystem->mkdir($this->directory . '/dir1/dir2');
        $this->fileSystem->touch($this->directory . '/dir1/dir2/file3.txt');

        $this->fileManager->deleteAllFiles('dir1');

        self::assertSame(['file1.txt'], $this->getFiles());
    }

    public function testDeleteAllFilesByPrefixWithLeadingSlash()
    {
        $this->fileSystem->mkdir($this->directory . '/dir1');
        $this->fileSystem->touch($this->directory . '/file1.txt');
        $this->fileSystem->touch($this->directory . '/dir1_file.txt');
        $this->fileSystem->touch($this->directory . '/dir1/file2.txt');
        $this->fileSystem->mkdir($this->directory . '/dir1/dir2');
        $this->fileSystem->touch($this->directory . '/dir1/dir2/file3.txt');

        $this->fileManager->deleteAllFiles('/dir1');

        self::assertSame(['file1.txt'], $this->getFiles());
    }

    public function testDeleteAllFilesByPrefixWithTailingSlash()
    {
        $this->fileSystem->mkdir($this->directory . '/dir1');
        $this->fileSystem->touch($this->directory . '/file1.txt');
        $this->fileSystem->touch($this->directory . '/dir1_file.txt');
        $this->fileSystem->touch($this->directory . '/dir1/file2.txt');
        $this->fileSystem->mkdir($this->directory . '/dir1/dir2');
        $this->fileSystem->touch($this->directory . '/dir1/dir2/file3.txt');

        $this->fileManager->deleteAllFiles('dir1/');

        self::assertSame(['dir1_file.txt', 'file1.txt'], $this->getFiles());
    }

    public function testDeleteAllFilesByPrefixWithTwoTailingSlashes()
    {
        $this->fileSystem->mkdir($this->directory . '/dir1');
        $this->fileSystem->touch($this->directory . '/file1.txt');
        $this->fileSystem->touch($this->directory . '/dir1_file.txt');
        $this->fileSystem->touch($this->directory . '/dir1/file2.txt');
        $this->fileSystem->mkdir($this->directory . '/dir1/dir2');
        $this->fileSystem->touch($this->directory . '/dir1/dir2/file3.txt');

        $this->fileManager->deleteAllFiles('dir1//');

        self::assertSame(['dir1_file.txt', 'file1.txt'], $this->getFiles());
    }

    public function testDeleteFile()
    {
        $this->fileSystem->mkdir($this->directory . '/dir1');
        $this->fileSystem->touch($this->directory . '/file1.txt');
        $this->fileSystem->touch($this->directory . '/dir1/file2.txt');

        $this->fileManager->deleteFile('file1.txt');

        self::assertSame(['dir1', 'dir1/file2.txt'], $this->getFiles());
    }

    public function testDeleteFileFromSubDirAndNoOtherFiles()
    {
        $this->fileSystem->mkdir($this->directory . '/dir1');
        $this->fileSystem->touch($this->directory . '/file1.txt');
        $this->fileSystem->touch($this->directory . '/dir1_file.txt');
        $this->fileSystem->touch($this->directory . '/dir1/file2.txt');

        $this->fileManager->deleteFile('dir1/file2.txt');

        self::assertSame(['dir1_file.txt', 'file1.txt'], $this->getFiles());
    }

    public function testDeleteFileFromSubDirAndThereAreOtherFiles()
    {
        $this->fileSystem->mkdir($this->directory . '/dir1');
        $this->fileSystem->touch($this->directory . '/file1.txt');
        $this->fileSystem->touch($this->directory . '/dir1_file.txt');
        $this->fileSystem->touch($this->directory . '/dir1/file2.txt');
        $this->fileSystem->mkdir($this->directory . '/dir1/dir2');
        $this->fileSystem->touch($this->directory . '/dir1/dir2/file3.txt');

        $this->fileManager->deleteFile('dir1/file2.txt');

        self::assertSame(
            ['dir1', 'dir1/dir2', 'dir1/dir2/file3.txt', 'dir1_file.txt', 'file1.txt'],
            $this->getFiles()
        );
    }
}
