<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\UpdateList;

use Oro\Bundle\ApiBundle\Batch\Async\Topic\UpdateListTopic;
use Oro\Bundle\ApiBundle\Batch\ChunkSizeProvider;
use Oro\Bundle\ApiBundle\Batch\SyncProcessingLimitProvider;
use Oro\Bundle\ApiBundle\Processor\UpdateList\StartAsyncOperation;
use Oro\Bundle\MessageQueueBundle\Client\BufferedMessageProducer;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class StartAsyncOperationTest extends UpdateListProcessorTestCase
{
    /** @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $producer;

    /** @var ChunkSizeProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $chunkSizeProvider;

    /** @var SyncProcessingLimitProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $syncProcessingLimitProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->producer = $this->createMock(MessageProducerInterface::class);
        $this->chunkSizeProvider = $this->createMock(ChunkSizeProvider::class);
        $this->syncProcessingLimitProvider = $this->createMock(SyncProcessingLimitProvider::class);
    }

    private function getProcessor(MessageProducerInterface $producer = null): StartAsyncOperation
    {
        return new StartAsyncOperation(
            $producer ?? $this->producer,
            $this->chunkSizeProvider,
            $this->syncProcessingLimitProvider
        );
    }

    public function testProcessWhenAsyncOperationIsAlreadyStarted(): void
    {
        $this->producer->expects(self::never())
            ->method('send');

        $this->context->setProcessed(StartAsyncOperation::OPERATION_NAME);
        $this->getProcessor()->process($this->context);
        self::assertTrue($this->context->isProcessed(StartAsyncOperation::OPERATION_NAME));
    }

    public function testProcessWhenNoOperationId(): void
    {
        $this->producer->expects(self::never())
            ->method('send');

        $this->getProcessor()->process($this->context);
        self::assertFalse($this->context->isProcessed(StartAsyncOperation::OPERATION_NAME));
    }

    public function testProcessWhenNoTargetFileName(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The target file name was not set to the context.');

        $this->producer->expects(self::never())
            ->method('send');

        $this->context->setOperationId(123);
        $this->getProcessor()->process($this->context);
    }

    public function testProcess(): void
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
                new Message([
                    'operationId'           => $operationId,
                    'entityClass'           => $entityClass,
                    'requestType'           => [self::TEST_REQUEST_TYPE],
                    'version'               => self::TEST_VERSION,
                    'synchronousMode'       => false,
                    'fileName'              => $targetFileName,
                    'chunkSize'             => $chunkSize,
                    'includedDataChunkSize' => $includedDataChunkSize
                ], MessagePriority::NORMAL)
            );

        $this->context->setOperationId($operationId);
        $this->context->setTargetFileName($targetFileName);
        $this->context->setClassName($entityClass);
        $this->getProcessor()->process($this->context);
        self::assertTrue($this->context->isProcessed(StartAsyncOperation::OPERATION_NAME));
    }

    public function testProcessForBufferedMessageProducer(): void
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
        $producer = $this->createMock(BufferedMessageProducer::class);
        $producer->expects(self::once())
            ->method('send')
            ->with(
                UpdateListTopic::getName(),
                new Message([
                    'operationId'           => $operationId,
                    'entityClass'           => $entityClass,
                    'requestType'           => [self::TEST_REQUEST_TYPE],
                    'version'               => self::TEST_VERSION,
                    'synchronousMode'       => false,
                    'fileName'              => $targetFileName,
                    'chunkSize'             => $chunkSize,
                    'includedDataChunkSize' => $includedDataChunkSize
                ], MessagePriority::NORMAL)
            );
        $producer->expects(self::never())
            ->method('isBufferingEnabled');

        $this->context->setOperationId($operationId);
        $this->context->setTargetFileName($targetFileName);
        $this->context->setClassName($entityClass);
        $this->getProcessor($producer)->process($this->context);
        self::assertTrue($this->context->isProcessed(StartAsyncOperation::OPERATION_NAME));
    }

    public function testProcessForBufferedMessageProducerAngSyncProcessingAndBufferingDisabled(): void
    {
        $operationId = 123;
        $entityClass = 'Test\Class';
        $chunkSize = 10;
        $includedDataChunkSize = 20;
        $targetFileName = 'testFile';

        $this->syncProcessingLimitProvider->expects(self::once())
            ->method('getLimit')
            ->with($entityClass)
            ->willReturn($chunkSize);
        $this->syncProcessingLimitProvider->expects(self::once())
            ->method('getIncludedDataLimit')
            ->with($entityClass)
            ->willReturn($includedDataChunkSize);
        $producer = $this->createMock(BufferedMessageProducer::class);
        $producer->expects(self::once())
            ->method('send')
            ->with(
                UpdateListTopic::getName(),
                new Message([
                    'operationId'           => $operationId,
                    'entityClass'           => $entityClass,
                    'requestType'           => [self::TEST_REQUEST_TYPE],
                    'version'               => self::TEST_VERSION,
                    'synchronousMode'       => true,
                    'fileName'              => $targetFileName,
                    'chunkSize'             => $chunkSize,
                    'includedDataChunkSize' => $includedDataChunkSize
                ], MessagePriority::HIGH)
            );
        $producer->expects(self::once())
            ->method('isBufferingEnabled')
            ->willReturn(false);
        $producer->expects(self::never())
            ->method('flushBuffer');

        $this->context->setOperationId($operationId);
        $this->context->setTargetFileName($targetFileName);
        $this->context->setClassName($entityClass);
        $this->context->setSynchronousMode(true);
        $this->getProcessor($producer)->process($this->context);
        self::assertTrue($this->context->isProcessed(StartAsyncOperation::OPERATION_NAME));
    }

    public function testProcessForBufferedMessageProducerAngSyncProcessingAndBufferingEnabled(): void
    {
        $operationId = 123;
        $entityClass = 'Test\Class';
        $chunkSize = 10;
        $includedDataChunkSize = 20;
        $targetFileName = 'testFile';

        $this->syncProcessingLimitProvider->expects(self::once())
            ->method('getLimit')
            ->with($entityClass)
            ->willReturn($chunkSize);
        $this->syncProcessingLimitProvider->expects(self::once())
            ->method('getIncludedDataLimit')
            ->with($entityClass)
            ->willReturn($includedDataChunkSize);
        $producer = $this->createMock(BufferedMessageProducer::class);
        $producer->expects(self::once())
            ->method('send')
            ->with(
                UpdateListTopic::getName(),
                new Message([
                    'operationId'           => $operationId,
                    'entityClass'           => $entityClass,
                    'requestType'           => [self::TEST_REQUEST_TYPE],
                    'version'               => self::TEST_VERSION,
                    'synchronousMode'       => true,
                    'fileName'              => $targetFileName,
                    'chunkSize'             => $chunkSize,
                    'includedDataChunkSize' => $includedDataChunkSize
                ], MessagePriority::HIGH)
            );
        $producer->expects(self::once())
            ->method('isBufferingEnabled')
            ->willReturn(true);
        $producer->expects(self::once())
            ->method('flushBuffer');

        $this->context->setOperationId($operationId);
        $this->context->setTargetFileName($targetFileName);
        $this->context->setClassName($entityClass);
        $this->context->setSynchronousMode(true);
        $this->getProcessor($producer)->process($this->context);
        self::assertTrue($this->context->isProcessed(StartAsyncOperation::OPERATION_NAME));
    }
}
