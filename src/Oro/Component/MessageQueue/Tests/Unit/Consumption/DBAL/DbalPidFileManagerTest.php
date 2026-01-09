<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Consumption\DBAL;

use Oro\Component\MessageQueue\Consumption\Dbal\DbalPidFileManager;
use Oro\Component\Testing\TempDirExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class DbalPidFileManagerTest extends TestCase
{
    use TempDirExtension;

    private string $pidDir;

    #[\Override]
    protected function setUp(): void
    {
        $this->pidDir = $this->getTempDir('test-mq-dbal', false);
    }

    public function testCouldCreatePidFile(): void
    {
        $expectedFile = $this->pidDir . '/CONSUMER.ID.pid';

        $processManager = new DbalPidFileManager($this->pidDir);
        $processManager->createPidFile('CONSUMER.ID');

        $this->assertFileExists($expectedFile);
        $this->assertTrue(is_numeric(file_get_contents($expectedFile)));
    }

    public function testShouldThrowIfPidFileAlreadyExists(): void
    {
        $processManager = new DbalPidFileManager($this->pidDir);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The pid file already exists');

        $processManager->createPidFile('CONSUMER.ID');
        $processManager->createPidFile('CONSUMER.ID');
    }

    public function testShouldReturnListOfPidsFileInfo(): void
    {
        $fs = new Filesystem();
        $fs->dumpFile($this->pidDir . '/pid1.pid', '12345');
        $fs->dumpFile($this->pidDir . '/pid2.pid', '54321');

        $processManager = new DbalPidFileManager($this->pidDir);

        $result = $processManager->getListOfPidsFileInfo();

        $expectedResult = [
            [
                'pid' => 12345,
                'consumerId' => 'pid1',
            ],
            [
                'pid' => 54321,
                'consumerId' => 'pid2',
            ],
        ];

        $this->assertEquals($expectedResult, $result);
    }

    public function testShouldThrowIfPidFileContainsNonNumericValue(): void
    {
        $fs = new Filesystem();
        $fs->dumpFile($this->pidDir . '/pid1.pid', 'non numeric value');

        $processManager = new DbalPidFileManager($this->pidDir);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Expected numeric content. content:"non numeric value"');

        $processManager->getListOfPidsFileInfo();
    }

    public function testShouldRemovePidFile(): void
    {
        $filename = $this->pidDir . '/consumer-id.pid';

        $processManager = new DbalPidFileManager($this->pidDir);
        $processManager->createPidFile('consumer-id');

        // guard
        $this->assertFileExists($filename);

        // test
        $processManager->removePidFile('consumer-id');
        $this->assertFileDoesNotExist($filename);
    }

    public function testShouldNotThrowAnyErrorIfFileDoesNotExistWhenRemovindPids(): void
    {
        $processManager = new DbalPidFileManager($this->pidDir);
        $processManager->createPidFile('consumer-id');

        // guard
        $this->assertFileDoesNotExist($this->pidDir . '/not-existent-pid-file.pid');

        // test
        $processManager->removePidFile('not-existent-pid-file');
    }
}
