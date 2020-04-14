<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Async;

use Oro\Bundle\ApiBundle\Batch\Async\Topics;
use Oro\Bundle\ApiBundle\Batch\Async\UpdateListProcessChunkMessageProcessor;
use Oro\Bundle\ApiBundle\Batch\Async\UpdateListProcessingHelper;
use Oro\Bundle\ApiBundle\Batch\Encoder\DataEncoderInterface;
use Oro\Bundle\ApiBundle\Batch\Encoder\DataEncoderRegistry;
use Oro\Bundle\ApiBundle\Batch\FileLockManager;
use Oro\Bundle\ApiBundle\Batch\FileNameProvider;
use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateHandler;
use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateItemStatus;
use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateResponse;
use Oro\Bundle\ApiBundle\Batch\Model\BatchSummary;
use Oro\Bundle\ApiBundle\Batch\Model\ChunkFile;
use Oro\Bundle\ApiBundle\Batch\RetryHelper;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class UpdateListProcessChunkMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|JobRunner */
    private $jobRunner;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FileManager */
    private $fileManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|BatchUpdateHandler */
    private $handler;

    /** @var \PHPUnit\Framework\MockObject\MockObject|DataEncoderRegistry */
    private $dataEncoderRegistry;

    /** @var \PHPUnit\Framework\MockObject\MockObject|UpdateListProcessingHelper */
    private $processingHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FileLockManager */
    private $fileLockManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|LoggerInterface */
    private $logger;

    /** @var UpdateListProcessChunkMessageProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->jobRunner = $this->createMock(JobRunner::class);
        $this->fileManager = $this->createMock(FileManager::class);
        $this->handler = $this->createMock(BatchUpdateHandler::class);
        $this->dataEncoderRegistry = $this->createMock(DataEncoderRegistry::class);
        $this->processingHelper = $this->createMock(UpdateListProcessingHelper::class);
        $this->fileLockManager = $this->createMock(FileLockManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->processor = new UpdateListProcessChunkMessageProcessor(
            $this->jobRunner,
            $this->fileManager,
            $this->handler,
            $this->dataEncoderRegistry,
            new RetryHelper(),
            $this->processingHelper,
            new FileNameProvider(),
            $this->fileLockManager,
            $this->logger
        );
    }

    /**
     * @param array $body
     * @param string $messageId
     *
     * @return MessageInterface
     */
    private function getMessage(array $body, string $messageId = '')
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::once())
            ->method('getBody')
            ->willReturn(JSON::encode($body));
        $message->expects(self::any())
            ->method('getMessageId')
            ->willReturn($messageId);

        return $message;
    }

    /**
     * @return SessionInterface
     */
    private function getSession()
    {
        return $this->createMock(SessionInterface::class);
    }

    private function createBatchSummaryFromArray(array $summaryData): BatchSummary
    {
        $summary = new BatchSummary();
        $summary->incrementReadCount($summaryData['readCount']);
        $summary->incrementWriteCount($summaryData['writeCount']);
        $summary->incrementErrorCount($summaryData['errorCount']);
        $summary->incrementCreateCount($summaryData['createCount']);
        $summary->incrementUpdateCount($summaryData['updateCount']);

        return $summary;
    }

    public function testGetSubscribedTopics()
    {
        self::assertEquals(
            [Topics::UPDATE_LIST_PROCESS_CHUNK],
            UpdateListProcessChunkMessageProcessor::getSubscribedTopics()
        );
    }

    public function testShouldRejectInvalidMessage()
    {
        $message = $this->getMessage(['key' => 'value']);

        $this->logger->expects(self::once())
            ->method('critical')
            ->with('Got invalid message.');

        $result = $this->processor->process($message, $this->getSession());

        self::assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldRejectWhenUnexpectedErrorOccurred()
    {
        $jobId = 456;
        $fileName = 'chunkFile';
        $message = $this->getMessage([
            'operationId'       => 123,
            'entityClass'       => 'Test\Entity',
            'requestType'       => ['testRequest'],
            'version'           => '1.1',
            'jobId'             => $jobId,
            'fileName'          => $fileName,
            'fileIndex'         => 10,
            'firstRecordOffset' => 0,
            'sectionName'       => 'test'
        ]);
        $processorAggregateTime = 11;
        $summaryData = [
            'readCount'   => 1,
            'writeCount'  => 0,
            'errorCount'  => 0,
            'createCount' => 0,
            'updateCount' => 0
        ];

        $job = new Job();
        $job->setData(['key' => 'value']);
        $job->setId($jobId);
        $expectedJobData = array_merge(
            $job->getData(),
            ['summary' => array_merge(['aggregateTime' => $processorAggregateTime], $summaryData)]
        );

        $this->jobRunner->expects(self::once())
            ->method('runDelayed')
            ->willReturnCallback(function ($name, $startCallback) use ($job) {
                return $startCallback($this->jobRunner, $job);
            });
        $this->handler->expects(self::once())
            ->method('handle')
            ->willReturn(new BatchUpdateResponse(
                [['key' => 'val1']],
                [BatchUpdateItemStatus::NOT_PROCESSED],
                $this->createBatchSummaryFromArray($summaryData),
                true
            ));

        $this->processingHelper->expects(self::once())
            ->method('calculateAggregateTime')
            ->with(self::isType('float'), 0)
            ->willReturn($processorAggregateTime);

        $this->processingHelper->expects(self::once())
            ->method('safeDeleteFile')
            ->with($fileName);

        $this->logger->expects(self::never())
            ->method(self::anything());

        $result = $this->processor->process($message, $this->getSession());

        self::assertEquals(MessageProcessorInterface::REJECT, $result);
        self::assertEquals($expectedJobData, $job->getData());
    }

    public function testShouldRejectWhenLoadChunkDataFailed()
    {
        $jobId = 456;
        $fileName = 'chunkFile';
        $message = $this->getMessage([
            'operationId'       => 123,
            'entityClass'       => 'Test\Entity',
            'requestType'       => ['testRequest'],
            'version'           => '1.1',
            'jobId'             => $jobId,
            'fileName'          => $fileName,
            'fileIndex'         => 10,
            'firstRecordOffset' => 0,
            'sectionName'       => 'test'
        ]);
        $processorAggregateTime = 11;
        $summaryData = [
            'readCount'   => 0,
            'writeCount'  => 0,
            'errorCount'  => 0,
            'createCount' => 0,
            'updateCount' => 0
        ];

        $job = new Job();
        $job->setData(['key' => 'value']);
        $job->setId($jobId);
        $expectedJobData = array_merge(
            $job->getData(),
            ['summary' => array_merge(['aggregateTime' => $processorAggregateTime], $summaryData)]
        );

        $this->jobRunner->expects(self::once())
            ->method('runDelayed')
            ->willReturnCallback(function ($name, $startCallback) use ($job) {
                return $startCallback($this->jobRunner, $job);
            });
        $this->handler->expects(self::once())
            ->method('handle')
            ->willReturn(new BatchUpdateResponse(
                [],
                [],
                $this->createBatchSummaryFromArray($summaryData),
                false
            ));

        $this->processingHelper->expects(self::once())
            ->method('calculateAggregateTime')
            ->with(self::isType('float'), 0)
            ->willReturn($processorAggregateTime);

        $this->processingHelper->expects(self::once())
            ->method('safeDeleteFile')
            ->with($fileName);

        $this->logger->expects(self::never())
            ->method(self::anything());

        $result = $this->processor->process($message, $this->getSession());

        self::assertEquals(MessageProcessorInterface::REJECT, $result);
        self::assertEquals($expectedJobData, $job->getData());
    }

    public function testShouldRejectWhenLoadedChunkDataWasNotProcessed()
    {
        $jobId = 456;
        $fileName = 'chunkFile';
        $message = $this->getMessage([
            'operationId'       => 123,
            'entityClass'       => 'Test\Entity',
            'requestType'       => ['testRequest'],
            'version'           => '1.1',
            'jobId'             => $jobId,
            'fileName'          => $fileName,
            'fileIndex'         => 10,
            'firstRecordOffset' => 0,
            'sectionName'       => 'test'
        ]);
        $processorAggregateTime = 11;
        $summaryData = [
            'readCount'   => 1,
            'writeCount'  => 0,
            'errorCount'  => 0,
            'createCount' => 0,
            'updateCount' => 0
        ];

        $job = new Job();
        $job->setData(['key' => 'value']);
        $job->setId($jobId);
        $expectedJobData = array_merge(
            $job->getData(),
            ['summary' => array_merge(['aggregateTime' => $processorAggregateTime], $summaryData)]
        );

        $this->jobRunner->expects(self::once())
            ->method('runDelayed')
            ->willReturnCallback(function ($name, $startCallback) use ($job) {
                return $startCallback($this->jobRunner, $job);
            });
        $this->handler->expects(self::once())
            ->method('handle')
            ->willReturn(new BatchUpdateResponse(
                [['key' => 'val1']],
                [],
                $this->createBatchSummaryFromArray($summaryData),
                false
            ));

        $this->processingHelper->expects(self::once())
            ->method('calculateAggregateTime')
            ->with(self::isType('float'), 0)
            ->willReturn($processorAggregateTime);

        $this->processingHelper->expects(self::once())
            ->method('safeDeleteFile')
            ->with($fileName);

        $this->logger->expects(self::never())
            ->method(self::anything());

        $result = $this->processor->process($message, $this->getSession());

        self::assertEquals(MessageProcessorInterface::REJECT, $result);
        self::assertEquals($expectedJobData, $job->getData());
    }

    public function testProcessOneItemInChunk()
    {
        $jobId = 456;
        $fileName = 'chunkFile';
        $message = $this->getMessage([
            'operationId'       => 123,
            'entityClass'       => 'Test\Entity',
            'requestType'       => ['testRequest'],
            'version'           => '1.1',
            'jobId'             => $jobId,
            'fileName'          => $fileName,
            'fileIndex'         => 10,
            'firstRecordOffset' => 0,
            'sectionName'       => 'test'
        ]);
        $processorAggregateTime = 11;
        $summaryData = [
            'readCount'   => 1,
            'writeCount'  => 1,
            'errorCount'  => 0,
            'createCount' => 1,
            'updateCount' => 0
        ];

        $job = new Job();
        $job->setData(['key' => 'value']);
        $job->setId($jobId);
        $expectedJobData = array_merge(
            $job->getData(),
            ['summary' => array_merge(['aggregateTime' => $processorAggregateTime], $summaryData)]
        );

        $this->jobRunner->expects(self::once())
            ->method('runDelayed')
            ->willReturnCallback(function ($name, $startCallback) use ($job) {
                return $startCallback($this->jobRunner, $job);
            });
        $this->handler->expects(self::once())
            ->method('handle')
            ->willReturn(new BatchUpdateResponse(
                [['key' => 'val1']],
                [BatchUpdateItemStatus::NO_ERRORS],
                $this->createBatchSummaryFromArray($summaryData),
                false
            ));

        $this->processingHelper->expects(self::exactly(2))
            ->method('calculateAggregateTime')
            ->with(self::isType('float'), 0)
            ->willReturn($processorAggregateTime);

        $this->processingHelper->expects(self::once())
            ->method('safeDeleteFile')
            ->with($fileName);

        $this->logger->expects(self::never())
            ->method(self::anything());

        $result = $this->processor->process($message, $this->getSession());

        self::assertEquals(MessageProcessorInterface::ACK, $result);
        self::assertEquals($expectedJobData, $job->getData());
    }

    public function testProcessSeveralItemsInChunk()
    {
        $jobId = 456;
        $fileName = 'chunkFile';
        $message = $this->getMessage([
            'operationId'       => 123,
            'entityClass'       => 'Test\Entity',
            'requestType'       => ['testRequest'],
            'version'           => '1.1',
            'jobId'             => $jobId,
            'fileName'          => $fileName,
            'fileIndex'         => 10,
            'firstRecordOffset' => 0,
            'sectionName'       => 'test'
        ]);
        $processorAggregateTime = 11;
        $summaryData = [
            'readCount'   => 2,
            'writeCount'  => 2,
            'errorCount'  => 0,
            'createCount' => 2,
            'updateCount' => 0
        ];
        $rawItems = [['key' => 'val1'], ['key' => 'val2']];
        $processedItemStatuses = [
            BatchUpdateItemStatus::NO_ERRORS,
            BatchUpdateItemStatus::NO_ERRORS
        ];

        $job = new Job();
        $job->setData(['key' => 'value']);
        $job->setId($jobId);
        $expectedJobData = array_merge(
            $job->getData(),
            ['summary' => array_merge(['aggregateTime' => $processorAggregateTime], $summaryData)]
        );

        $this->jobRunner->expects(self::once())
            ->method('runDelayed')
            ->willReturnCallback(function ($name, $startCallback) use ($job) {
                return $startCallback($this->jobRunner, $job);
            });
        $this->handler->expects(self::once())
            ->method('handle')
            ->willReturn(new BatchUpdateResponse(
                $rawItems,
                $processedItemStatuses,
                $this->createBatchSummaryFromArray($summaryData),
                false
            ));

        $this->processingHelper->expects(self::exactly(2))
            ->method('calculateAggregateTime')
            ->with(self::isType('float'), 0)
            ->willReturn($processorAggregateTime);

        $this->processingHelper->expects(self::once())
            ->method('safeDeleteFile')
            ->with($fileName);

        $this->logger->expects(self::never())
            ->method(self::anything());

        $result = $this->processor->process($message, $this->getSession());

        self::assertEquals(MessageProcessorInterface::ACK, $result);
        self::assertEquals($expectedJobData, $job->getData());
    }

    public function testProcessSeveralItemsInChunkAndHasItemsWithPermanentErrorsAndDataEncoderNotFound()
    {
        $jobId = 456;
        $requestType = ['testRequest'];
        $fileName = 'chunkFile';
        $message = $this->getMessage([
            'operationId'       => 123,
            'entityClass'       => 'Test\Entity',
            'requestType'       => $requestType,
            'version'           => '1.1',
            'jobId'             => $jobId,
            'fileName'          => $fileName,
            'fileIndex'         => 10,
            'firstRecordOffset' => 0,
            'sectionName'       => 'test'
        ]);
        $processorAggregateTime = 11;
        $summaryData = [
            'readCount'   => 2,
            'writeCount'  => 2,
            'errorCount'  => 0,
            'createCount' => 2,
            'updateCount' => 0
        ];
        $rawItems = [['key' => 'val1'], ['key' => 'val2']];
        $processedItemStatuses = [
            BatchUpdateItemStatus::NOT_PROCESSED,
            BatchUpdateItemStatus::HAS_PERMANENT_ERRORS
        ];

        $job = new Job();
        $job->setData(['key' => 'value']);
        $job->setId($jobId);
        $expectedJobData = array_merge(
            $job->getData(),
            ['summary' => array_merge(['aggregateTime' => $processorAggregateTime], $summaryData)]
        );

        $this->jobRunner->expects(self::once())
            ->method('runDelayed')
            ->willReturnCallback(function ($name, $startCallback) use ($job) {
                return $startCallback($this->jobRunner, $job);
            });
        $this->handler->expects(self::once())
            ->method('handle')
            ->willReturn(new BatchUpdateResponse(
                $rawItems,
                $processedItemStatuses,
                $this->createBatchSummaryFromArray($summaryData),
                false
            ));

        $this->dataEncoderRegistry->expects(self::once())
            ->method('getEncoder')
            ->with(new RequestType($requestType))
            ->willReturn(null);

        $this->processingHelper->expects(self::exactly(2))
            ->method('calculateAggregateTime')
            ->with(self::isType('float'), 0)
            ->willReturn($processorAggregateTime);

        $this->processingHelper->expects(self::once())
            ->method('safeDeleteFile')
            ->with($fileName);

        $this->logger->expects(self::once())
            ->method('error')
            ->with('Cannot get data encoder. Request Type: testRequest.');

        $result = $this->processor->process($message, $this->getSession());

        self::assertEquals(MessageProcessorInterface::REJECT, $result);
        self::assertEquals($expectedJobData, $job->getData());
    }

    public function testProcessSeveralItemsInChunkAndHasItemsWithPermanentErrorsAndCannotAcquireUpdateChunkCountLock()
    {
        $jobId = 456;
        $operationId = 123;
        $requestType = ['testRequest'];
        $fileName = 'chunkFile';
        $message = $this->getMessage([
            'operationId'       => $operationId,
            'entityClass'       => 'Test\Entity',
            'requestType'       => $requestType,
            'version'           => '1.1',
            'jobId'             => $jobId,
            'fileName'          => $fileName,
            'fileIndex'         => 10,
            'firstRecordOffset' => 0,
            'sectionName'       => 'test'
        ]);
        $processorAggregateTime = 11;
        $summaryData = [
            'readCount'   => 2,
            'writeCount'  => 2,
            'errorCount'  => 0,
            'createCount' => 2,
            'updateCount' => 0
        ];
        $rawItems = [['key' => 'val1'], ['key' => 'val2']];
        $processedItemStatuses = [
            BatchUpdateItemStatus::NOT_PROCESSED,
            BatchUpdateItemStatus::HAS_PERMANENT_ERRORS
        ];

        $job = new Job();
        $job->setData(['key' => 'value']);
        $job->setId($jobId);
        $expectedJobData = array_merge(
            $job->getData(),
            ['summary' => array_merge(['aggregateTime' => $processorAggregateTime], $summaryData)]
        );

        $this->jobRunner->expects(self::once())
            ->method('runDelayed')
            ->willReturnCallback(function ($name, $startCallback) use ($job) {
                return $startCallback($this->jobRunner, $job);
            });
        $this->handler->expects(self::once())
            ->method('handle')
            ->willReturn(new BatchUpdateResponse(
                $rawItems,
                $processedItemStatuses,
                $this->createBatchSummaryFromArray($summaryData),
                false
            ));

        $dataEncoder = $this->createMock(DataEncoderInterface::class);
        $this->dataEncoderRegistry->expects(self::once())
            ->method('getEncoder')
            ->with(new RequestType($requestType))
            ->willReturn($dataEncoder);
        $this->fileLockManager->expects(self::once())
            ->method('acquireLock')
            ->with(sprintf('api_%s_info.lock', $operationId))
            ->willReturn(false);
        $this->fileManager->expects(self::never())
            ->method('writeToStorage');
        $this->fileManager->expects(self::never())
            ->method('getFileContent');
        $this->fileManager->expects(self::never())
            ->method('deleteFile');
        $this->fileLockManager->expects(self::never())
            ->method('releaseLock');

        $this->processingHelper->expects(self::exactly(2))
            ->method('calculateAggregateTime')
            ->with(self::isType('float'), 0)
            ->willReturn($processorAggregateTime);

        $this->processingHelper->expects(self::once())
            ->method('safeDeleteFile')
            ->with($fileName);

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Cannot update the chunk count. Reason:'
                . ' Failed to update the info file "api_123_info" because the lock cannot be acquired.'
            );

        $result = $this->processor->process($message, $this->getSession());

        self::assertEquals(MessageProcessorInterface::REJECT, $result);
        self::assertEquals($expectedJobData, $job->getData());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessSeveralItemsInChunkAndHasItemsWithPermanentErrorsAndFailedUpdateChunkCount()
    {
        $jobId = 456;
        $operationId = 123;
        $requestType = ['testRequest'];
        $fileName = 'chunkFile';
        $message = $this->getMessage([
            'operationId'       => $operationId,
            'entityClass'       => 'Test\Entity',
            'requestType'       => $requestType,
            'version'           => '1.1',
            'jobId'             => $jobId,
            'fileName'          => $fileName,
            'fileIndex'         => 10,
            'firstRecordOffset' => 0,
            'sectionName'       => 'test'
        ]);
        $processorAggregateTime = 11;
        $summaryData = [
            'readCount'   => 2,
            'writeCount'  => 2,
            'errorCount'  => 0,
            'createCount' => 2,
            'updateCount' => 0
        ];
        $rawItems = [['key' => 'val1'], ['key' => 'val2']];
        $processedItemStatuses = [
            BatchUpdateItemStatus::NOT_PROCESSED,
            BatchUpdateItemStatus::HAS_PERMANENT_ERRORS
        ];

        $job = new Job();
        $job->setData(['key' => 'value']);
        $job->setId($jobId);
        $expectedJobData = array_merge(
            $job->getData(),
            ['summary' => array_merge(['aggregateTime' => $processorAggregateTime], $summaryData)]
        );

        $this->jobRunner->expects(self::once())
            ->method('runDelayed')
            ->willReturnCallback(function ($name, $startCallback) use ($job) {
                return $startCallback($this->jobRunner, $job);
            });
        $this->handler->expects(self::once())
            ->method('handle')
            ->willReturn(new BatchUpdateResponse(
                $rawItems,
                $processedItemStatuses,
                $this->createBatchSummaryFromArray($summaryData),
                false
            ));

        $exception = new \Exception('writeToStorage exception');

        $dataEncoder = $this->createMock(DataEncoderInterface::class);
        $this->dataEncoderRegistry->expects(self::once())
            ->method('getEncoder')
            ->with(new RequestType($requestType))
            ->willReturn($dataEncoder);
        $this->fileLockManager->expects(self::once())
            ->method('acquireLock')
            ->with(sprintf('api_%s_info.lock', $operationId))
            ->willReturn(true);
        $this->fileManager->expects(self::at(0))
            ->method('getFileContent')
            ->with(sprintf('api_%s_info', $operationId))
            ->willReturn('{"chunkCount":10}');
        $this->fileManager->expects(self::at(1))
            ->method('writeToStorage')
            ->with('{"chunkCount":11}', sprintf('api_%s_info', $operationId))
            ->willThrowException($exception);
        $this->fileLockManager->expects(self::once())
            ->method('releaseLock')
            ->with(sprintf('api_%s_info.lock', $operationId));

        $this->processingHelper->expects(self::exactly(2))
            ->method('calculateAggregateTime')
            ->with(self::isType('float'), 0)
            ->willReturn($processorAggregateTime);

        $this->processingHelper->expects(self::once())
            ->method('safeDeleteFile')
            ->with($fileName);

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Cannot update the chunk count. Reason: Failed to update the info file "api_123_info".',
                ['exception' => $exception]
            );

        $result = $this->processor->process($message, $this->getSession());

        self::assertEquals(MessageProcessorInterface::REJECT, $result);
        self::assertEquals($expectedJobData, $job->getData());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessSeveralItemsInChunkAndHasItemsWithPermanentErrors()
    {
        $jobId = 456;
        $operationId = 123;
        $requestType = ['testRequest'];
        $fileName = 'chunkFile';
        $body = [
            'operationId'       => $operationId,
            'entityClass'       => 'Test\Entity',
            'requestType'       => $requestType,
            'version'           => '1.1',
            'jobId'             => $jobId,
            'fileName'          => $fileName,
            'fileIndex'         => 10,
            'firstRecordOffset' => 0,
            'sectionName'       => 'test'
        ];
        $message = $this->getMessage($body);
        $processorAggregateTime = 11;
        $summaryData = [
            'readCount'   => 2,
            'writeCount'  => 2,
            'errorCount'  => 0,
            'createCount' => 2,
            'updateCount' => 0
        ];
        $rawItems = [['key' => 'val1'], ['key' => 'val2']];
        $processedItemStatuses = [
            BatchUpdateItemStatus::NOT_PROCESSED,
            BatchUpdateItemStatus::HAS_PERMANENT_ERRORS
        ];
        $chunkCount = 30;

        $job = new Job();
        $job->setName('oro:batch_api:123:chunk:1');
        $job->setData(['key' => 'value']);
        $job->setId($jobId);
        $expectedJobData = array_merge(
            $job->getData(),
            ['summary' => array_merge(['aggregateTime' => $processorAggregateTime], $summaryData)]
        );

        $this->jobRunner->expects(self::once())
            ->method('runDelayed')
            ->willReturnCallback(function ($name, $startCallback) use ($job) {
                return $startCallback($this->jobRunner, $job);
            });
        $this->handler->expects(self::once())
            ->method('handle')
            ->willReturn(new BatchUpdateResponse(
                $rawItems,
                $processedItemStatuses,
                $this->createBatchSummaryFromArray($summaryData),
                false
            ));

        $dataEncoder = $this->createMock(DataEncoderInterface::class);
        $this->dataEncoderRegistry->expects(self::once())
            ->method('getEncoder')
            ->with(new RequestType($requestType))
            ->willReturn($dataEncoder);
        $this->fileLockManager->expects(self::once())
            ->method('acquireLock')
            ->with(sprintf('api_%s_info.lock', $operationId))
            ->willReturn(true);
        $this->fileManager->expects(self::at(0))
            ->method('getFileContent')
            ->with(sprintf('api_%s_info', $operationId))
            ->willReturn(sprintf('{"chunkCount":%d}', $chunkCount));
        $this->fileManager->expects(self::at(1))
            ->method('writeToStorage')
            ->with(sprintf('{"chunkCount":%d}', $chunkCount + 1), sprintf('api_%s_info', $operationId));
        $this->fileLockManager->expects(self::once())
            ->method('releaseLock')
            ->with(sprintf('api_%s_info.lock', $operationId));
        $dataEncoder->expects(self::once())
            ->method('encodeItems')
            ->with([$rawItems[0]])
            ->willReturn('[{"key":"val1"}]');
        $this->fileManager->expects(self::at(2))
            ->method('writeToStorage')
            ->with('[{"key":"val1"}]', $fileName . '_0');
        $this->jobRunner->expects(self::once())
            ->method('createDelayed')
            ->with('oro:batch_api:123:chunk:1:1')
            ->willReturnCallback(function ($name, $startCallback) {
                $job = new Job();
                $job->setId(111);

                return $startCallback($this->jobRunner, $job);
            });
        $this->processingHelper->expects(self::once())
            ->method('sendProcessChunkMessage')
            ->with(
                $body,
                self::isInstanceOf(Job::class),
                new ChunkFile($fileName . '_0', $chunkCount, 0, 'test'),
                true
            );

        $this->processingHelper->expects(self::exactly(2))
            ->method('calculateAggregateTime')
            ->with(self::isType('float'), 0)
            ->willReturn($processorAggregateTime);

        $this->processingHelper->expects(self::once())
            ->method('safeDeleteFile')
            ->with($fileName);

        $this->logger->expects(self::never())
            ->method(self::anything());

        $result = $this->processor->process($message, $this->getSession());

        self::assertEquals(MessageProcessorInterface::ACK, $result);
        self::assertEquals($expectedJobData, $job->getData());
    }

    public function testProcessExtraChunk()
    {
        $jobId = 456;
        $fileName = 'chunkFile';
        $message = $this->getMessage([
            'operationId'       => 123,
            'entityClass'       => 'Test\Entity',
            'requestType'       => ['testRequest'],
            'version'           => '1.1',
            'jobId'             => $jobId,
            'fileName'          => $fileName,
            'fileIndex'         => 10,
            'firstRecordOffset' => 0,
            'sectionName'       => 'test',
            'extra_chunk'       => true
        ]);
        $processorAggregateTime = 11;
        $summaryData = [
            'readCount'   => 1,
            'writeCount'  => 1,
            'errorCount'  => 0,
            'createCount' => 1,
            'updateCount' => 0
        ];

        $job = new Job();
        $job->setData(['key' => 'value', 'extra_chunk' => true]);
        $job->setId($jobId);
        $expectedJobData = array_merge(
            $job->getData(),
            ['summary' => array_merge(['aggregateTime' => $processorAggregateTime], $summaryData)]
        );

        $this->jobRunner->expects(self::once())
            ->method('runDelayed')
            ->willReturnCallback(function ($name, $startCallback) use ($job) {
                return $startCallback($this->jobRunner, $job);
            });
        $this->handler->expects(self::once())
            ->method('handle')
            ->willReturn(new BatchUpdateResponse(
                [['key' => 'val1']],
                [BatchUpdateItemStatus::NO_ERRORS],
                $this->createBatchSummaryFromArray($summaryData),
                false
            ));

        $this->processingHelper->expects(self::exactly(2))
            ->method('calculateAggregateTime')
            ->with(self::isType('float'), 0)
            ->willReturn($processorAggregateTime);

        $this->processingHelper->expects(self::once())
            ->method('safeDeleteFile')
            ->with($fileName);

        $this->logger->expects(self::never())
            ->method(self::anything());

        $result = $this->processor->process($message, $this->getSession());

        self::assertEquals(MessageProcessorInterface::ACK, $result);
        self::assertEquals($expectedJobData, $job->getData());
    }

    /**
     * @dataProvider processWhenRetryRequestedDataProvider
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessWhenRetryRequested(string $jobName, string $retryJobName, bool $extraChunk)
    {
        $jobId = 456;
        $operationId = 123;
        $fileName = 'chunkFile';
        $fileIndex = 10;
        $firstRecordOffset = 100;
        $sectionName = 'test';
        $body = [
            'operationId'       => $operationId,
            'entityClass'       => 'Test\Entity',
            'requestType'       => ['testRequest'],
            'version'           => '1.1',
            'jobId'             => $jobId,
            'fileName'          => $fileName,
            'fileIndex'         => $fileIndex,
            'firstRecordOffset' => $firstRecordOffset,
            'sectionName'       => $sectionName
        ];
        if ($extraChunk) {
            $body['extra_chunk'] = true;
        }
        $message = $this->getMessage($body);
        $processorAggregateTime = 11;
        $summaryData = [
            'readCount'   => 1,
            'writeCount'  => 0,
            'errorCount'  => 0,
            'createCount' => 0,
            'updateCount' => 0
        ];
        $chunkCount = 30;

        $job = new Job();
        $job->setName($jobName);
        $job->setData(['key' => 'value']);
        $job->setId($jobId);
        $expectedJobData = array_merge(
            $job->getData(),
            ['summary' => array_merge(['aggregateTime' => $processorAggregateTime], $summaryData)]
        );
        if ($extraChunk) {
            $expectedJobData['extra_chunk'] = true;
        }

        $this->jobRunner->expects(self::once())
            ->method('runDelayed')
            ->willReturnCallback(function ($name, $startCallback) use ($job) {
                return $startCallback($this->jobRunner, $job);
            });
        $this->handler->expects(self::once())
            ->method('handle')
            ->willReturn(new BatchUpdateResponse(
                [['key' => 'val1']],
                [BatchUpdateItemStatus::NOT_PROCESSED],
                $this->createBatchSummaryFromArray($summaryData),
                false,
                'test retry reason'
            ));

        $this->fileLockManager->expects(self::once())
            ->method('acquireLock')
            ->with(sprintf('api_%s_info.lock', $operationId))
            ->willReturn(true);
        $this->fileManager->expects(self::at(0))
            ->method('getFileContent')
            ->with(sprintf('api_%s_info', $operationId))
            ->willReturn(sprintf('{"chunkCount":%d}', $chunkCount));
        $this->fileManager->expects(self::at(1))
            ->method('writeToStorage')
            ->with(sprintf('{"chunkCount":%d}', $chunkCount + 1), sprintf('api_%s_info', $operationId));
        $this->fileLockManager->expects(self::once())
            ->method('releaseLock')
            ->with(sprintf('api_%s_info.lock', $operationId));

        $this->jobRunner->expects(self::once())
            ->method('createDelayed')
            ->with($retryJobName)
            ->willReturnCallback(function ($name, $startCallback) {
                $job = new Job();
                $job->setId(111);

                return $startCallback($this->jobRunner, $job);
            });
        $this->processingHelper->expects(self::once())
            ->method('sendProcessChunkMessage')
            ->with(
                $body,
                self::isInstanceOf(Job::class),
                new ChunkFile($fileName, $fileIndex, $firstRecordOffset, $sectionName),
                $extraChunk
            );

        $this->processingHelper->expects(self::once())
            ->method('calculateAggregateTime')
            ->with(self::isType('float'), 0)
            ->willReturn($processorAggregateTime);

        $this->processingHelper->expects(self::never())
            ->method('safeDeleteFile');

        $this->logger->expects(self::once())
            ->method('info')
            ->with('The retry requested. Reason: test retry reason');

        $this->processor->process($message, $this->getSession());

        self::assertEquals($expectedJobData, $job->getData());
    }

    public function processWhenRetryRequestedDataProvider()
    {
        return [
            'first retry' => ['oro:batch_api:123:chunk:1', 'oro:batch_api:123:chunk:1:r1', false],
            'other retry' => ['oro:batch_api:123:chunk:1:r15', 'oro:batch_api:123:chunk:1:r16', false],
            'extra chunk' => ['oro:batch_api:123:chunk:10:11:r1', 'oro:batch_api:123:chunk:10:11:r2', true]
        ];
    }

    public function testProcessAfterRetry()
    {
        $jobId = 456;
        $fileName = 'chunkFile';
        $message = $this->getMessage([
            'operationId'       => 123,
            'entityClass'       => 'Test\Entity',
            'requestType'       => ['testRequest'],
            'version'           => '1.1',
            'jobId'             => $jobId,
            'fileName'          => $fileName,
            'fileIndex'         => 10,
            'firstRecordOffset' => 0,
            'sectionName'       => 'test'
        ]);
        $previousAggregateTime = 100;
        $processorAggregateTime = 11;
        $summaryData = [
            'readCount'   => 1,
            'writeCount'  => 1,
            'errorCount'  => 0,
            'createCount' => 1,
            'updateCount' => 0
        ];

        $job = new Job();
        $job->setData([
            'key'     => 'value',
            'summary' => [
                'aggregateTime' => $previousAggregateTime,
                'readCount'     => 1,
                'writeCount'    => 0,
                'errorCount'    => 0,
                'createCount'   => 0,
                'updateCount'   => 0
            ]
        ]);
        $job->setId($jobId);
        $expectedJobData = array_merge(
            $job->getData(),
            [
                'summary' => array_merge(
                    ['aggregateTime' => $processorAggregateTime + $previousAggregateTime],
                    $summaryData
                )
            ]
        );

        $this->jobRunner->expects(self::once())
            ->method('runDelayed')
            ->willReturnCallback(function ($name, $startCallback) use ($job) {
                return $startCallback($this->jobRunner, $job);
            });
        $this->handler->expects(self::once())
            ->method('handle')
            ->willReturn(new BatchUpdateResponse(
                [['key' => 'val1']],
                [BatchUpdateItemStatus::NO_ERRORS],
                $this->createBatchSummaryFromArray($summaryData),
                false
            ));

        $this->processingHelper->expects(self::exactly(2))
            ->method('calculateAggregateTime')
            ->with(self::isType('float'), $previousAggregateTime)
            ->willReturn($processorAggregateTime + $previousAggregateTime);

        $this->processingHelper->expects(self::once())
            ->method('safeDeleteFile')
            ->with($fileName);

        $this->logger->expects(self::never())
            ->method(self::anything());

        $result = $this->processor->process($message, $this->getSession());

        self::assertEquals(MessageProcessorInterface::ACK, $result);
        self::assertEquals($expectedJobData, $job->getData());
    }
}
