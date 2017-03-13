<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Consumption\Dbal;

use Oro\Component\MessageQueue\Consumption\Dbal\DbalPidFileManager;
use Symfony\Component\Filesystem\Filesystem;

class DbalPidFileManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    private $pidDir;

    protected function setUp()
    {
        parent::setUp();

        $this->pidDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'test-mq-dbal';

        $fs = new Filesystem();
        $fs->remove($this->pidDir);
    }

    public function testCouldCreatePidFile()
    {
        $expectedFile = $this->pidDir.'/CONSUMER.ID.pid';

        $processManager = new DbalPidFileManager($this->pidDir);
        $processManager->createPidFile('CONSUMER.ID');

        $this->assertFileExists($expectedFile);
        $this->assertTrue(is_numeric(file_get_contents($expectedFile)));
    }

    public function testShouldThrowIfPidFileAlreadyExists()
    {
        $processManager = new DbalPidFileManager($this->pidDir);

        $this->setExpectedException(\LogicException::class, 'Pid file already exists');

        $processManager->createPidFile('CONSUMER.ID');
        $processManager->createPidFile('CONSUMER.ID');
    }

    public function testShouldReturnListOfPidsFileInfo()
    {
        $fs = new Filesystem();
        $fs->dumpFile($this->pidDir.'/pid1.pid', '12345');
        $fs->dumpFile($this->pidDir.'/pid2.pid', '54321');

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

    public function testShouldThrowIfPidFileContainsNonNumericValue()
    {
        $fs = new Filesystem();
        $fs->dumpFile($this->pidDir.'/pid1.pid', 'non numeric value');

        $processManager = new DbalPidFileManager($this->pidDir);

        $this->setExpectedException(\LogicException::class, 'Expected numeric content. content:"non numeric value"');

        $processManager->getListOfPidsFileInfo();
    }

    public function testShouldRemovePidFile()
    {
        $filename = $this->pidDir.'/consumer-id.pid';

        $processManager = new DbalPidFileManager($this->pidDir);
        $processManager->createPidFile('consumer-id');

        // guard
        $this->assertFileExists($filename);

        // test
        $processManager->removePidFile('consumer-id');
        $this->assertFileNotExists($filename);
    }

    public function testShouldNotThrowAnyErrorIfFileDoesNotExistWhenRemovindPids()
    {
        $processManager = new DbalPidFileManager($this->pidDir);
        $processManager->createPidFile('consumer-id');

        // guard
        $this->assertFileNotExists($this->pidDir.'/not-existent-pid-file.pid');

        // test
        $processManager->removePidFile('not-existent-pid-file');
    }
}
