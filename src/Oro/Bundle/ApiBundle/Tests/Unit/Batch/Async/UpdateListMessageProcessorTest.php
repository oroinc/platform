<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Async;

use Oro\Bundle\ApiBundle\Batch\Async\AsyncOperationManager;
use Oro\Bundle\ApiBundle\Batch\Async\ChunkFileClassifierInterface;
use Oro\Bundle\ApiBundle\Batch\Async\ChunkFileClassifierRegistry;
use Oro\Bundle\ApiBundle\Batch\Async\Topics;
use Oro\Bundle\ApiBundle\Batch\Async\UpdateListMessageProcessor;
use Oro\Bundle\ApiBundle\Batch\Async\UpdateListProcessingHelper;
use Oro\Bundle\ApiBundle\Batch\FileNameProvider;
use Oro\Bundle\ApiBundle\Batch\IncludeAccessor\IncludeAccessorInterface;
use Oro\Bundle\ApiBundle\Batch\IncludeAccessor\IncludeAccessorRegistry;
use Oro\Bundle\ApiBundle\Batch\IncludeMapManager;
use Oro\Bundle\ApiBundle\Batch\Model\ChunkFile;
use Oro\Bundle\ApiBundle\Batch\Splitter\FileSplitterInterface;
use Oro\Bundle\ApiBundle\Batch\Splitter\FileSplitterRegistry;
use Oro\Bundle\ApiBundle\Batch\Splitter\PartialFileSplitterInterface;
use Oro\Bundle\ApiBundle\Exception\FileSplitterException;
use Oro\Bundle\ApiBundle\Exception\ParsingErrorFileSplitterException;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Bundle\MessageQueueBundle\Test\Unit\MessageQueueExtension;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\DependentJobContext;
use Oro\Component\MessageQueue\Job\DependentJobService;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobManagerInterface;
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
class UpdateListMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    use MessageQueueExtension;

    private const SPLIT_FILE_TIMEOUT = 30000;

    /** @var \PHPUnit\Framework\MockObject\MockObject|JobRunner */
    private $jobRunner;

    /** @var \PHPUnit\Framework\MockObject\MockObject|JobManagerInterface */
    private $jobManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|DependentJobService */
    private $dependentJob;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FileSplitterRegistry */
    private $splitterRegistry;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ChunkFileClassifierRegistry */
    private $chunkFileClassifierRegistry;

    /** @var \PHPUnit\Framework\MockObject\MockObject|IncludeAccessorRegistry */
    private $includeAccessorRegistry;

    /** @var \PHPUnit\Framework\MockObject\MockObject|IncludeMapManager */
    private $includeMapManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FileManager */
    private $sourceDataFileManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FileManager */
    private $fileManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|AsyncOperationManager */
    private $operationManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|UpdateListProcessingHelper */
    private $processingHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|LoggerInterface */
    private $logger;

    /** @var UpdateListMessageProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->jobRunner = $this->createMock(JobRunner::class);
        $this->jobManager = $this->createMock(JobManagerInterface::class);
        $this->dependentJob = $this->createMock(DependentJobService::class);
        $this->splitterRegistry = $this->createMock(FileSplitterRegistry::class);
        $this->chunkFileClassifierRegistry = $this->createMock(ChunkFileClassifierRegistry::class);
        $this->includeAccessorRegistry = $this->createMock(IncludeAccessorRegistry::class);
        $this->includeMapManager = $this->createMock(IncludeMapManager::class);
        $this->sourceDataFileManager = $this->createMock(FileManager::class);
        $this->fileManager = $this->createMock(FileManager::class);
        $this->operationManager = $this->createMock(AsyncOperationManager::class);
        $this->processingHelper = $this->createMock(UpdateListProcessingHelper::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->processingHelper->expects(self::any())
            ->method('getCommonBody')
            ->willReturnCallback(function (array $parentBody) {
                return array_intersect_key(
                    $parentBody,
                    array_flip(['operationId', 'entityClass', 'requestType', 'version'])
                );
            });

        $this->processor = new UpdateListMessageProcessor(
            $this->jobRunner,
            $this->jobManager,
            $this->dependentJob,
            $this->splitterRegistry,
            $this->chunkFileClassifierRegistry,
            $this->includeAccessorRegistry,
            $this->includeMapManager,
            $this->sourceDataFileManager,
            $this->fileManager,
            self::getMessageProducer(),
            $this->operationManager,
            $this->processingHelper,
            new FileNameProvider(),
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

    private function expectGetSplitter(RequestType $requestType, ?FileSplitterInterface $splitter)
    {
        $this->splitterRegistry->expects(self::once())
            ->method('getSplitter')
            ->with($requestType)
            ->willReturn($splitter);
    }

    private function expectGetClassifier(RequestType $requestType, ?ChunkFileClassifierInterface $classifier)
    {
        $this->chunkFileClassifierRegistry->expects(self::once())
            ->method('getClassifier')
            ->with($requestType)
            ->willReturn($classifier);
    }

    /**
     * @param \PHPUnit\Framework\MockObject\MockObject|FileSplitterInterface $splitter
     * @param int                                                            $operationId
     * @param string                                                         $fileName
     * @param int                                                            $chunkSize
     * @param ChunkFile[]                                                    $chunkFiles
     */
    private function expectSplitFile(
        FileSplitterInterface $splitter,
        int $operationId,
        string $fileName,
        int $chunkSize,
        array $chunkFiles
    ) {
        $initialChunkSize = 300;
        $initialChunkSizePerSection = ['included' => 400];
        $chunkSizePerSection = ['included' => 20];
        $splitter->expects(self::once())
            ->method('getChunkSize')
            ->willReturn($initialChunkSize);
        $splitter->expects(self::once())
            ->method('getChunkSizePerSection')
            ->willReturn($initialChunkSizePerSection);
        $splitter->expects(self::exactly(2))
            ->method('setChunkSize')
            ->withConsecutive([$chunkSize], [$initialChunkSize]);
        $splitter->expects(self::exactly(2))
            ->method('setChunkSizePerSection')
            ->withConsecutive([$chunkSizePerSection], [$initialChunkSizePerSection]);
        $splitter->expects(self::once())
            ->method('setChunkFileNameTemplate')
            ->with(sprintf('api_%s_chunk_', $operationId) . '%s');
        $splitter->expects(self::once())
            ->method('splitFile')
            ->with($fileName, self::identicalTo($this->sourceDataFileManager), self::identicalTo($this->fileManager))
            ->willReturn($chunkFiles);
    }

    /**
     * @param \PHPUnit\Framework\MockObject\MockObject|FileSplitterInterface $splitter
     * @param int                                                            $operationId
     * @param string                                                         $fileName
     * @param int                                                            $chunkSize
     * @param \Exception                                                     $exception
     */
    private function expectSplitFileThrowException(
        FileSplitterInterface $splitter,
        int $operationId,
        string $fileName,
        int $chunkSize,
        \Exception $exception
    ) {
        $initialChunkSize = 1000;
        $splitter->expects(self::once())
            ->method('getChunkSize')
            ->willReturn($initialChunkSize);
        $splitter->expects(self::exactly(2))
            ->method('setChunkSize')
            ->withConsecutive([$chunkSize], [$initialChunkSize]);
        $splitter->expects(self::once())
            ->method('setChunkFileNameTemplate')
            ->with(sprintf('api_%s_chunk_', $operationId) . '%s');
        $splitter->expects(self::once())
            ->method('splitFile')
            ->with($fileName, self::identicalTo($this->sourceDataFileManager), self::identicalTo($this->fileManager))
            ->willThrowException($exception);
    }

    private function expectRunUniqueJob(string $messageId, int $operationId, Job $job)
    {
        $this->jobRunner->expects(self::once())
            ->method('runUnique')
            ->willReturnCallback(function ($ownerId, $name, $runCallback) use ($messageId, $operationId, $job) {
                self::assertEquals($messageId, $ownerId);
                self::assertEquals(sprintf('oro:batch_api:%d', $operationId), $name);

                return $runCallback($this->jobRunner, $job);
            });
    }

    private function expectCreateDelayedJob(int $operationId, Job $job, int $jobId)
    {
        $this->jobRunner->expects(self::once())
            ->method('createDelayed')
            ->willReturnCallback(function ($name, $startCallback) use ($operationId, $job, $jobId) {
                self::assertEquals(sprintf('oro:batch_api:%d:chunk:1', $operationId), $name);
                $job->setId($jobId);

                return $startCallback($this->jobRunner, $job);
            });
    }

    private function expectSaveJob(int $operationId, array $jobData)
    {
        $this->jobManager->expects(self::once())
            ->method('saveJob')
            ->willReturnCallback(function (Job $job) use ($operationId, $jobData) {
                self::assertEquals(array_merge($jobData, ['api_operation_id' => $operationId]), $job->getData());
            });
    }

    /**
     * @param Job $rootJob
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|DependentJobContext
     */
    private function createDependentJobContext(Job $rootJob): DependentJobContext
    {
        $dependentJobContext = $this->createMock(DependentJobContext::class);
        $this->dependentJob->expects(self::once())
            ->method('createDependentJobContext')
            ->with(self::identicalTo($rootJob))
            ->willReturn($dependentJobContext);
        $this->dependentJob->expects(self::once())
            ->method('saveDependentJob')
            ->with(self::identicalTo($dependentJobContext));

        return $dependentJobContext;
    }

    public function testGetSubscribedTopics()
    {
        self::assertEquals(
            [Topics::UPDATE_LIST],
            UpdateListMessageProcessor::getSubscribedTopics()
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

    public function testShouldRejectIfSplitterNotFound()
    {
        $operationId = 123;
        $requestType = ['testRequest'];
        $fileName = 'testFile';
        $message = $this->getMessage([
            'operationId'           => $operationId,
            'entityClass'           => 'Test\Entity',
            'requestType'           => $requestType,
            'version'               => '1.1',
            'fileName'              => $fileName,
            'chunkSize'             => 10,
            'includedDataChunkSize' => 20
        ]);

        $this->operationManager->expects(self::once())
            ->method('markAsRunning')
            ->with($operationId);
        $this->expectGetSplitter(new RequestType($requestType), null);
        $this->operationManager->expects(self::once())
            ->method('markAsFailed')
            ->with($operationId, $fileName, 'A file splitter was not found for the request type "testRequest".');

        $this->logger->expects(self::once())
            ->method('error')
            ->with('A file splitter was not found for the request type "testRequest".');

        $result = $this->processor->process($message, $this->getSession());

        self::assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldRejectIfClassifierNotFound()
    {
        $operationId = 123;
        $requestType = ['testRequest'];
        $fileName = 'testFile';
        $message = $this->getMessage([
            'operationId'           => $operationId,
            'entityClass'           => 'Test\Entity',
            'requestType'           => $requestType,
            'version'               => '1.1',
            'fileName'              => $fileName,
            'chunkSize'             => 10,
            'includedDataChunkSize' => 20
        ]);

        $this->operationManager->expects(self::once())
            ->method('markAsRunning')
            ->with($operationId);
        $this->expectGetSplitter(new RequestType($requestType), $this->createMock(FileSplitterInterface::class));
        $this->expectGetClassifier(new RequestType($requestType), null);
        $this->operationManager->expects(self::once())
            ->method('markAsFailed')
            ->with(
                $operationId,
                $fileName,
                'A chunk file classifier was not found for the request type "testRequest".'
            );

        $this->logger->expects(self::once())
            ->method('error')
            ->with('A chunk file classifier was not found for the request type "testRequest".');

        $result = $this->processor->process($message, $this->getSession());

        self::assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testSplitterThrowsParsingErrorFileSplitterException()
    {
        $operationId = 123;
        $requestType = ['testRequest'];
        $fileName = 'testFile';
        $chunkSize = 10;
        $message = $this->getMessage([
            'operationId'           => $operationId,
            'entityClass'           => 'Test\Entity',
            'requestType'           => $requestType,
            'version'               => '1.1',
            'fileName'              => $fileName,
            'chunkSize'             => $chunkSize,
            'includedDataChunkSize' => 20
        ]);

        $splitter = $this->createMock(FileSplitterInterface::class);
        $classifier = $this->createMock(ChunkFileClassifierInterface::class);
        $splitterException = new ParsingErrorFileSplitterException(
            $fileName,
            [],
            new \Exception('Some parsing error.')
        );

        $this->operationManager->expects(self::once())
            ->method('markAsRunning')
            ->with($operationId);
        $this->expectGetSplitter(new RequestType($requestType), $splitter);
        $this->expectGetClassifier(new RequestType($requestType), $classifier);
        $this->expectSplitFileThrowException($splitter, $operationId, $fileName, $chunkSize, $splitterException);
        $this->operationManager->expects(self::once())
            ->method('markAsFailed')
            ->with($operationId, $fileName, 'Failed to parse the data file. Some parsing error.');

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'The splitting of the file "testFile" failed.'
                . ' Reason: Failed to parse the data file. Some parsing error.'
            );

        $this->processingHelper->expects(self::once())
            ->method('safeDeleteChunkFiles')
            ->with($operationId, sprintf('api_%d_chunk_', $operationId) . '%s');

        $result = $this->processor->process($message, $this->getSession());

        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testSplitterThrowsFileSplitterException()
    {
        $operationId = 123;
        $requestType = ['testRequest'];
        $fileName = 'testFile';
        $chunkSize = 10;
        $message = $this->getMessage([
            'operationId'           => $operationId,
            'entityClass'           => 'Test\Entity',
            'requestType'           => $requestType,
            'version'               => '1.1',
            'fileName'              => $fileName,
            'chunkSize'             => $chunkSize,
            'includedDataChunkSize' => 20
        ]);

        $splitter = $this->createMock(FileSplitterInterface::class);
        $classifier = $this->createMock(ChunkFileClassifierInterface::class);
        $splitterException = new FileSplitterException($fileName, ['targetFile1']);

        $this->operationManager->expects(self::once())
            ->method('markAsRunning')
            ->with($operationId);
        $this->expectGetSplitter(new RequestType($requestType), $splitter);
        $this->expectGetClassifier(new RequestType($requestType), $classifier);
        $this->expectSplitFileThrowException($splitter, $operationId, $fileName, $chunkSize, $splitterException);
        $this->operationManager->expects(self::once())
            ->method('markAsFailed')
            ->with($operationId, $fileName, 'Failed to parse the data file.');

        $this->logger->expects(self::once())
            ->method('error')
            ->with('The splitting of the file "testFile" failed. Reason: Failed to parse the data file.');

        $this->processingHelper->expects(self::once())
            ->method('safeDeleteChunkFiles')
            ->with($operationId, sprintf('api_%d_chunk_', $operationId) . '%s');

        $result = $this->processor->process($message, $this->getSession());

        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testSplitterThrowsUnexpectedException()
    {
        $operationId = 123;
        $requestType = ['testRequest'];
        $fileName = 'testFile';
        $chunkSize = 10;
        $message = $this->getMessage([
            'operationId'           => $operationId,
            'entityClass'           => 'Test\Entity',
            'requestType'           => $requestType,
            'version'               => '1.1',
            'fileName'              => $fileName,
            'chunkSize'             => $chunkSize,
            'includedDataChunkSize' => 20
        ]);

        $splitter = $this->createMock(FileSplitterInterface::class);
        $classifier = $this->createMock(ChunkFileClassifierInterface::class);
        $splitterException = new \Exception('some error');

        $this->operationManager->expects(self::once())
            ->method('markAsRunning')
            ->with($operationId);
        $this->expectGetSplitter(new RequestType($requestType), $splitter);
        $this->expectGetClassifier(new RequestType($requestType), $classifier);
        $this->expectSplitFileThrowException($splitter, $operationId, $fileName, $chunkSize, $splitterException);
        $this->operationManager->expects(self::once())
            ->method('markAsFailed')
            ->with($operationId, $fileName, 'some error');

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'The splitting of the file "testFile" failed. Reason: some error',
                ['exception' => $splitterException]
            );

        $this->processingHelper->expects(self::never())
            ->method('safeDeleteChunkFiles');
        $this->sourceDataFileManager->expects(self::once())
            ->method('deleteFile')
            ->with($fileName);

        $result = $this->processor->process($message, $this->getSession());

        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testProcessWhenSplitterCompletedWork()
    {
        $messageId = 'testMassage';
        $operationId = 123;
        $entityClass = 'Test\Entity';
        $requestType = ['testRequest'];
        $version = '1.1';
        $fileName = 'testFile';
        $chunkSize = 10;
        $body = [
            'operationId'           => $operationId,
            'entityClass'           => $entityClass,
            'requestType'           => $requestType,
            'version'               => $version,
            'fileName'              => $fileName,
            'chunkSize'             => $chunkSize,
            'includedDataChunkSize' => 20
        ];
        $message = $this->getMessage($body, $messageId);
        $aggregateTime = 100;

        $splitter = $this->createMock(FileSplitterInterface::class);
        $classifier = $this->createMock(ChunkFileClassifierInterface::class);
        $chunkFile = new ChunkFile('targetFile1', 0, 0, 'data');

        $this->operationManager->expects(self::once())
            ->method('markAsRunning')
            ->with($operationId);
        $this->expectGetSplitter(new RequestType($requestType), $splitter);
        $this->expectGetClassifier(new RequestType($requestType), $classifier);
        $this->expectSplitFile($splitter, $operationId, $fileName, $chunkSize, [$chunkFile]);
        $classifier->expects(self::once())
            ->method('isPrimaryData')
            ->with(self::identicalTo($chunkFile))
            ->willReturn(true);
        $classifier->expects(self::never())
            ->method('isIncludedData');
        $this->operationManager->expects(self::never())
            ->method('markAsFailed');

        $this->processingHelper->expects(self::once())
            ->method('hasChunkIndex')
            ->with($operationId)
            ->willReturn(false);
        $this->processingHelper->expects(self::never())
            ->method('updateChunkIndex');
        $this->processingHelper->expects(self::never())
            ->method('loadChunkIndex');

        $jobId = 11;
        $rootJob = new Job();
        $rootJob->setData(['key' => 'value']);
        $job = new Job();
        $job->setRootJob($rootJob);

        $this->expectRunUniqueJob($messageId, $operationId, $job);
        $this->expectSaveJob($operationId, ['key' => 'value']);
        $this->fileManager->expects(self::once())
            ->method('writeToStorage')
            ->with('{"chunkCount":1}', sprintf('api_%s_info', $operationId));

        $dependentJobContext = $this->createDependentJobContext($rootJob);
        $dependentJobContext->expects(self::once())
            ->method('addDependentJob')
            ->with(
                Topics::UPDATE_LIST_FINISH,
                [
                    'operationId' => $operationId,
                    'entityClass' => $entityClass,
                    'requestType' => $requestType,
                    'version'     => $version,
                    'fileName'    => $fileName
                ]
            );

        $this->expectCreateDelayedJob($operationId, $job, $jobId);
        $this->processingHelper->expects(self::once())
            ->method('sendProcessChunkMessage')
            ->with($body, self::identicalTo($job), self::identicalTo($chunkFile));

        $this->sourceDataFileManager->expects(self::once())
            ->method('deleteFile')
            ->with($fileName);

        $this->includeMapManager->expects(self::never())
            ->method('updateIncludedChunkIndex');

        $this->processingHelper->expects(self::exactly(2))
            ->method('calculateAggregateTime')
            ->withConsecutive([self::isType('float'), 0], [self::isType('float'), $aggregateTime])
            ->willReturnOnConsecutiveCalls($aggregateTime, $aggregateTime + 10);
        $this->operationManager->expects(self::once())
            ->method('incrementAggregateTime')
            ->with($operationId, $aggregateTime + 10);

        $result = $this->processor->process($message, $this->getSession());

        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessLastIteration()
    {
        $messageId = 'testMassage';
        $operationId = 123;
        $entityClass = 'Test\Entity';
        $requestType = ['testRequest'];
        $version = '1.1';
        $fileName = 'testFile';
        $chunkSize = 10;
        $splitterState = ['splitter_option1' => 'val1'];
        $aggregateTime = 2345;
        $body = [
            'operationId'           => $operationId,
            'entityClass'           => $entityClass,
            'requestType'           => $requestType,
            'version'               => $version,
            'fileName'              => $fileName,
            'chunkSize'             => $chunkSize,
            'includedDataChunkSize' => 20,
            'splitterState'         => $splitterState,
            'aggregateTime'         => $aggregateTime
        ];
        $message = $this->getMessage($body, $messageId);

        $splitter = $this->createMock(PartialFileSplitterInterface::class);
        $classifier = $this->createMock(ChunkFileClassifierInterface::class);
        $chunkFileFromPreviousIteration = new ChunkFile('targetFile1', 0, 0, 'data');
        $chunkFile = new ChunkFile('targetFile2', 1, 1, 'data');

        $this->operationManager->expects(self::never())
            ->method('markAsRunning');
        $this->expectGetSplitter(new RequestType($requestType), $splitter);
        $this->expectGetClassifier(new RequestType($requestType), $classifier);
        $splitter->expects(self::once())
            ->method('setTimeout')
            ->with(self::SPLIT_FILE_TIMEOUT);
        $splitter->expects(self::once())
            ->method('setState')
            ->with($splitterState);
        $splitter->expects(self::once())
            ->method('isCompleted')
            ->willReturn(true);
        $this->expectSplitFile($splitter, $operationId, $fileName, $chunkSize, [$chunkFile]);
        $classifier->expects(self::once())
            ->method('isPrimaryData')
            ->with(self::identicalTo($chunkFile))
            ->willReturn(true);
        $classifier->expects(self::never())
            ->method('isIncludedData');
        $this->operationManager->expects(self::never())
            ->method('markAsFailed');

        $this->processingHelper->expects(self::once())
            ->method('hasChunkIndex')
            ->with($operationId)
            ->willReturn(true);
        $this->processingHelper->expects(self::once())
            ->method('updateChunkIndex')
            ->with($operationId, [$chunkFile]);
        $this->processingHelper->expects(self::once())
            ->method('loadChunkIndex')
            ->with($operationId)
            ->willReturn([$chunkFileFromPreviousIteration, $chunkFile]);

        $rootJob = new Job();
        $rootJob->setData(['key' => 'value']);
        $job = new Job();
        $job->setRootJob($rootJob);

        $this->expectRunUniqueJob($messageId, $operationId, $job);
        $this->expectSaveJob($operationId, ['key' => 'value']);
        $this->fileManager->expects(self::once())
            ->method('writeToStorage')
            ->with('{"chunkCount":2}', sprintf('api_%s_info', $operationId));

        $dependentJobContext = $this->createDependentJobContext($rootJob);
        $dependentJobContext->expects(self::once())
            ->method('addDependentJob')
            ->with(
                Topics::UPDATE_LIST_FINISH,
                [
                    'operationId' => $operationId,
                    'entityClass' => $entityClass,
                    'requestType' => $requestType,
                    'version'     => $version,
                    'fileName'    => $fileName
                ]
            );

        $chunkJobNameTemplate = sprintf('oro:batch_api:%s:chunk:', $operationId) . '%s';
        $nextChunkFileIndex = 2;
        $this->processingHelper->expects(self::once())
            ->method('createChunkJobs')
            ->with(self::identicalTo($this->jobRunner), $operationId, $chunkJobNameTemplate, 0, 0)
            ->willReturn($nextChunkFileIndex);
        $this->processingHelper->expects(self::once())
            ->method('sendMessageToCreateChunkJobs')
            ->with(self::identicalTo($rootJob), $chunkJobNameTemplate, $body, $nextChunkFileIndex);
        $this->jobRunner->expects(self::never())
            ->method('createDelayed');
        $this->processingHelper->expects(self::never())
            ->method('sendProcessChunkMessage');

        $this->sourceDataFileManager->expects(self::once())
            ->method('deleteFile')
            ->with($fileName);

        $this->includeMapManager->expects(self::never())
            ->method('updateIncludedChunkIndex');

        $this->processingHelper->expects(self::exactly(2))
            ->method('calculateAggregateTime')
            ->withConsecutive([self::isType('float'), $aggregateTime], [self::isType('float'), $aggregateTime + 10])
            ->willReturnOnConsecutiveCalls($aggregateTime + 10, $aggregateTime + 20);
        $this->operationManager->expects(self::once())
            ->method('incrementAggregateTime')
            ->with($operationId, $aggregateTime + 20);

        $result = $this->processor->process($message, $this->getSession());

        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testProcessNextIteration()
    {
        $messageId = 'testMassage';
        $operationId = 123;
        $entityClass = 'Test\Entity';
        $requestType = ['testRequest'];
        $version = '1.1';
        $fileName = 'testFile';
        $chunkSize = 10;
        $includedDataChunkSize = 20;
        $message = $this->getMessage(
            [
                'operationId'           => $operationId,
                'entityClass'           => $entityClass,
                'requestType'           => $requestType,
                'version'               => $version,
                'fileName'              => $fileName,
                'chunkSize'             => $chunkSize,
                'includedDataChunkSize' => $includedDataChunkSize
            ],
            $messageId
        );
        $aggregateTime = 100;

        $splitter = $this->createMock(PartialFileSplitterInterface::class);
        $classifier = $this->createMock(ChunkFileClassifierInterface::class);
        $splitterNextIterationState = ['key' => 'value'];
        $chunkFile = new ChunkFile('targetFile1', 0, 0, 'data');

        $this->operationManager->expects(self::once())
            ->method('markAsRunning')
            ->with($operationId);
        $this->expectGetSplitter(new RequestType($requestType), $splitter);
        $this->expectGetClassifier(new RequestType($requestType), $classifier);
        $splitter->expects(self::once())
            ->method('setTimeout')
            ->with(self::SPLIT_FILE_TIMEOUT);
        $splitter->expects(self::once())
            ->method('setState')
            ->with(self::identicalTo([]));
        $splitter->expects(self::once())
            ->method('isCompleted')
            ->willReturn(false);
        $this->expectSplitFile($splitter, $operationId, $fileName, $chunkSize, [$chunkFile]);
        $classifier->expects(self::once())
            ->method('isPrimaryData')
            ->with(self::identicalTo($chunkFile))
            ->willReturn(true);
        $classifier->expects(self::never())
            ->method('isIncludedData');
        $splitter->expects(self::once())
            ->method('getState')
            ->willReturn($splitterNextIterationState);
        $this->operationManager->expects(self::never())
            ->method('markAsFailed');

        $this->includeMapManager->expects(self::never())
            ->method('updateIncludedChunkIndex');

        $this->processingHelper->expects(self::once())
            ->method('updateChunkIndex')
            ->with($operationId, [$chunkFile]);
        $this->processingHelper->expects(self::once())
            ->method('calculateAggregateTime')
            ->with(self::isType('float'), 0)
            ->willReturn($aggregateTime);
        $this->operationManager->expects(self::once())
            ->method('incrementAggregateTime')
            ->with($operationId, $aggregateTime);

        $this->jobRunner->expects(self::never())
            ->method('runUnique');

        $result = $this->processor->process($message, $this->getSession());

        self::assertEquals(MessageProcessorInterface::ACK, $result);
        self::assertMessageSent(Topics::UPDATE_LIST, [
            'operationId'           => $operationId,
            'entityClass'           => $entityClass,
            'requestType'           => $requestType,
            'version'               => $version,
            'fileName'              => $fileName,
            'chunkSize'             => $chunkSize,
            'includedDataChunkSize' => $includedDataChunkSize,
            'splitterState'         => $splitterNextIterationState,
            'aggregateTime'         => $aggregateTime
        ]);
    }

    public function testProcessWhenPartialSplitterCompletedWork()
    {
        $messageId = 'testMassage';
        $operationId = 123;
        $entityClass = 'Test\Entity';
        $requestType = ['testRequest'];
        $fileName = 'testFile';
        $chunkSize = 10;
        $message = $this->getMessage(
            [
                'operationId'           => $operationId,
                'entityClass'           => $entityClass,
                'requestType'           => $requestType,
                'version'               => '1.1',
                'fileName'              => $fileName,
                'chunkSize'             => $chunkSize,
                'includedDataChunkSize' => 20
            ],
            $messageId
        );

        $splitter = $this->createMock(PartialFileSplitterInterface::class);
        $classifier = $this->createMock(ChunkFileClassifierInterface::class);
        $chunkFile = new ChunkFile('targetFile1', 0, 0, 'data');

        $this->operationManager->expects(self::once())
            ->method('markAsRunning')
            ->with($operationId);
        $this->expectGetSplitter(new RequestType($requestType), $splitter);
        $this->expectGetClassifier(new RequestType($requestType), $classifier);
        $splitter->expects(self::once())
            ->method('setTimeout')
            ->with(self::SPLIT_FILE_TIMEOUT);
        $splitter->expects(self::once())
            ->method('setState')
            ->with(self::identicalTo([]));
        $splitter->expects(self::once())
            ->method('isCompleted')
            ->willReturn(true);
        $this->expectSplitFile($splitter, $operationId, $fileName, $chunkSize, [$chunkFile]);
        $classifier->expects(self::once())
            ->method('isPrimaryData')
            ->with(self::identicalTo($chunkFile))
            ->willReturn(true);
        $classifier->expects(self::never())
            ->method('isIncludedData');
        $splitter->expects(self::never())
            ->method('getState');
        $this->operationManager->expects(self::never())
            ->method('markAsFailed');

        $this->jobRunner->expects(self::once())
            ->method('runUnique')
            ->willReturn(true);
        $this->sourceDataFileManager->expects(self::once())
            ->method('deleteFile')
            ->with($fileName);

        $result = $this->processor->process($message, $this->getSession());

        self::assertEquals(MessageProcessorInterface::ACK, $result);
        self::assertEmptyMessages(Topics::UPDATE_LIST);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessWithIncludedData()
    {
        $messageId = 'testMassage';
        $operationId = 123;
        $entityClass = 'Test\Entity';
        $requestType = ['testRequest'];
        $version = '1.1';
        $fileName = 'testFile';
        $chunkSize = 10;
        $body = [
            'operationId'           => $operationId,
            'entityClass'           => $entityClass,
            'requestType'           => $requestType,
            'version'               => $version,
            'fileName'              => $fileName,
            'chunkSize'             => $chunkSize,
            'includedDataChunkSize' => 20
        ];
        $message = $this->getMessage($body, $messageId);
        $aggregateTime = 100;

        $splitter = $this->createMock(FileSplitterInterface::class);
        $classifier = $this->createMock(ChunkFileClassifierInterface::class);
        $chunkFile = new ChunkFile('targetFile1', 0, 0, 'included');

        $this->operationManager->expects(self::once())
            ->method('markAsRunning')
            ->with($operationId);
        $this->expectGetSplitter(new RequestType($requestType), $splitter);
        $this->expectGetClassifier(new RequestType($requestType), $classifier);
        $this->expectSplitFile($splitter, $operationId, $fileName, $chunkSize, [$chunkFile]);
        $classifier->expects(self::once())
            ->method('isPrimaryData')
            ->with(self::identicalTo($chunkFile))
            ->willReturn(false);
        $classifier->expects(self::once())
            ->method('isIncludedData')
            ->with(self::identicalTo($chunkFile))
            ->willReturn(true);
        $this->operationManager->expects(self::never())
            ->method('markAsFailed');

        $this->processingHelper->expects(self::once())
            ->method('hasChunkIndex')
            ->with($operationId)
            ->willReturn(false);
        $this->processingHelper->expects(self::never())
            ->method('updateChunkIndex');
        $this->processingHelper->expects(self::never())
            ->method('loadChunkIndex');

        $rootJob = new Job();
        $rootJob->setData(['key' => 'value']);
        $job = new Job();
        $job->setRootJob($rootJob);

        $this->expectRunUniqueJob($messageId, $operationId, $job);
        $this->expectSaveJob($operationId, ['key' => 'value']);
        $this->fileManager->expects(self::once())
            ->method('writeToStorage')
            ->with('{"chunkCount":0}', sprintf('api_%s_info', $operationId));

        $dependentJobContext = $this->createDependentJobContext($rootJob);
        $dependentJobContext->expects(self::once())
            ->method('addDependentJob')
            ->with(
                Topics::UPDATE_LIST_FINISH,
                [
                    'operationId' => $operationId,
                    'entityClass' => $entityClass,
                    'requestType' => $requestType,
                    'version'     => $version,
                    'fileName'    => $fileName
                ]
            );

        $this->jobRunner->expects(self::never())
            ->method('createDelayed');
        $this->processingHelper->expects(self::never())
            ->method('sendProcessChunkMessage');

        $this->sourceDataFileManager->expects(self::once())
            ->method('deleteFile')
            ->with($fileName);

        $includeAccessor = $this->createMock(IncludeAccessorInterface::class);
        $this->includeAccessorRegistry->expects(self::once())
            ->method('getAccessor')
            ->with(new RequestType($requestType))
            ->willReturn($includeAccessor);
        $this->includeMapManager->expects(self::once())
            ->method('updateIncludedChunkIndex')
            ->with(
                self::identicalTo($this->fileManager),
                $operationId,
                self::identicalTo($includeAccessor),
                [$chunkFile]
            );

        $this->processingHelper->expects(self::exactly(2))
            ->method('calculateAggregateTime')
            ->withConsecutive([self::isType('float'), 0], [self::isType('float'), $aggregateTime])
            ->willReturnOnConsecutiveCalls($aggregateTime, $aggregateTime + 10);
        $this->operationManager->expects(self::once())
            ->method('incrementAggregateTime')
            ->with($operationId, $aggregateTime + 10);

        $result = $this->processor->process($message, $this->getSession());

        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testShouldRejectIfIncludeAccessorNotFound()
    {
        $messageId = 'testMassage';
        $operationId = 123;
        $requestType = ['testRequest'];
        $fileName = 'testFile';
        $chunkSize = 10;
        $body = [
            'operationId'           => $operationId,
            'entityClass'           => 'Test\Entity',
            'requestType'           => $requestType,
            'version'               => '1.1',
            'fileName'              => $fileName,
            'chunkSize'             => $chunkSize,
            'includedDataChunkSize' => 20
        ];
        $message = $this->getMessage($body, $messageId);

        $splitter = $this->createMock(FileSplitterInterface::class);
        $classifier = $this->createMock(ChunkFileClassifierInterface::class);
        $chunkFile = new ChunkFile('targetFile1', 0, 0, 'included');

        $this->operationManager->expects(self::once())
            ->method('markAsRunning')
            ->with($operationId);
        $this->expectGetSplitter(new RequestType($requestType), $splitter);
        $this->expectGetClassifier(new RequestType($requestType), $classifier);
        $this->expectSplitFile($splitter, $operationId, $fileName, $chunkSize, [$chunkFile]);
        $classifier->expects(self::once())
            ->method('isPrimaryData')
            ->with(self::identicalTo($chunkFile))
            ->willReturn(false);
        $classifier->expects(self::once())
            ->method('isIncludedData')
            ->with(self::identicalTo($chunkFile))
            ->willReturn(true);
        $this->operationManager->expects(self::once())
            ->method('markAsFailed')
            ->with($operationId, $fileName, 'An include accessor was not found for the request type "testRequest".');

        $this->logger->expects(self::once())
            ->method('error')
            ->with('An include accessor was not found for the request type "testRequest".');

        $this->processingHelper->expects(self::never())
            ->method('hasChunkIndex');
        $this->processingHelper->expects(self::never())
            ->method('updateChunkIndex');
        $this->processingHelper->expects(self::never())
            ->method('loadChunkIndex');

        $this->jobRunner->expects(self::never())
            ->method('runUnique');
        $this->jobManager->expects(self::never())
            ->method('saveJob');
        $this->fileManager->expects(self::never())
            ->method('writeToStorage');

        $this->dependentJob->expects(self::never())
            ->method('createDependentJobContext');
        $this->dependentJob->expects(self::never())
            ->method('saveDependentJob');

        $this->jobRunner->expects(self::never())
            ->method('createDelayed');
        $this->processingHelper->expects(self::never())
            ->method('sendProcessChunkMessage');

        $this->sourceDataFileManager->expects(self::never())
            ->method('deleteFile')
            ->with($fileName);

        $this->includeAccessorRegistry->expects(self::once())
            ->method('getAccessor')
            ->with(new RequestType($requestType))
            ->willReturn(null);

        $this->processingHelper->expects(self::never())
            ->method('calculateAggregateTime');
        $this->operationManager->expects(self::never())
            ->method('incrementAggregateTime');

        $result = $this->processor->process($message, $this->getSession());

        self::assertEquals(MessageProcessorInterface::REJECT, $result);
    }
}
