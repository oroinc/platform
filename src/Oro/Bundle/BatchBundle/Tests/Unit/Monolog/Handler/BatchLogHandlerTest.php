<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\Monolog\Handler;

use Oro\Bundle\BatchBundle\Monolog\Handler\BatchLogHandler;

class BatchLogHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var BatchLogHandler */
    protected $batchLogHandler;

    protected function setUp()
    {
        $this->batchLogHandler = new BatchLogHandler(sys_get_temp_dir());
        $this->batchLogHandler->setSubDirectory('batch_test');
    }

    protected function tearDown()
    {
        if (is_file($this->batchLogHandler->getFilename())) {
            unlink($this->batchLogHandler->getFilename());
            rmdir(dirname($this->batchLogHandler->getFilename()));
        }

        unset($this->batchLogHandler);
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
