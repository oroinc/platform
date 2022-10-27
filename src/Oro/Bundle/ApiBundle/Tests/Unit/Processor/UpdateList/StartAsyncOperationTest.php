<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\UpdateList;

use Oro\Bundle\ApiBundle\Batch\Async\Topic\UpdateListTopic;
use Oro\Bundle\ApiBundle\Batch\ChunkSizeProvider;
use Oro\Bundle\ApiBundle\Processor\UpdateList\StartAsyncOperation;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class StartAsyncOperationTest extends UpdateListProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|MessageProducerInterface */
    private $producer;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ChunkSizeProvider */
    private $chunkSizeProvider;

    /** @var StartAsyncOperation */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->producer = $this->createMock(MessageProducerInterface::class);
        $this->chunkSizeProvider = $this->createMock(ChunkSizeProvider::class);

        $this->processor = new StartAsyncOperation($this->producer, $this->chunkSizeProvider);
    }

    public function testProcessWhenAsyncOperationIsAlreadyStarted()
    {
        $this->producer->expects(self::never())
            ->method('send');

        $this->context->setProcessed(StartAsyncOperation::OPERATION_NAME);
        $this->processor->process($this->context);
        self::assertTrue($this->context->isProcessed(StartAsyncOperation::OPERATION_NAME));
    }

    public function testProcessWhenNoOperationId()
    {
        $this->producer->expects(self::never())
            ->method('send');

        $this->processor->process($this->context);
        self::assertFalse($this->context->isProcessed(StartAsyncOperation::OPERATION_NAME));
    }

    public function testProcessWhenNoTargetFileName()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The target file name was not set to the context.');

        $this->producer->expects(self::never())
            ->method('send');

        $this->context->setOperationId(123);
        $this->processor->process($this->context);
    }

    public function testProcess()
    {
        $operationId = 123;
        $entityClass = 'Test\Class';
        $chunkSize = 10;
        $includedDataChunkSize = 20;
        $targetFileName = 'testFile';

        $this->chunkSizeProvider->expects(self::once())
            ->method('getChunkSize')
            ->with($entityClass)
            ->willReturn($chunkSize);
        $this->chunkSizeProvider->expects(self::once())
            ->method('getIncludedDataChunkSize')
            ->with($entityClass)
            ->willReturn($includedDataChunkSize);
        $this->producer->expects(self::once())
            ->method('send')
            ->with(
                UpdateListTopic::getName(),
                [
                    'operationId'           => $operationId,
                    'entityClass'           => $entityClass,
                    'requestType'           => [self::TEST_REQUEST_TYPE],
                    'version'               => self::TEST_VERSION,
                    'fileName'              => $targetFileName,
                    'chunkSize'             => $chunkSize,
                    'includedDataChunkSize' => $includedDataChunkSize
                ]
            );

        $this->context->setOperationId($operationId);
        $this->context->setTargetFileName($targetFileName);
        $this->context->setClassName($entityClass);
        $this->processor->process($this->context);
        self::assertTrue($this->context->isProcessed(StartAsyncOperation::OPERATION_NAME));
    }
}
