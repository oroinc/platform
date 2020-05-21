<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Processor\Update;

use Oro\Bundle\ApiBundle\Batch\ErrorManager;
use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateItem;
use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateItemStatus;
use Oro\Bundle\ApiBundle\Batch\Model\BatchError;
use Oro\Bundle\ApiBundle\Batch\Model\ChunkFile;
use Oro\Bundle\ApiBundle\Batch\Processor\Update\SaveErrors;
use Oro\Bundle\ApiBundle\Batch\Processor\UpdateItem\BatchUpdateItemContext;
use Oro\Bundle\ApiBundle\Batch\RetryHelper;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\GaufretteBundle\FileManager;
use Psr\Log\LoggerInterface;

class SaveErrorsTest extends BatchUpdateProcessorTestCase
{
    private const ASYNC_OPERATION_ID = 123;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FileManager */
    private $fileManager;

    /** @var ChunkFile */
    private $file;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ErrorManager */
    private $errorManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|LoggerInterface */
    private $logger;

    /** @var SaveErrors */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fileManager = $this->createMock(FileManager::class);
        $this->file = new ChunkFile('api_chunk_1', 1, 0, 'data');
        $this->errorManager = $this->createMock(ErrorManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->processor = new SaveErrors($this->errorManager, new RetryHelper(), $this->logger);

        $this->context->setOperationId(self::ASYNC_OPERATION_ID);
        $this->context->setFileManager($this->fileManager);
        $this->context->setFile($this->file);
    }

    /**
     * @param int     $itemIndex
     * @param Error[] $itemErrors
     *
     * @return BatchUpdateItem
     */
    private function getBatchUpdateItem(int $itemIndex, array $itemErrors = []): BatchUpdateItem
    {
        $itemContext = new BatchUpdateItemContext();
        foreach ($itemErrors as $itemError) {
            $itemContext->addError($itemError);
        }

        $item = $this->createMock(BatchUpdateItem::class);
        $item->expects(self::any())
            ->method('getIndex')
            ->willReturn($itemIndex);
        $item->expects(self::any())
            ->method('getContext')
            ->willReturn($itemContext);

        return $item;
    }

    public function testProcessWhenNoErrorsInContext()
    {
        $this->errorManager->expects(self::never())
            ->method('writeErrors');

        $this->context->setBatchItems([]);
        $this->processor->process($this->context);
    }

    public function testProcessWhenHasErrorsInContext()
    {
        $error = Error::createValidationError('error1', 'error 1')
            ->setCode('error1_code')
            ->setInnerException(new \Exception('error 1 exception'));
        $errorToSave = BatchError::create($error->getTitle(), $error->getDetail())
            ->setStatusCode($error->getStatusCode())
            ->setCode($error->getCode())
            ->setInnerException($error->getInnerException());

        $this->errorManager->expects(self::once())
            ->method('writeErrors')
            ->with(
                self::identicalTo($this->fileManager),
                self::ASYNC_OPERATION_ID,
                [$errorToSave],
                self::identicalTo($this->file)
            );

        $this->logger->expects(self::never())
            ->method('error');

        $this->context->addError($error);
        $this->context->setBatchItems([]);
        $this->processor->process($this->context);
        self::assertTrue($this->context->hasUnexpectedErrors());
        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessWhenWriteErrorsFailed()
    {
        $error = Error::createValidationError('error1', 'error 1')
            ->setCode('error1_code')
            ->setInnerException(new \Exception('error 1 exception'));
        $errorToSave = BatchError::create($error->getTitle(), $error->getDetail())
            ->setStatusCode($error->getStatusCode())
            ->setCode($error->getCode())
            ->setInnerException($error->getInnerException());

        $exception = new \Exception('writeErrors exception');

        $this->errorManager->expects(self::once())
            ->method('writeErrors')
            ->with(
                self::identicalTo($this->fileManager),
                self::ASYNC_OPERATION_ID,
                [$errorToSave],
                self::identicalTo($this->file)
            )
            ->willThrowException($exception);

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Failed to save errors occurred when processing a batch operation chunk.',
                [
                    'operationId' => self::ASYNC_OPERATION_ID,
                    'chunkFile'   => $this->file->getFileName(),
                    'exception'   => $exception
                ]
            );

        $this->context->addError($error);
        $this->context->setBatchItems([]);
        $this->processor->process($this->context);
        self::assertTrue($this->context->hasUnexpectedErrors());
        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessWhenHasErrorsInBatchItemsContextButNoRawItemsAndProcessedItemStatuses()
    {
        $item1Index = 0;
        $item2Index = 1;
        $item1Error = Error::createValidationError('item1 error1', 'item1 error 1')
            ->setSource(ErrorSource::createByPointer('/data/0/attributes/name'));
        $item1ErrorToSave = BatchError::create($item1Error->getTitle(), $item1Error->getDetail())
            ->setStatusCode($item1Error->getStatusCode())
            ->setSource($item1Error->getSource())
            ->setItemIndex($item1Index);
        $item2Error = Error::createValidationError('item2 error1', 'item2 error 1')
            ->setSource(ErrorSource::createByPointer('/data/1/attributes/name'));
        $item2ErrorToSave = BatchError::create($item2Error->getTitle(), $item2Error->getDetail())
            ->setStatusCode($item2Error->getStatusCode())
            ->setSource($item2Error->getSource())
            ->setItemIndex($item2Index);

        $item1 = $this->getBatchUpdateItem($item1Index, [$item1Error]);
        $item2 = $this->getBatchUpdateItem($item2Index, [$item2Error]);

        $this->errorManager->expects(self::once())
            ->method('writeErrors')
            ->with(
                self::identicalTo($this->fileManager),
                self::ASYNC_OPERATION_ID,
                [$item1ErrorToSave, $item2ErrorToSave],
                self::identicalTo($this->file)
            );

        $this->context->setBatchItems([$item1, $item2]);
        $this->processor->process($this->context);
        self::assertFalse($this->context->hasUnexpectedErrors());
        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessWhenHasErrorsInBatchItemsContext()
    {
        $rawItems = [[], []];
        $processedItemStatuses = [
            BatchUpdateItemStatus::HAS_PERMANENT_ERRORS,
            BatchUpdateItemStatus::HAS_PERMANENT_ERRORS
        ];

        $item1Index = 0;
        $item2Index = 1;
        $item1Error = Error::createValidationError('item1 error1', 'item1 error 1')
            ->setSource(ErrorSource::createByPointer('/data/0/attributes/name'));
        $item1ErrorToSave = BatchError::create($item1Error->getTitle(), $item1Error->getDetail())
            ->setStatusCode($item1Error->getStatusCode())
            ->setSource($item1Error->getSource())
            ->setItemIndex($item1Index);
        $item2Error = Error::createValidationError('item2 error1', 'item2 error 1')
            ->setSource(ErrorSource::createByPointer('/data/1/attributes/name'));
        $item2ErrorToSave = BatchError::create($item2Error->getTitle(), $item2Error->getDetail())
            ->setStatusCode($item2Error->getStatusCode())
            ->setSource($item2Error->getSource())
            ->setItemIndex($item2Index);

        $item1 = $this->getBatchUpdateItem($item1Index, [$item1Error]);
        $item2 = $this->getBatchUpdateItem($item2Index, [$item2Error]);

        $this->errorManager->expects(self::once())
            ->method('writeErrors')
            ->with(
                self::identicalTo($this->fileManager),
                self::ASYNC_OPERATION_ID,
                [$item1ErrorToSave, $item2ErrorToSave],
                self::identicalTo($this->file)
            );

        $this->context->setResult($rawItems);
        $this->context->setProcessedItemStatuses($processedItemStatuses);
        $this->context->setBatchItems([$item1, $item2]);
        $this->processor->process($this->context);
        self::assertFalse($this->context->hasUnexpectedErrors());
        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessWhenHasErrorsInBatchItemsContextButTheseBatchItemsWillBeProcessedOneMoreTime()
    {
        $rawItems = [[], []];
        $processedItemStatuses = [
            BatchUpdateItemStatus::HAS_ERRORS,
            BatchUpdateItemStatus::HAS_ERRORS
        ];

        $item1Index = 0;
        $item2Index = 1;
        $item1Error = Error::createValidationError('item1 error1', 'item1 error 1')
            ->setSource(ErrorSource::createByPointer('/data/0/attributes/name'));
        $item2Error = Error::createValidationError('item2 error1', 'item2 error 1')
            ->setSource(ErrorSource::createByPointer('/data/1/attributes/name'));

        $item1 = $this->getBatchUpdateItem($item1Index, [$item1Error]);
        $item2 = $this->getBatchUpdateItem($item2Index, [$item2Error]);

        $this->errorManager->expects(self::never())
            ->method('writeErrors');

        $this->context->setResult($rawItems);
        $this->context->setProcessedItemStatuses($processedItemStatuses);
        $this->context->setBatchItems([$item1, $item2]);
        $this->processor->process($this->context);
        self::assertFalse($this->context->hasUnexpectedErrors());
        self::assertFalse($this->context->hasErrors());
    }
}
