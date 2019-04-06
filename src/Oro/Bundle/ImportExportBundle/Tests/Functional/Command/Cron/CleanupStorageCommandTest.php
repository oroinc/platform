<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Functional\Command;

use Gaufrette\File;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class CleanupStorageCommandTest extends WebTestCase
{
    /**
     * @var FileManager
     */
    private $previousFileManager;

    /**
     * @var FileManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $fileManager;

    protected function setUp()
    {
        parent::setUp();
        $this->initClient();

        $this->fileManager = $this->createMock(FileManager::class);
        $this->previousFileManager = self::getContainer()->get('oro_importexport.file.file_manager');

        self::getContainer()->set('oro_importexport.file.file_manager', $this->fileManager);
    }

    protected function tearDown()
    {
        parent::tearDown();
        self::getContainer()->set('oro_importexport.file.file_manager', $this->previousFileManager);
    }

    public function testExecuteWhenNoFilesReturned(): void
    {
        $this->fileManager
            ->expects(self::once())
            ->method('getFilesByPeriod')
            ->willReturn([]);

        $result = self::runCommand('oro:cron:import-clean-up-storage');

        $this->assertEquals('Were removed "0" files.', $result);
    }

    public function testExecuteWhenFilesReturned(): void
    {
        $firstFile = $this->createMock(File::class);
        $secondFile = $this->createMock(File::class);

        $this->fileManager
            ->expects(self::once())
            ->method('getFilesByPeriod')
            ->willReturn(['firstFile' => $firstFile, 'secondFile' => $secondFile]);

        $this->fileManager
            ->expects(self::exactly(2))
            ->method('deleteFile')
            ->withConsecutive([$firstFile], [$secondFile]);

        $result = self::runCommand('oro:cron:import-clean-up-storage');

        $this->assertEquals('Were removed "2" files.', $result);
    }
}
