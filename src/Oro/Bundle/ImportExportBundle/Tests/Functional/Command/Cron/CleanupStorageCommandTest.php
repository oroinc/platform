<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Functional\Command\Cron;

use Gaufrette\File;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class CleanupStorageCommandTest extends WebTestCase
{
    /** @var FileManager|\PHPUnit\Framework\MockObject\MockObject */
    private $fileManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->initClient();

        $this->fileManager = $this->createMock(FileManager::class);

        self::getContainer()->set('oro_importexport.file.file_manager.stub', $this->fileManager);
    }

    public function testExecuteWhenNoFilesReturned(): void
    {
        $this->fileManager->expects(self::once())
            ->method('getFilesByPeriod')
            ->willReturn([]);

        $result = self::runCommand('oro:cron:import-clean-up-storage');

        $this->assertEquals('Were removed "0" files.', $result);
    }

    public function testExecuteWhenFilesReturned(): void
    {
        $firstFile = $this->createMock(File::class);
        $secondFile = $this->createMock(File::class);

        $this->fileManager->expects(self::once())
            ->method('getFilesByPeriod')
            ->willReturn(['firstFile' => $firstFile, 'secondFile' => $secondFile]);

        $this->fileManager->expects(self::exactly(2))
            ->method('deleteFile')
            ->withConsecutive([$firstFile], [$secondFile]);

        $result = self::runCommand('oro:cron:import-clean-up-storage');

        $this->assertEquals('Were removed "2" files.', $result);
    }
}
