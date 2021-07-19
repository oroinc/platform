<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\Monolog\Handler;

use Oro\Bundle\BatchBundle\Monolog\Handler\BatchLogHandler;
use Oro\Component\Testing\TempDirExtension;

class BatchLogHandlerTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    public function testWrite(): void
    {
        $batchLogHandler = new BatchLogHandler($this->getTempDir('batch_log_handler'), true);
        $batchLogHandler->setSubDirectory('batch_test');

        $messageText = 'batch.DEBUG: Job execution started';
        $record = ['formatted' => $messageText];

        $batchLogHandler->write($record);
        $batchLogHandler->close();
        self::assertFileExists($batchLogHandler->getFilename());
        self::assertEquals($messageText, file_get_contents($batchLogHandler->getFilename()));
    }

    public function testWriteWhenNotActive(): void
    {
        $batchLogHandler = new BatchLogHandler($this->getTempDir('batch_log_handler'), false);

        $messageText = 'batch.DEBUG: Job execution started';
        $record = ['formatted' => $messageText];

        $batchLogHandler->write($record);
        self::assertFileDoesNotExist($batchLogHandler->getFilename());

        $batchLogHandler->close();
    }
}
