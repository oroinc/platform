<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Processor\Update;

use Doctrine\DBAL\Driver\DriverException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Oro\Bundle\ApiBundle\Batch\Handler\BatchFlushDataHandlerFactoryInterface;
use Oro\Bundle\ApiBundle\Batch\Handler\BatchFlushDataHandlerFactoryRegistry;
use Oro\Bundle\ApiBundle\Batch\Handler\BatchFlushDataHandlerInterface;
use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateItem;
use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateItemStatus;
use Oro\Bundle\ApiBundle\Batch\Model\ChunkFile;
use Oro\Bundle\ApiBundle\Batch\Processor\Update\BatchUpdateContext;
use Oro\Bundle\ApiBundle\Batch\Processor\Update\FlushData;
use Oro\Bundle\ApiBundle\Batch\Processor\UpdateItem\BatchUpdateItemContext;
use Oro\Bundle\ApiBundle\Model\Error;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FlushDataTest extends BatchUpdateProcessorTestCase
{
    private const ENTITY_CLASS = 'Test\Entity';

    /** @var \PHPUnit\Framework\MockObject\MockObject|BatchFlushDataHandlerFactoryRegistry */
    private $flushDataHandlerFactoryRegistry;

    /** @var \PHPUnit\Framework\MockObject\MockObject|BatchFlushDataHandlerFactoryInterface */
    private $flushDataHandlerFactory;

    /** @var \PHPUnit\Framework\MockObject\MockObject|LoggerInterface */
    private $logger;

    /** @var FlushData */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->flushDataHandlerFactoryRegistry = $this->createMock(BatchFlushDataHandlerFactoryRegistry::class);
        $this->flushDataHandlerFactory = $this->createMock(BatchFlushDataHandlerFactoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->processor = new FlushData($this->flushDataHandlerFactoryRegistry, $this->logger);
    }

    /**
     * @param int $itemIndex
     *
     * @return BatchUpdateItem|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getBatchUpdateItem(int $itemIndex): BatchUpdateItem
    {
        $item = $this->createMock(BatchUpdateItem::class);
        $item->expects(self::any())
            ->method('getIndex')
            ->willReturn($itemIndex);
        $item->expects(self::any())
            ->method('getContext')
            ->willReturn(new BatchUpdateItemContext());

        return $item;
    }

    private function initializeProcessedItemStatuses(BatchUpdateContext $context)
    {
        $processedItemStatuses = [];
        $items = $context->getBatchItems();
        foreach ($items as $item) {
            $processedItemStatuses[$item->getIndex()] = BatchUpdateItemStatus::NOT_PROCESSED;
        }
        $context->setProcessedItemStatuses($processedItemStatuses);
    }

    public function testProcessWhenDataAlreadyFlushed()
    {
        $this->flushDataHandlerFactoryRegistry->expects(self::never())
            ->method('getFactory');
        $this->flushDataHandlerFactory->expects(self::never())
            ->method('createHandler');

        $this->context->setProcessed(FlushData::OPERATION_NAME);
        $this->context->setBatchItems([$this->getBatchUpdateItem(0)]);
        $this->initializeProcessedItemStatuses($this->context);
        $this->processor->process($this->context);
        self::assertNull($this->context->getFlushDataHandler());
    }

    public function testProcessWhenNoBatchItemsInContext()
    {
        $this->flushDataHandlerFactoryRegistry->expects(self::never())
            ->method('getFactory');
        $this->flushDataHandlerFactory->expects(self::never())
            ->method('createHandler');

        $this->context->setBatchItems([]);
        $this->initializeProcessedItemStatuses($this->context);
        $this->processor->process($this->context);
        self::assertTrue($this->context->isProcessed(FlushData::OPERATION_NAME));
        self::assertNull($this->context->getFlushDataHandler());
    }

    public function testProcessWhenSomeBatchItemsHaveErrorsDetectedOnInitializeStep()
    {
        $item1 = $this->getBatchUpdateItem(0);
        $item2 = $this->getBatchUpdateItem(1);
        $item1->getContext()->setClassName(self::ENTITY_CLASS);
        $item2->getContext()->setClassName('Test\AnotherEntity');
        $item2Error = Error::createValidationError('entity type constraint');
        $item2->getContext()->addError($item2Error);
        $items = [$item1, $item2];

        $flushDataHandler = $this->createMock(BatchFlushDataHandlerInterface::class);
        $this->flushDataHandlerFactoryRegistry->expects(self::once())
            ->method('getFactory')
            ->with(self::ENTITY_CLASS)
            ->willReturn($this->flushDataHandlerFactory);
        $this->flushDataHandlerFactory->expects(self::once())
            ->method('createHandler')
            ->with(self::ENTITY_CLASS)
            ->willReturn($flushDataHandler);

        $flushDataHandler->expects(self::at(0))
            ->method('startFlushData')
            ->with($items);
        $flushDataHandler->expects(self::never())
            ->method('flushData');
        $flushDataHandler->expects(self::at(1))
            ->method('finishFlushData')
            ->with($items);

        $this->context->setBatchItems($items);
        $this->initializeProcessedItemStatuses($this->context);
        $this->processor->process($this->context);
        self::assertTrue($this->context->isProcessed(FlushData::OPERATION_NAME));
        self::assertSame($flushDataHandler, $this->context->getFlushDataHandler());
        self::assertEquals(
            [BatchUpdateItemStatus::NOT_PROCESSED, BatchUpdateItemStatus::HAS_PERMANENT_ERRORS],
            $this->context->getProcessedItemStatuses()
        );
        self::assertFalse($this->context->hasErrors());
        self::assertFalse($item1->getContext()->hasErrors());
        self::assertTrue($item2->getContext()->hasErrors());
        self::assertEquals([$item2Error], $item2->getContext()->getErrors());
    }

    public function testProcessWhenAllBatchItemsHaveErrorsDetectedOnInitializeStep()
    {
        $item1 = $this->getBatchUpdateItem(0);
        $item2 = $this->getBatchUpdateItem(1);
        $item1->getContext()->setClassName(self::ENTITY_CLASS);
        $item2->getContext()->setClassName(self::ENTITY_CLASS);
        $item1Error = Error::createValidationError('some error', 'item 1');
        $item1->getContext()->addError($item1Error);
        $item2Error = Error::createValidationError('some error', 'item 2');
        $item2->getContext()->addError($item2Error);
        $items = [$item1, $item2];

        $this->flushDataHandlerFactoryRegistry->expects(self::never())
            ->method('getFactory');
        $this->flushDataHandlerFactory->expects(self::never())
            ->method('createHandler');

        $this->context->setBatchItems($items);
        $this->initializeProcessedItemStatuses($this->context);
        $this->processor->process($this->context);
        self::assertTrue($this->context->isProcessed(FlushData::OPERATION_NAME));
        self::assertNull($this->context->getFlushDataHandler());
        self::assertEquals(
            [BatchUpdateItemStatus::HAS_PERMANENT_ERRORS, BatchUpdateItemStatus::HAS_PERMANENT_ERRORS],
            $this->context->getProcessedItemStatuses()
        );
        self::assertFalse($this->context->hasErrors());
        self::assertTrue($item1->getContext()->hasErrors());
        self::assertEquals([$item1Error], $item1->getContext()->getErrors());
        self::assertTrue($item2->getContext()->hasErrors());
        self::assertEquals([$item2Error], $item2->getContext()->getErrors());
    }

    public function testProcessWhenFlushDataHandlerNotFound()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The flush data handler is not registered for Test\Entity.');

        $item1 = $this->getBatchUpdateItem(0);
        $item2 = $this->getBatchUpdateItem(1);
        $item1->getContext()->setClassName(self::ENTITY_CLASS);
        $item2->getContext()->setClassName(self::ENTITY_CLASS);
        $items = [$item1, $item2];

        $this->flushDataHandlerFactoryRegistry->expects(self::once())
            ->method('getFactory')
            ->with(self::ENTITY_CLASS)
            ->willReturn($this->flushDataHandlerFactory);
        $this->flushDataHandlerFactory->expects(self::once())
            ->method('createHandler')
            ->with(self::ENTITY_CLASS)
            ->willReturn(null);

        $this->context->setBatchItems($items);
        $this->initializeProcessedItemStatuses($this->context);
        $this->processor->process($this->context);
        self::assertNull($this->context->getFlushDataHandler());
    }

    public function testProcessWhenDataFlushedWithoutAnyErrors()
    {
        $item1 = $this->getBatchUpdateItem(0);
        $item2 = $this->getBatchUpdateItem(1);
        $item1->getContext()->setClassName(self::ENTITY_CLASS);
        $item2->getContext()->setClassName(self::ENTITY_CLASS);
        $items = [$item1, $item2];

        $flushDataHandler = $this->createMock(BatchFlushDataHandlerInterface::class);
        $this->flushDataHandlerFactoryRegistry->expects(self::once())
            ->method('getFactory')
            ->with(self::ENTITY_CLASS)
            ->willReturn($this->flushDataHandlerFactory);
        $this->flushDataHandlerFactory->expects(self::once())
            ->method('createHandler')
            ->with(self::ENTITY_CLASS)
            ->willReturn($flushDataHandler);

        $flushDataHandler->expects(self::at(0))
            ->method('startFlushData')
            ->with($items);
        $flushDataHandler->expects(self::at(1))
            ->method('flushData')
            ->with($items);
        $flushDataHandler->expects(self::at(2))
            ->method('finishFlushData')
            ->with($items);

        $this->context->setBatchItems($items);
        $this->initializeProcessedItemStatuses($this->context);
        $this->processor->process($this->context);
        self::assertTrue($this->context->isProcessed(FlushData::OPERATION_NAME));
        self::assertSame($flushDataHandler, $this->context->getFlushDataHandler());
        self::assertEquals(
            [BatchUpdateItemStatus::NO_ERRORS, BatchUpdateItemStatus::NO_ERRORS],
            $this->context->getProcessedItemStatuses()
        );
        self::assertNull($this->context->getFailedGroup());
        self::assertSame([], $this->context->getSkippedGroups());
        self::assertFalse($this->context->hasErrors());
        self::assertFalse($item1->getContext()->hasErrors());
        self::assertFalse($item2->getContext()->hasErrors());
    }

    public function testProcessWhenExceptionOccurredInFlushDataAndSeveralBatchItemsInContext()
    {
        $operationId = 123;
        $chunkFileName = 'test.json';

        $item1 = $this->getBatchUpdateItem(0);
        $item2 = $this->getBatchUpdateItem(1);
        $item1->getContext()->setClassName(self::ENTITY_CLASS);
        $item2->getContext()->setClassName(self::ENTITY_CLASS);
        $items = [$item1, $item2];

        $exception = new \Exception('flushData exception');

        $flushDataHandler = $this->createMock(BatchFlushDataHandlerInterface::class);
        $this->flushDataHandlerFactoryRegistry->expects(self::once())
            ->method('getFactory')
            ->with(self::ENTITY_CLASS)
            ->willReturn($this->flushDataHandlerFactory);
        $this->flushDataHandlerFactory->expects(self::once())
            ->method('createHandler')
            ->with(self::ENTITY_CLASS)
            ->willReturn($flushDataHandler);

        $flushDataHandler->expects(self::at(0))
            ->method('startFlushData')
            ->with($items);
        $flushDataHandler->expects(self::at(1))
            ->method('flushData')
            ->with($items)
            ->willThrowException($exception);
        $flushDataHandler->expects(self::at(2))
            ->method('finishFlushData')
            ->with($items);

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Unexpected error occurred when flushing data for a batch operation chunk.',
                ['operationId' => $operationId, 'chunkFile' => $chunkFileName, 'exception' => $exception]
            );

        $this->context->setOperationId($operationId);
        $this->context->setFile(new ChunkFile($chunkFileName, 0, 0, 'data'));
        $this->context->setBatchItems($items);
        $this->initializeProcessedItemStatuses($this->context);
        $this->processor->process($this->context);
        self::assertTrue($this->context->isProcessed(FlushData::OPERATION_NAME));
        self::assertSame($flushDataHandler, $this->context->getFlushDataHandler());
        self::assertEquals(
            [BatchUpdateItemStatus::HAS_ERRORS, BatchUpdateItemStatus::HAS_ERRORS],
            $this->context->getProcessedItemStatuses()
        );
        self::assertFalse($this->context->hasErrors());
        self::assertFalse($item1->getContext()->hasErrors());
        self::assertFalse($item2->getContext()->hasErrors());
    }

    public function testProcessWhenUniqueConstraintViolationExceptionOccurredInFlushDataAndSeveralBatchItemsInContext()
    {
        $item1 = $this->getBatchUpdateItem(0);
        $item2 = $this->getBatchUpdateItem(1);
        $item1->getContext()->setClassName(self::ENTITY_CLASS);
        $item2->getContext()->setClassName(self::ENTITY_CLASS);
        $items = [$item1, $item2];

        $exception = new UniqueConstraintViolationException(
            'flushData exception',
            $this->createMock(DriverException::class)
        );

        $flushDataHandler = $this->createMock(BatchFlushDataHandlerInterface::class);
        $this->flushDataHandlerFactoryRegistry->expects(self::once())
            ->method('getFactory')
            ->with(self::ENTITY_CLASS)
            ->willReturn($this->flushDataHandlerFactory);
        $this->flushDataHandlerFactory->expects(self::once())
            ->method('createHandler')
            ->with(self::ENTITY_CLASS)
            ->willReturn($flushDataHandler);

        $flushDataHandler->expects(self::at(0))
            ->method('startFlushData')
            ->with($items);
        $flushDataHandler->expects(self::at(1))
            ->method('flushData')
            ->with($items)
            ->willThrowException($exception);
        $flushDataHandler->expects(self::at(2))
            ->method('finishFlushData')
            ->with($items);

        $this->logger->expects(self::never())
            ->method('error');

        $this->context->setBatchItems($items);
        $this->initializeProcessedItemStatuses($this->context);
        $this->processor->process($this->context);
        self::assertTrue($this->context->isProcessed(FlushData::OPERATION_NAME));
        self::assertSame($flushDataHandler, $this->context->getFlushDataHandler());
        self::assertEquals(
            [BatchUpdateItemStatus::HAS_ERRORS, BatchUpdateItemStatus::HAS_ERRORS],
            $this->context->getProcessedItemStatuses()
        );
        self::assertFalse($this->context->hasErrors());
        self::assertFalse($item1->getContext()->hasErrors());
        self::assertFalse($item2->getContext()->hasErrors());
    }

    public function testProcessWhenExceptionOccurredInFlushDataAndOneBatchItemsInContext()
    {
        $operationId = 123;
        $chunkFileName = 'test.json';

        $item1 = $this->getBatchUpdateItem(0);
        $item1->getContext()->setClassName(self::ENTITY_CLASS);
        $items = [$item1];

        $exception = new \Exception('flushData exception');

        $flushDataHandler = $this->createMock(BatchFlushDataHandlerInterface::class);
        $this->flushDataHandlerFactoryRegistry->expects(self::once())
            ->method('getFactory')
            ->with(self::ENTITY_CLASS)
            ->willReturn($this->flushDataHandlerFactory);
        $this->flushDataHandlerFactory->expects(self::once())
            ->method('createHandler')
            ->with(self::ENTITY_CLASS)
            ->willReturn($flushDataHandler);

        $flushDataHandler->expects(self::at(0))
            ->method('startFlushData')
            ->with($items);
        $flushDataHandler->expects(self::at(1))
            ->method('flushData')
            ->with($items)
            ->willThrowException($exception);
        $flushDataHandler->expects(self::at(2))
            ->method('finishFlushData')
            ->with($items);

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Unexpected error occurred when flushing data for a batch operation chunk.',
                ['operationId' => $operationId, 'chunkFile' => $chunkFileName, 'exception' => $exception]
            );

        $this->context->setOperationId($operationId);
        $this->context->setFile(new ChunkFile($chunkFileName, 0, 0, 'data'));
        $this->context->setBatchItems($items);
        $this->initializeProcessedItemStatuses($this->context);
        $this->processor->process($this->context);
        self::assertTrue($this->context->isProcessed(FlushData::OPERATION_NAME));
        self::assertSame($flushDataHandler, $this->context->getFlushDataHandler());
        self::assertEquals(
            [BatchUpdateItemStatus::HAS_ERRORS],
            $this->context->getProcessedItemStatuses()
        );
        self::assertFalse($this->context->hasErrors());
        self::assertTrue($item1->getContext()->hasErrors());
        self::assertEquals(
            [Error::createByException($exception)],
            $item1->getContext()->getErrors()
        );
    }

    public function testProcessWhenUniqueConstraintViolationExceptionOccurredInFlushDataAndOneBatchItemsInContext()
    {
        $item1 = $this->getBatchUpdateItem(0);
        $item1->getContext()->setClassName(self::ENTITY_CLASS);
        $items = [$item1];

        $exception = new UniqueConstraintViolationException(
            'flushData exception',
            $this->createMock(DriverException::class)
        );

        $flushDataHandler = $this->createMock(BatchFlushDataHandlerInterface::class);
        $this->flushDataHandlerFactoryRegistry->expects(self::once())
            ->method('getFactory')
            ->with(self::ENTITY_CLASS)
            ->willReturn($this->flushDataHandlerFactory);
        $this->flushDataHandlerFactory->expects(self::once())
            ->method('createHandler')
            ->with(self::ENTITY_CLASS)
            ->willReturn($flushDataHandler);

        $flushDataHandler->expects(self::at(0))
            ->method('startFlushData')
            ->with($items);
        $flushDataHandler->expects(self::at(1))
            ->method('flushData')
            ->with($items)
            ->willThrowException($exception);
        $flushDataHandler->expects(self::at(2))
            ->method('finishFlushData')
            ->with($items);

        $this->context->setBatchItems($items);
        $this->initializeProcessedItemStatuses($this->context);
        $this->processor->process($this->context);
        self::assertTrue($this->context->isProcessed(FlushData::OPERATION_NAME));
        self::assertSame($flushDataHandler, $this->context->getFlushDataHandler());
        self::assertEquals(
            [BatchUpdateItemStatus::HAS_ERRORS],
            $this->context->getProcessedItemStatuses()
        );
        self::assertFalse($this->context->hasErrors());
        self::assertTrue($item1->getContext()->hasErrors());
        self::assertEquals(
            [Error::createConflictValidationError('The entity already exists')->setInnerException($exception)],
            $item1->getContext()->getErrors()
        );
    }

    public function testProcessWhenSomeItemsHaveErrorsFoundBeforeExecutionOfFlushData()
    {
        $item1 = $this->getBatchUpdateItem(0);
        $item2 = $this->getBatchUpdateItem(1);
        $item1->getContext()->setClassName(self::ENTITY_CLASS);
        $item2->getContext()->setClassName(self::ENTITY_CLASS);
        $items = [$item1, $item2];
        $processedItemStatuses = [
            $item1->getIndex() => BatchUpdateItemStatus::HAS_ERRORS,
            $item2->getIndex() => BatchUpdateItemStatus::NOT_PROCESSED,
        ];
        $itemsToProcess = [$item2];
        $expectedProcessedItemStatuses = [
            $item1->getIndex() => BatchUpdateItemStatus::HAS_ERRORS,
            $item2->getIndex() => BatchUpdateItemStatus::NO_ERRORS,
        ];

        $flushDataHandler = $this->createMock(BatchFlushDataHandlerInterface::class);
        $this->flushDataHandlerFactoryRegistry->expects(self::once())
            ->method('getFactory')
            ->with(self::ENTITY_CLASS)
            ->willReturn($this->flushDataHandlerFactory);
        $this->flushDataHandlerFactory->expects(self::once())
            ->method('createHandler')
            ->with(self::ENTITY_CLASS)
            ->willReturn($flushDataHandler);

        $flushDataHandler->expects(self::at(0))
            ->method('startFlushData')
            ->with($itemsToProcess);
        $flushDataHandler->expects(self::at(1))
            ->method('flushData')
            ->with($itemsToProcess);
        $flushDataHandler->expects(self::at(2))
            ->method('finishFlushData')
            ->with($itemsToProcess);

        $this->context->setBatchItems($items);
        $this->context->setProcessedItemStatuses($processedItemStatuses);
        $this->processor->process($this->context);
        self::assertTrue($this->context->isProcessed(FlushData::OPERATION_NAME));
        self::assertSame($flushDataHandler, $this->context->getFlushDataHandler());
        self::assertEquals($expectedProcessedItemStatuses, $this->context->getProcessedItemStatuses());
        self::assertNull($this->context->getFailedGroup());
        self::assertSame([], $this->context->getSkippedGroups());
        self::assertFalse($this->context->hasErrors());
        self::assertFalse($item1->getContext()->hasErrors());
        self::assertFalse($item2->getContext()->hasErrors());
    }

    public function testProcessWhenAllItemsHaveErrorsFoundBeforeExecutionOfFlushData()
    {
        $item1 = $this->getBatchUpdateItem(0);
        $item2 = $this->getBatchUpdateItem(1);
        $item1->getContext()->setClassName(self::ENTITY_CLASS);
        $item2->getContext()->setClassName(self::ENTITY_CLASS);
        $items = [$item1, $item2];
        $processedItemStatuses = [
            $item1->getIndex() => BatchUpdateItemStatus::HAS_ERRORS,
            $item2->getIndex() => BatchUpdateItemStatus::HAS_ERRORS,
        ];

        $this->flushDataHandlerFactoryRegistry->expects(self::never())
            ->method('getFactory');
        $this->flushDataHandlerFactory->expects(self::never())
            ->method('createHandler');

        $this->context->setBatchItems($items);
        $this->context->setProcessedItemStatuses($processedItemStatuses);
        $this->processor->process($this->context);
        self::assertTrue($this->context->isProcessed(FlushData::OPERATION_NAME));
        self::assertNull($this->context->getFlushDataHandler());
        self::assertEquals($processedItemStatuses, $this->context->getProcessedItemStatuses());
        self::assertNull($this->context->getFailedGroup());
        self::assertSame([], $this->context->getSkippedGroups());
        self::assertFalse($this->context->hasErrors());
        self::assertFalse($item1->getContext()->hasErrors());
        self::assertFalse($item2->getContext()->hasErrors());
    }
}
