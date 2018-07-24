<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Handler;

use Akeneo\Bundle\BatchBundle\Item\ItemReaderInterface;
use Akeneo\Bundle\BatchBundle\Item\ItemWriterInterface;
use Oro\Bundle\DataGridBundle\Handler\ExportHandler;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Processor\ExportProcessor;
use Psr\Log\LoggerInterface;

class ExportHandlerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var FileManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $fileManager;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $logger;

    /**
     * @var ExportHandler
     */
    private $exportHandler;

    protected function setUp()
    {
        $this->fileManager = $this->createMock(FileManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->exportHandler = new ExportHandler();

        $this->exportHandler->setFileManager($this->fileManager);
        $this->exportHandler->setLogger($this->logger);
    }


    public function testHandleExceptionsAreAddedToContext()
    {
        $reader = $this->createMock(ItemReaderInterface::class);
        $processor = $this->createMock(ExportProcessor::class);
        $writer = $this->createMock(ItemWriterInterface::class);

        $contextParameters = ['gridName' => 'test-grid'];
        $batchSize = 1000;
        $format = 'csv';

        $exceptionMsg = 'Failure exception';
        $exception = new \Exception($exceptionMsg);
        $reader
            ->expects(self::once())
            ->method('read')
            ->willThrowException($exception);

        $result = $this->exportHandler->handle($reader, $processor, $writer, $contextParameters, $batchSize, $format);

        self::assertEquals($exceptionMsg, $result['errors'][0]);
        self::assertEquals(1, $result['errorsCount']);
    }
}
