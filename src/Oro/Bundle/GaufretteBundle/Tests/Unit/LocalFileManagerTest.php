<?php

namespace Oro\Bundle\GaufretteBundle\Tests\Unit;

use Gaufrette\Filesystem;
use Oro\Bundle\GaufretteBundle\Adapter\LocalAdapter;
use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Bundle\GaufretteBundle\FilesystemMap;
use Oro\Component\Testing\TempDirExtension;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;
use Symfony\Component\Finder\Finder;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class LocalFileManagerTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    private const TEST_FILE_SYSTEM_NAME  = 'testFileSystem';
    private const TEST_PROTOCOL          = 'testProtocol';
    private const TEST_READONLY_PROTOCOL = 'testReadonlyProtocol';

    /** @var string */
    private $rootDirectory;

    /** @var SymfonyFilesystem */
    private $fileSystem;

    protected function setUp(): void
    {
        $this->rootDirectory = $this->getTempDir('LocalFileManager');
        $this->fileSystem = new SymfonyFilesystem();
    }

    private function getFileManager(bool $useSubDir, string $subDir = null): FileManager
    {
        $fileManager = new FileManager(self::TEST_FILE_SYSTEM_NAME, $subDir);
        $fileManager->setProtocol(self::TEST_PROTOCOL);
        $fileManager->setReadonlyProtocol(self::TEST_READONLY_PROTOCOL);
        if ($useSubDir) {
            $fileManager->useSubDirectory(true);
        }

        $filesystemMap = $this->createMock(FilesystemMap::class);
        $filesystemMap->expects(self::once())
            ->method('get')
            ->with(self::TEST_FILE_SYSTEM_NAME)
            ->willReturn(new Filesystem(new LocalAdapter($this->rootDirectory)));
        $fileManager->setFilesystemMap($filesystemMap);

        return $fileManager;
    }

    /**
     * @return string[]
     */
    private function getFiles(string $rootDirectory): array
    {
        $files = [];
        $fileFinder = Finder::create()->in($rootDirectory);
        foreach ($fileFinder as $file) {
            $files[] = str_replace('\\', '/', substr($file->getPathname(), strlen($rootDirectory) + 1));
        }
        sort($files);

        return $files;
    }

    /**
     * @param FileManager $fileManager
     * @param string      $prefix
     *
     * @return string[]
     */
    private function fileManagerFindFiles(FileManager $fileManager, string $prefix = ''): array
    {
        $files = $fileManager->findFiles($prefix);
        sort($files);

        return $files;
    }

    public function fileManagerDataProvider(): array
    {
        return [
            'with auto configured sub directory'        => [true, null, '/' . self::TEST_FILE_SYSTEM_NAME],
            'without sub directory'                     => [false, null, ''],
            'with custom sub directory'                 => [true, 'testSubDir', '/testSubDir'],
            'with auto configured custom sub directory' => [false, 'testSubDir', '/testSubDir']
        ];
    }

    /**
     * @dataProvider fileManagerDataProvider
     */
    public function testGetAdapterDescription(bool $useSubDir, ?string $subDir, string $resultSubDir)
    {
        $fileManager = $this->getFileManager($useSubDir, $subDir);

        self::assertEquals(
            $this->rootDirectory . $resultSubDir,
            $fileManager->getAdapterDescription()
        );
    }

    /**
     * @dataProvider fileManagerDataProvider
     */
    public function testFindFiles(bool $useSubDir, ?string $subDir, string $resultSubDir)
    {
        $rootDirectory = $this->rootDirectory . $resultSubDir;
        $this->fileSystem->mkdir($rootDirectory . '/dir1');
        $this->fileSystem->touch($rootDirectory . '/file1.txt');
        $this->fileSystem->touch($rootDirectory . '/dir1_file.txt');
        $this->fileSystem->touch($rootDirectory . '/dir1/file2.txt');
        $this->fileSystem->touch($rootDirectory . '/dir1/test1.txt');

        $fileManager = $this->getFileManager($useSubDir, $subDir);

        self::assertSame(
            ['dir1/file2.txt', 'dir1/test1.txt', 'dir1_file.txt', 'file1.txt'],
            $this->fileManagerFindFiles($fileManager)
        );
        self::assertSame(
            ['dir1/file2.txt', 'dir1/test1.txt', 'dir1_file.txt', 'file1.txt'],
            $this->fileManagerFindFiles($fileManager, '/')
        );

        self::assertSame(
            ['dir1/file2.txt', 'dir1/test1.txt', 'dir1_file.txt'],
            $this->fileManagerFindFiles($fileManager, 'dir1')
        );
        self::assertSame(
            ['dir1/file2.txt', 'dir1/test1.txt', 'dir1_file.txt'],
            $this->fileManagerFindFiles($fileManager, '/dir1')
        );
        self::assertSame(
            ['dir1/file2.txt', 'dir1/test1.txt'],
            $this->fileManagerFindFiles($fileManager, 'dir1/')
        );
        self::assertSame(
            ['dir1/file2.txt', 'dir1/test1.txt'],
            $this->fileManagerFindFiles($fileManager, '/dir1/')
        );
        self::assertSame(
            ['dir1/file2.txt'],
            $this->fileManagerFindFiles($fileManager, 'dir1/file')
        );
    }

    /**
     * @dataProvider fileManagerDataProvider
     */
    public function testDeleteAllFiles(bool $useSubDir, ?string $subDir, string $resultSubDir)
    {
        $rootDirectory = $this->rootDirectory . $resultSubDir;
        $this->fileSystem->mkdir($rootDirectory . '/dir1');
        $this->fileSystem->touch($rootDirectory . '/file1.txt');
        $this->fileSystem->touch($rootDirectory . '/dir1/file2.txt');
        $this->fileSystem->mkdir($rootDirectory . '/dir1/dir2');
        $this->fileSystem->touch($rootDirectory . '/dir1/dir2/file3.txt');

        $fileManager = $this->getFileManager($useSubDir, $subDir);

        $fileManager->deleteAllFiles();

        self::assertSame([], $this->getFiles($rootDirectory));
    }

    /**
     * @dataProvider fileManagerDataProvider
     */
    public function testDeleteAllFilesBySlashPrefix(bool $useSubDir, ?string $subDir, string $resultSubDir)
    {
        $rootDirectory = $this->rootDirectory . $resultSubDir;
        $this->fileSystem->mkdir($rootDirectory . '/dir1');
        $this->fileSystem->touch($rootDirectory . '/file1.txt');
        $this->fileSystem->touch($rootDirectory . '/dir1_file.txt');
        $this->fileSystem->touch($rootDirectory . '/dir1/file2.txt');
        $this->fileSystem->mkdir($rootDirectory . '/dir1/dir2');
        $this->fileSystem->touch($rootDirectory . '/dir1/dir2/file3.txt');

        $fileManager = $this->getFileManager($useSubDir, $subDir);

        $fileManager->deleteAllFiles('/');

        self::assertSame([], $this->getFiles($rootDirectory));
    }

    /**
     * @dataProvider fileManagerDataProvider
     */
    public function testDeleteAllFilesByPrefix(bool $useSubDir, ?string $subDir, string $resultSubDir)
    {
        $rootDirectory = $this->rootDirectory . $resultSubDir;
        $this->fileSystem->mkdir($rootDirectory . '/dir1');
        $this->fileSystem->touch($rootDirectory . '/file1.txt');
        $this->fileSystem->touch($rootDirectory . '/dir1_file.txt');
        $this->fileSystem->touch($rootDirectory . '/dir1/file2.txt');
        $this->fileSystem->mkdir($rootDirectory . '/dir1/dir2');
        $this->fileSystem->touch($rootDirectory . '/dir1/dir2/file3.txt');

        $fileManager = $this->getFileManager($useSubDir, $subDir);

        $fileManager->deleteAllFiles('dir1');

        self::assertSame(['file1.txt'], $this->getFiles($rootDirectory));
    }

    /**
     * @dataProvider fileManagerDataProvider
     */
    public function testDeleteAllFilesByPrefixWithLeadingSlash(bool $useSubDir, ?string $subDir, string $resultSubDir)
    {
        $rootDirectory = $this->rootDirectory . $resultSubDir;
        $this->fileSystem->mkdir($rootDirectory . '/dir1');
        $this->fileSystem->touch($rootDirectory . '/file1.txt');
        $this->fileSystem->touch($rootDirectory . '/dir1_file.txt');
        $this->fileSystem->touch($rootDirectory . '/dir1/file2.txt');
        $this->fileSystem->mkdir($rootDirectory . '/dir1/dir2');
        $this->fileSystem->touch($rootDirectory . '/dir1/dir2/file3.txt');

        $fileManager = $this->getFileManager($useSubDir, $subDir);

        $fileManager->deleteAllFiles('/dir1');

        self::assertSame(['file1.txt'], $this->getFiles($rootDirectory));
    }

    /**
     * @dataProvider fileManagerDataProvider
     */
    public function testDeleteAllFilesByPrefixWithTailingSlash(bool $useSubDir, ?string $subDir, string $resultSubDir)
    {
        $rootDirectory = $this->rootDirectory . $resultSubDir;
        $this->fileSystem->mkdir($rootDirectory . '/dir1');
        $this->fileSystem->touch($rootDirectory . '/file1.txt');
        $this->fileSystem->touch($rootDirectory . '/dir1_file.txt');
        $this->fileSystem->touch($rootDirectory . '/dir1/file2.txt');
        $this->fileSystem->mkdir($rootDirectory . '/dir1/dir2');
        $this->fileSystem->touch($rootDirectory . '/dir1/dir2/file3.txt');

        $fileManager = $this->getFileManager($useSubDir, $subDir);

        $fileManager->deleteAllFiles('dir1/');

        self::assertSame(['dir1_file.txt', 'file1.txt'], $this->getFiles($rootDirectory));
    }

    /**
     * @dataProvider fileManagerDataProvider
     */
    public function testDeleteAllFilesByPrefixWithTwoTailingSlashes(
        bool $useSubDir,
        ?string $subDir,
        string $resultSubDir
    ) {
        $rootDirectory = $this->rootDirectory . $resultSubDir;
        $this->fileSystem->mkdir($rootDirectory . '/dir1');
        $this->fileSystem->touch($rootDirectory . '/file1.txt');
        $this->fileSystem->touch($rootDirectory . '/dir1_file.txt');
        $this->fileSystem->touch($rootDirectory . '/dir1/file2.txt');
        $this->fileSystem->mkdir($rootDirectory . '/dir1/dir2');
        $this->fileSystem->touch($rootDirectory . '/dir1/dir2/file3.txt');

        $fileManager = $this->getFileManager($useSubDir, $subDir);

        $fileManager->deleteAllFiles('dir1//');

        self::assertSame(['dir1_file.txt', 'file1.txt'], $this->getFiles($rootDirectory));
    }

    /**
     * @dataProvider fileManagerDataProvider
     */
    public function testDeleteFile(bool $useSubDir, ?string $subDir, string $resultSubDir)
    {
        $rootDirectory = $this->rootDirectory . $resultSubDir;
        $this->fileSystem->mkdir($rootDirectory . '/dir1');
        $this->fileSystem->touch($rootDirectory . '/file1.txt');
        $this->fileSystem->touch($rootDirectory . '/dir1/file2.txt');

        $fileManager = $this->getFileManager($useSubDir, $subDir);

        $fileManager->deleteFile('file1.txt');

        self::assertSame(['dir1', 'dir1/file2.txt'], $this->getFiles($rootDirectory));
    }

    /**
     * @dataProvider fileManagerDataProvider
     */
    public function testDeleteFileFromSubDirAndNoOtherFiles(bool $useSubDir, ?string $subDir, string $resultSubDir)
    {
        $rootDirectory = $this->rootDirectory . $resultSubDir;
        $this->fileSystem->mkdir($rootDirectory . '/dir1');
        $this->fileSystem->touch($rootDirectory . '/file1.txt');
        $this->fileSystem->touch($rootDirectory . '/dir1_file.txt');
        $this->fileSystem->touch($rootDirectory . '/dir1/file2.txt');

        $fileManager = $this->getFileManager($useSubDir, $subDir);

        $fileManager->deleteFile('dir1/file2.txt');

        self::assertSame(['dir1_file.txt', 'file1.txt'], $this->getFiles($rootDirectory));
    }

    /**
     * @dataProvider fileManagerDataProvider
     */
    public function testDeleteFileFromSubDirAndThereAreOtherFiles(
        bool $useSubDir,
        ?string $subDir,
        string $resultSubDir
    ) {
        $rootDirectory = $this->rootDirectory . $resultSubDir;
        $this->fileSystem->mkdir($rootDirectory . '/dir1');
        $this->fileSystem->touch($rootDirectory . '/file1.txt');
        $this->fileSystem->touch($rootDirectory . '/dir1_file.txt');
        $this->fileSystem->touch($rootDirectory . '/dir1/file2.txt');
        $this->fileSystem->mkdir($rootDirectory . '/dir1/dir2');
        $this->fileSystem->touch($rootDirectory . '/dir1/dir2/file3.txt');

        $fileManager = $this->getFileManager($useSubDir, $subDir);

        $fileManager->deleteFile('dir1/file2.txt');

        self::assertSame(
            ['dir1', 'dir1/dir2', 'dir1/dir2/file3.txt', 'dir1_file.txt', 'file1.txt'],
            $this->getFiles($rootDirectory)
        );
    }
}
