<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\Monolog\Handler;

use Oro\Bundle\BatchBundle\Monolog\Handler\BatchLogHandler;
use Oro\Component\Testing\TempDirExtension;

class BatchLogHandlerTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    /** @var BatchLogHandler */
    protected $batchLogHandler;

    protected function setUp()
    {
        $this->batchLogHandler = new BatchLogHandler($this->getTempDir('batch_log_handler'));
        $this->batchLogHandler->setSubDirectory('batch_test');
    }

    protected function tearDown()
    {
        $this->batchLogHandler->close();
    }

    public function testGetIsActive()
    {
        $this->assertFalse($this->batchLogHandler->isActive());
        $this->batchLogHandler->setIsActive(true);
        $this->assertTrue($this->batchLogHandler->isActive());
    }

    public function testWrite()
    {
        $messageText = 'batch.DEBUG: Job execution started';
        $record      = ['formatted' => $messageText];

        $this->batchLogHandler->write($record);
        $this->assertFalse(is_file($this->batchLogHandler->getFilename()));

        $this->batchLogHandler->setIsActive(true);
        $this->batchLogHandler->write($record);
        $this->batchLogHandler->close();
        $this->assertEquals($messageText, file_get_contents($this->batchLogHandler->getFilename()));
    }
}
