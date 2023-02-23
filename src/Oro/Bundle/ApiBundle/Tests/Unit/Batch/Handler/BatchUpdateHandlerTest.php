<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Handler;

use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateHandler;
use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateItem;
use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateItemStatus;
use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateRequest;
use Oro\Bundle\ApiBundle\Batch\Model\ChunkFile;
use Oro\Bundle\ApiBundle\Batch\Processor\BatchUpdateItemProcessor;
use Oro\Bundle\ApiBundle\Batch\Processor\BatchUpdateProcessor;
use Oro\Bundle\ApiBundle\Batch\Processor\Update\BatchUpdateContext;
use Oro\Bundle\ApiBundle\Batch\Processor\UpdateItem\BatchUpdateItemContext;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Request\ApiActionGroup;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Component\ChainProcessor\ProcessorBagInterface;
use PHPUnit\Framework\MockObject\Stub\ReturnCallback;

class BatchUpdateHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|BatchUpdateProcessor */
    private $processor;

    /** @var \PHPUnit\Framework\MockObject\MockObject|BatchUpdateItemProcessor */
    private $itemProcessor;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FileManager */
    private $fileManager;

    /** @var BatchUpdateHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->processor = $this->getMockBuilder(BatchUpdateProcessor::class)
            ->onlyMethods(['process'])
            ->setConstructorArgs([$this->createMock(ProcessorBagInterface::class), 'batch_update'])
            ->getMock();
        $this->itemProcessor = $this->getMockBuilder(BatchUpdateItemProcessor::class)
            ->onlyMethods(['process'])
            ->setConstructorArgs([$this->createMock(ProcessorBagInterface::class), 'batch_update_item'])
            ->getMock();
        $this->fileManager = $this->createMock(FileManager::class);

        $this->handler = new BatchUpdateHandler($this->processor, $this->itemProcessor);
    }

    private function assertBatchUpdateContext(BatchUpdateContext $context, BatchUpdateRequest $request)
    {
        self::assertEquals($request->getVersion(), $context->getVersion());
        self::assertEquals($request->getRequestType(), $context->getRequestType());
        self::assertEquals($request->getOperationId(), $context->getOperationId());
        self::assertSame($request->getFileManager(), $context->getFileManager());
        self::assertSame($request->getFile(), $context->getFile());
        self::assertEquals($request->getSupportedEntityClasses(), $context->getSupportedEntityClasses());
        self::assertTrue($context->isSoftErrorsHandling());
    }

    public function testHandle()
    {
        $version = '1.2';
        $requestType = new RequestType(['test']);
        $operationId = 123;
        $supportedEntityClasses = ['Test\Entity'];
        $file = new ChunkFile('chunk1', 0, 0, 'data');
        $request = new BatchUpdateRequest(
            $version,
            $requestType,
            $operationId,
            $supportedEntityClasses,
            $file,
            $this->fileManager
        );

        $records = [['key' => 'val1']];
        $processedItemStatuses = [BatchUpdateItemStatus::NO_ERRORS];

        $this->itemProcessor->expects(self::exactly(2))
            ->method('process')
            ->willReturnOnConsecutiveCalls(
                new ReturnCallback(function (BatchUpdateItemContext $context) use ($request, $records) {
                    self::assertEquals($request->getVersion(), $context->getVersion());
                    self::assertEquals($request->getRequestType(), $context->getRequestType());
                    self::assertEquals($request->getSupportedEntityClasses(), $context->getSupportedEntityClasses());
                    self::assertEquals(ApiActionGroup::INITIALIZE, $context->getFirstGroup());
                    self::assertEquals(ApiActionGroup::INITIALIZE, $context->getLastGroup());
                    self::assertEquals($records[0], $context->getRequestData());
                }),
                new ReturnCallback(function (BatchUpdateItemContext $context) use ($request) {
                    self::assertEquals($request->getVersion(), $context->getVersion());
                    self::assertEquals($request->getRequestType(), $context->getRequestType());
                    self::assertEquals($request->getSupportedEntityClasses(), $context->getSupportedEntityClasses());
                    self::assertEquals(ApiActionGroup::TRANSFORM_DATA, $context->getFirstGroup());
                    self::assertEquals(ApiActionGroup::TRANSFORM_DATA, $context->getLastGroup());
                })
            );
        $this->processor->expects(self::exactly(4))
            ->method('process')
            ->willReturnOnConsecutiveCalls(
                new ReturnCallback(function (BatchUpdateContext $context) use ($request, $records) {
                    $this->assertBatchUpdateContext($context, $request);
                    self::assertEquals(ApiActionGroup::INITIALIZE, $context->getFirstGroup());
                    self::assertEquals(ApiActionGroup::INITIALIZE, $context->getLastGroup());
                    $context->setResult($records);
                }),
                new ReturnCallback(function (BatchUpdateContext $context) use ($request, $processedItemStatuses) {
                    $this->assertBatchUpdateContext($context, $request);
                    self::assertEquals(ApiActionGroup::SAVE_DATA, $context->getFirstGroup());
                    self::assertEquals(ApiActionGroup::SAVE_DATA, $context->getLastGroup());
                    $items = $context->getBatchItems();
                    self::assertCount(1, $items);
                    self::assertInstanceOf(BatchUpdateItem::class, $items[0]);
                    $context->setProcessedItemStatuses($processedItemStatuses);
                    $context->getSummary()->incrementWriteCount();
                    $context->getSummary()->incrementCreateCount();
                }),
                new ReturnCallback(function (BatchUpdateContext $context) use ($request) {
                    $this->assertBatchUpdateContext($context, $request);
                    self::assertEquals(ApiActionGroup::FINALIZE, $context->getFirstGroup());
                    self::assertEquals(ApiActionGroup::FINALIZE, $context->getLastGroup());
                }),
                new ReturnCallback(function (BatchUpdateContext $context) use ($request) {
                    $this->assertBatchUpdateContext($context, $request);
                    self::assertEquals(ApiActionGroup::SAVE_ERRORS, $context->getFirstGroup());
                    self::assertEquals(ApiActionGroup::SAVE_ERRORS, $context->getLastGroup());
                })
            );

        $response = $this->handler->handle($request);
        self::assertFalse($response->hasUnexpectedErrors(), 'UnexpectedErrors');
        self::assertFalse($response->isRetryAgain(), 'RetryAgain');
        self::assertNull($response->getRetryReason(), 'RetryReason');
        self::assertSame($records, $response->getData(), 'Data');
        self::assertSame($processedItemStatuses, $response->getProcessedItemStatuses(), 'ProcessedItemStatuses');
        self::assertSame(1, $response->getSummary()->getReadCount(), 'Summary.ReadCount');
        self::assertSame(1, $response->getSummary()->getWriteCount(), 'Summary.WriteCount');
        self::assertSame(0, $response->getSummary()->getErrorCount(), 'Summary.ErrorCount');
        self::assertSame(1, $response->getSummary()->getCreateCount(), 'Summary.CreateCount');
        self::assertSame(0, $response->getSummary()->getUpdateCount(), 'Summary.UpdateCount');
    }

    public function testHandleWhenInitializeGroupFoundsErrors()
    {
        $version = '1.2';
        $requestType = new RequestType(['test']);
        $operationId = 123;
        $supportedEntityClasses = ['Test\Entity'];
        $file = new ChunkFile('chunk1', 0, 0, 'data');
        $request = new BatchUpdateRequest(
            $version,
            $requestType,
            $operationId,
            $supportedEntityClasses,
            $file,
            $this->fileManager
        );

        $this->itemProcessor->expects(self::never())
            ->method('process');
        $this->processor->expects(self::exactly(2))
            ->method('process')
            ->willReturnOnConsecutiveCalls(
                new ReturnCallback(function (BatchUpdateContext $context) use ($request) {
                    $this->assertBatchUpdateContext($context, $request);
                    self::assertEquals(ApiActionGroup::INITIALIZE, $context->getFirstGroup());
                    self::assertEquals(ApiActionGroup::INITIALIZE, $context->getLastGroup());
                    $context->addError(Error::create('some error'));
                }),
                new ReturnCallback(function (BatchUpdateContext $context) use ($request) {
                    $this->assertBatchUpdateContext($context, $request);
                    self::assertEquals(ApiActionGroup::SAVE_ERRORS, $context->getFirstGroup());
                    self::assertEquals(ApiActionGroup::SAVE_ERRORS, $context->getLastGroup());
                    $context->resetErrors();
                    $context->setHasUnexpectedErrors(true);
                })
            );

        $response = $this->handler->handle($request);
        self::assertTrue($response->hasUnexpectedErrors(), 'UnexpectedErrors');
        self::assertFalse($response->isRetryAgain(), 'RetryAgain');
        self::assertNull($response->getRetryReason(), 'RetryReason');
        self::assertSame([], $response->getData(), 'Data');
        self::assertSame([], $response->getProcessedItemStatuses(), 'ProcessedItemStatuses');
        self::assertSame(0, $response->getSummary()->getReadCount(), 'Summary.ReadCount');
        self::assertSame(0, $response->getSummary()->getWriteCount(), 'Summary.WriteCount');
        self::assertSame(0, $response->getSummary()->getErrorCount(), 'Summary.ErrorCount');
        self::assertSame(0, $response->getSummary()->getCreateCount(), 'Summary.CreateCount');
        self::assertSame(0, $response->getSummary()->getUpdateCount(), 'Summary.UpdateCount');
    }

    public function testHandleWhenInitializeGroupRequestsRetry()
    {
        $version = '1.2';
        $requestType = new RequestType(['test']);
        $operationId = 123;
        $supportedEntityClasses = ['Test\Entity'];
        $file = new ChunkFile('chunk1', 0, 0, 'data');
        $request = new BatchUpdateRequest(
            $version,
            $requestType,
            $operationId,
            $supportedEntityClasses,
            $file,
            $this->fileManager
        );

        $this->itemProcessor->expects(self::never())
            ->method('process');
        $this->processor->expects(self::exactly(2))
            ->method('process')
            ->willReturnOnConsecutiveCalls(
                new ReturnCallback(function (BatchUpdateContext $context) use ($request) {
                    $this->assertBatchUpdateContext($context, $request);
                    self::assertEquals(ApiActionGroup::INITIALIZE, $context->getFirstGroup());
                    self::assertEquals(ApiActionGroup::INITIALIZE, $context->getLastGroup());
                    $context->skipGroup(ApiActionGroup::INITIALIZE);
                    $context->setRetryReason('test retry reason');
                }),
                new ReturnCallback(function (BatchUpdateContext $context) use ($request) {
                    $this->assertBatchUpdateContext($context, $request);
                    self::assertEquals(ApiActionGroup::SAVE_ERRORS, $context->getFirstGroup());
                    self::assertEquals(ApiActionGroup::SAVE_ERRORS, $context->getLastGroup());
                })
            );

        $response = $this->handler->handle($request);
        self::assertFalse($response->hasUnexpectedErrors(), 'UnexpectedErrors');
        self::assertTrue($response->isRetryAgain(), 'RetryAgain');
        self::assertSame('test retry reason', $response->getRetryReason(), 'RetryReason');
        self::assertSame([], $response->getData(), 'Data');
        self::assertSame([], $response->getProcessedItemStatuses(), 'ProcessedItemStatuses');
        self::assertSame(0, $response->getSummary()->getReadCount(), 'Summary.ReadCount');
        self::assertSame(0, $response->getSummary()->getWriteCount(), 'Summary.WriteCount');
        self::assertSame(0, $response->getSummary()->getErrorCount(), 'Summary.ErrorCount');
        self::assertSame(0, $response->getSummary()->getCreateCount(), 'Summary.CreateCount');
        self::assertSame(0, $response->getSummary()->getUpdateCount(), 'Summary.UpdateCount');
    }

    public function testHandleWhenSaveDataGroupFoundsErrors()
    {
        $version = '1.2';
        $requestType = new RequestType(['test']);
        $operationId = 123;
        $supportedEntityClasses = ['Test\Entity'];
        $file = new ChunkFile('chunk1', 0, 0, 'data');
        $request = new BatchUpdateRequest(
            $version,
            $requestType,
            $operationId,
            $supportedEntityClasses,
            $file,
            $this->fileManager
        );

        $records = [['key' => 'val1']];

        $this->itemProcessor->expects(self::exactly(2))
            ->method('process')
            ->willReturnOnConsecutiveCalls(
                new ReturnCallback(function (BatchUpdateItemContext $context) use ($request, $records) {
                    self::assertEquals($request->getVersion(), $context->getVersion());
                    self::assertEquals($request->getRequestType(), $context->getRequestType());
                    self::assertEquals($request->getSupportedEntityClasses(), $context->getSupportedEntityClasses());
                    self::assertEquals(ApiActionGroup::INITIALIZE, $context->getFirstGroup());
                    self::assertEquals(ApiActionGroup::INITIALIZE, $context->getLastGroup());
                    self::assertEquals($records[0], $context->getRequestData());
                }),
                new ReturnCallback(function (BatchUpdateItemContext $context) use ($request) {
                    self::assertEquals($request->getVersion(), $context->getVersion());
                    self::assertEquals($request->getRequestType(), $context->getRequestType());
                    self::assertEquals($request->getSupportedEntityClasses(), $context->getSupportedEntityClasses());
                    self::assertEquals(ApiActionGroup::TRANSFORM_DATA, $context->getFirstGroup());
                    self::assertEquals(ApiActionGroup::TRANSFORM_DATA, $context->getLastGroup());
                })
            );
        $this->processor->expects(self::exactly(3))
            ->method('process')
            ->willReturnOnConsecutiveCalls(
                new ReturnCallback(function (BatchUpdateContext $context) use ($request, $records) {
                    $this->assertBatchUpdateContext($context, $request);
                    self::assertEquals(ApiActionGroup::INITIALIZE, $context->getFirstGroup());
                    self::assertEquals(ApiActionGroup::INITIALIZE, $context->getLastGroup());
                    $context->setResult($records);
                }),
                new ReturnCallback(function (BatchUpdateContext $context) use ($request) {
                    $this->assertBatchUpdateContext($context, $request);
                    self::assertEquals(ApiActionGroup::SAVE_DATA, $context->getFirstGroup());
                    self::assertEquals(ApiActionGroup::SAVE_DATA, $context->getLastGroup());
                    $items = $context->getBatchItems();
                    self::assertCount(1, $items);
                    self::assertInstanceOf(BatchUpdateItem::class, $items[0]);
                    $context->addError(Error::create('some error'));
                }),
                new ReturnCallback(function (BatchUpdateContext $context) use ($request) {
                    $this->assertBatchUpdateContext($context, $request);
                    self::assertEquals(ApiActionGroup::SAVE_ERRORS, $context->getFirstGroup());
                    self::assertEquals(ApiActionGroup::SAVE_ERRORS, $context->getLastGroup());
                    $context->resetErrors();
                    $context->setHasUnexpectedErrors(true);
                })
            );

        $response = $this->handler->handle($request);
        self::assertTrue($response->hasUnexpectedErrors(), 'UnexpectedErrors');
        self::assertFalse($response->isRetryAgain(), 'RetryAgain');
        self::assertNull($response->getRetryReason(), 'RetryReason');
        self::assertSame($records, $response->getData(), 'Data');
        self::assertSame([], $response->getProcessedItemStatuses(), 'ProcessedItemStatuses');
        self::assertSame(1, $response->getSummary()->getReadCount(), 'Summary.ReadCount');
        self::assertSame(0, $response->getSummary()->getWriteCount(), 'Summary.WriteCount');
        self::assertSame(0, $response->getSummary()->getErrorCount(), 'Summary.ErrorCount');
        self::assertSame(0, $response->getSummary()->getCreateCount(), 'Summary.CreateCount');
        self::assertSame(0, $response->getSummary()->getUpdateCount(), 'Summary.UpdateCount');
    }

    public function testHandleWhenSaveDataGroupRequestsRetry()
    {
        $version = '1.2';
        $requestType = new RequestType(['test']);
        $operationId = 123;
        $supportedEntityClasses = ['Test\Entity'];
        $file = new ChunkFile('chunk1', 0, 0, 'data');
        $request = new BatchUpdateRequest(
            $version,
            $requestType,
            $operationId,
            $supportedEntityClasses,
            $file,
            $this->fileManager
        );

        $records = [['key' => 'val1']];

        $this->itemProcessor->expects(self::exactly(2))
            ->method('process')
            ->willReturnOnConsecutiveCalls(
                new ReturnCallback(function (BatchUpdateItemContext $context) use ($request, $records) {
                    self::assertEquals($request->getVersion(), $context->getVersion());
                    self::assertEquals($request->getRequestType(), $context->getRequestType());
                    self::assertEquals($request->getSupportedEntityClasses(), $context->getSupportedEntityClasses());
                    self::assertEquals(ApiActionGroup::INITIALIZE, $context->getFirstGroup());
                    self::assertEquals(ApiActionGroup::INITIALIZE, $context->getLastGroup());
                    self::assertEquals($records[0], $context->getRequestData());
                }),
                new ReturnCallback(function (BatchUpdateItemContext $context) use ($request) {
                    self::assertEquals($request->getVersion(), $context->getVersion());
                    self::assertEquals($request->getRequestType(), $context->getRequestType());
                    self::assertEquals($request->getSupportedEntityClasses(), $context->getSupportedEntityClasses());
                    self::assertEquals(ApiActionGroup::TRANSFORM_DATA, $context->getFirstGroup());
                    self::assertEquals(ApiActionGroup::TRANSFORM_DATA, $context->getLastGroup());
                })
            );
        $this->processor->expects(self::exactly(3))
            ->method('process')
            ->willReturnOnConsecutiveCalls(
                new ReturnCallback(function (BatchUpdateContext $context) use ($request, $records) {
                    $this->assertBatchUpdateContext($context, $request);
                    self::assertEquals(ApiActionGroup::INITIALIZE, $context->getFirstGroup());
                    self::assertEquals(ApiActionGroup::INITIALIZE, $context->getLastGroup());
                    $context->setResult($records);
                }),
                new ReturnCallback(function (BatchUpdateContext $context) use ($request) {
                    $this->assertBatchUpdateContext($context, $request);
                    self::assertEquals(ApiActionGroup::SAVE_DATA, $context->getFirstGroup());
                    self::assertEquals(ApiActionGroup::SAVE_DATA, $context->getLastGroup());
                    $items = $context->getBatchItems();
                    self::assertCount(1, $items);
                    self::assertInstanceOf(BatchUpdateItem::class, $items[0]);
                    $context->skipGroup(ApiActionGroup::SAVE_DATA);
                    $context->setRetryReason('test retry reason');
                }),
                new ReturnCallback(function (BatchUpdateContext $context) use ($request) {
                    $this->assertBatchUpdateContext($context, $request);
                    self::assertEquals(ApiActionGroup::SAVE_ERRORS, $context->getFirstGroup());
                    self::assertEquals(ApiActionGroup::SAVE_ERRORS, $context->getLastGroup());
                    $context->resetErrors();
                })
            );

        $response = $this->handler->handle($request);
        self::assertFalse($response->hasUnexpectedErrors(), 'UnexpectedErrors');
        self::assertTrue($response->isRetryAgain(), 'RetryAgain');
        self::assertEquals('test retry reason', $response->getRetryReason(), 'RetryReason');
        self::assertSame($records, $response->getData(), 'Data');
        self::assertSame([], $response->getProcessedItemStatuses(), 'ProcessedItemStatuses');
        self::assertSame(1, $response->getSummary()->getReadCount(), 'Summary.ReadCount');
        self::assertSame(0, $response->getSummary()->getWriteCount(), 'Summary.WriteCount');
        self::assertSame(0, $response->getSummary()->getErrorCount(), 'Summary.ErrorCount');
        self::assertSame(0, $response->getSummary()->getCreateCount(), 'Summary.CreateCount');
        self::assertSame(0, $response->getSummary()->getUpdateCount(), 'Summary.UpdateCount');
    }

    public function testHandleWhenInitializeItemGroupFoundsErrors()
    {
        $version = '1.2';
        $requestType = new RequestType(['test']);
        $operationId = 123;
        $supportedEntityClasses = ['Test\Entity'];
        $file = new ChunkFile('chunk1', 0, 0, 'data');
        $request = new BatchUpdateRequest(
            $version,
            $requestType,
            $operationId,
            $supportedEntityClasses,
            $file,
            $this->fileManager
        );

        $records = [['key' => 'val1']];

        $this->itemProcessor->expects(self::once())
            ->method('process')
            ->willReturnCallback(function (BatchUpdateItemContext $context) use ($request, $records) {
                self::assertEquals($request->getVersion(), $context->getVersion());
                self::assertEquals($request->getRequestType(), $context->getRequestType());
                self::assertEquals($request->getSupportedEntityClasses(), $context->getSupportedEntityClasses());
                self::assertEquals(ApiActionGroup::INITIALIZE, $context->getFirstGroup());
                self::assertEquals(ApiActionGroup::INITIALIZE, $context->getLastGroup());
                self::assertEquals($records[0], $context->getRequestData());
                $context->addError(Error::create('some error'));
            });
        $this->processor->expects(self::exactly(4))
            ->method('process')
            ->willReturnOnConsecutiveCalls(
                new ReturnCallback(function (BatchUpdateContext $context) use ($request, $records) {
                    $this->assertBatchUpdateContext($context, $request);
                    self::assertEquals(ApiActionGroup::INITIALIZE, $context->getFirstGroup());
                    self::assertEquals(ApiActionGroup::INITIALIZE, $context->getLastGroup());
                    $context->setResult($records);
                }),
                new ReturnCallback(function (BatchUpdateContext $context) use ($request) {
                    $this->assertBatchUpdateContext($context, $request);
                    self::assertEquals(ApiActionGroup::SAVE_DATA, $context->getFirstGroup());
                    self::assertEquals(ApiActionGroup::SAVE_DATA, $context->getLastGroup());
                    $items = $context->getBatchItems();
                    self::assertCount(1, $items);
                    self::assertInstanceOf(BatchUpdateItem::class, $items[0]);
                }),
                new ReturnCallback(function (BatchUpdateContext $context) use ($request) {
                    $this->assertBatchUpdateContext($context, $request);
                    self::assertEquals(ApiActionGroup::FINALIZE, $context->getFirstGroup());
                    self::assertEquals(ApiActionGroup::FINALIZE, $context->getLastGroup());
                }),
                new ReturnCallback(function (BatchUpdateContext $context) use ($request) {
                    $this->assertBatchUpdateContext($context, $request);
                    self::assertEquals(ApiActionGroup::SAVE_ERRORS, $context->getFirstGroup());
                    self::assertEquals(ApiActionGroup::SAVE_ERRORS, $context->getLastGroup());
                })
            );

        $response = $this->handler->handle($request);
        self::assertFalse($response->hasUnexpectedErrors(), 'UnexpectedErrors');
        self::assertFalse($response->isRetryAgain(), 'RetryAgain');
        self::assertNull($response->getRetryReason(), 'RetryReason');
        self::assertSame($records, $response->getData(), 'Data');
        self::assertSame([], $response->getProcessedItemStatuses(), 'ProcessedItemStatuses');
        self::assertSame(1, $response->getSummary()->getReadCount(), 'Summary.ReadCount');
        self::assertSame(0, $response->getSummary()->getWriteCount(), 'Summary.WriteCount');
        self::assertSame(0, $response->getSummary()->getErrorCount(), 'Summary.ErrorCount');
        self::assertSame(0, $response->getSummary()->getCreateCount(), 'Summary.CreateCount');
        self::assertSame(0, $response->getSummary()->getUpdateCount(), 'Summary.UpdateCount');
    }
}
