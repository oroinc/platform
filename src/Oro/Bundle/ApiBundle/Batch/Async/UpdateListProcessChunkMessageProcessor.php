<?php

namespace Oro\Bundle\ApiBundle\Batch\Async;

use Oro\Bundle\ApiBundle\Batch\Async\Topic\UpdateListProcessChunkTopic;
use Oro\Bundle\ApiBundle\Batch\Encoder\DataEncoderRegistry;
use Oro\Bundle\ApiBundle\Batch\FileLockManager;
use Oro\Bundle\ApiBundle\Batch\FileNameProvider;
use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateHandler;
use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateRequest;
use Oro\Bundle\ApiBundle\Batch\JsonUtil;
use Oro\Bundle\ApiBundle\Batch\Model\ChunkFile;
use Oro\Bundle\ApiBundle\Batch\RetryHelper;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobManagerInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

/**
 * Processes a chunk of data of API batch update request.
 */
class UpdateListProcessChunkMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    private JobRunner $jobRunner;
    private JobManagerInterface $jobManager;
    private FileManager $fileManager;
    private BatchUpdateHandler $handler;
    private DataEncoderRegistry $dataEncoderRegistry;
    private RetryHelper $retryHelper;
    private UpdateListProcessingHelper $processingHelper;
    private FileNameProvider $fileNameProvider;
    private FileLockManager $fileLockManager;
    private LoggerInterface $logger;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        JobRunner $jobRunner,
        JobManagerInterface $jobManager,
        FileManager $fileManager,
        BatchUpdateHandler $handler,
        DataEncoderRegistry $dataEncoderRegistry,
        RetryHelper $retryHelper,
        UpdateListProcessingHelper $processingHelper,
        FileNameProvider $fileNameProvider,
        FileLockManager $fileLockManager,
        LoggerInterface $logger
    ) {
        $this->jobRunner = $jobRunner;
        $this->jobManager = $jobManager;
        $this->fileManager = $fileManager;
        $this->handler = $handler;
        $this->dataEncoderRegistry = $dataEncoderRegistry;
        $this->retryHelper = $retryHelper;
        $this->processingHelper = $processingHelper;
        $this->fileNameProvider = $fileNameProvider;
        $this->fileLockManager = $fileLockManager;
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedTopics(): array
    {
        return [UpdateListProcessChunkTopic::getName()];
    }

    /**
     * {@inheritDoc}
     */
    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $startTimestamp = microtime(true);
        $messageBody = $message->getBody();

        $deleteChunkFile = true;
        $result = $this->jobRunner->runDelayed(
            $messageBody['jobId'],
            function (JobRunner $jobRunner, Job $job) use ($messageBody, $startTimestamp, &$deleteChunkFile) {
                return $this->processJob($jobRunner, $job, $messageBody, $startTimestamp, $deleteChunkFile);
            }
        );

        if ($deleteChunkFile) {
            $this->processingHelper->safeDeleteFile($messageBody['fileName']);
        }

        return $result ? self::ACK : self::REJECT;
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function processJob(
        JobRunner $jobRunner,
        Job $job,
        array $body,
        float $startTimestamp,
        bool &$deleteChunkFile
    ): bool {
        $deleteChunkFile = true;
        $chunkFile = new ChunkFile(
            $body['fileName'],
            $body['fileIndex'],
            $body['firstRecordOffset'],
            $body['sectionName']
        );
        $requestType = new RequestType($body['requestType']);
        $requestType->add(ApiAction::UPDATE_LIST);
        $request = new BatchUpdateRequest(
            $body['version'],
            $requestType,
            $body['operationId'],
            [$body['entityClass']],
            $chunkFile,
            $this->fileManager
        );

        $response = $this->handler->handle($request);

        $jobData = $job->getData();
        if ($body['extra_chunk']) {
            $jobData['extra_chunk'] = true;
        }
        $previousAggregateTime = $jobData['summary']['aggregateTime'] ?? 0;
        $jobData['summary'] = [
            'aggregateTime' => $this->processingHelper->calculateAggregateTime(
                $startTimestamp,
                $previousAggregateTime
            ),
            'readCount'     => $response->getSummary()->getReadCount(),
            'writeCount'    => $response->getSummary()->getWriteCount(),
            'errorCount'    => $response->getSummary()->getErrorCount(),
            'createCount'   => $response->getSummary()->getCreateCount(),
            'updateCount'   => $response->getSummary()->getUpdateCount()
        ];
        $job->setData($jobData);

        if ($response->isRetryAgain()) {
            $retryResult = $this->retryChunk($jobRunner, $job, $body, $chunkFile, $response->getRetryReason());
            if ($retryResult) {
                $deleteChunkFile = false;
            }

            return $retryResult;
        }

        if ($response->hasUnexpectedErrors()) {
            return false;
        }

        $rawItems = $response->getData();
        if (!$rawItems) {
            // failed to load data
            return false;
        }

        $processedItemStatuses = $response->getProcessedItemStatuses();
        if (!$processedItemStatuses) {
            // some unexpected errors occurred before processing of loaded data
            return false;
        }

        $result = true;
        if ($this->retryHelper->hasItemsToRetry($rawItems, $processedItemStatuses)) {
            $chunksToRetry = $this->retryHelper->getChunksToRetry($rawItems, $processedItemStatuses);
            if ($chunksToRetry && !$this->processChunksToRetry($jobRunner, $job, $body, $chunkFile, $chunksToRetry)) {
                $result = false;
            }
        }

        $jobData['summary']['aggregateTime'] = $this->processingHelper->calculateAggregateTime(
            $startTimestamp,
            $previousAggregateTime
        );
        $job->setData($jobData);

        return $result;
    }

    private function retryChunk(
        JobRunner $jobRunner,
        Job $job,
        array $body,
        ChunkFile $chunkFile,
        string $retryReason
    ): bool {
        $this->logger->info(sprintf('The retry requested. Reason: %s', $retryReason));

        $chunkCount = $this->updateChunkCount($body['operationId'], 1);
        if (null === $chunkCount) {
            return false;
        }

        $jobRunner->createDelayed(
            $this->getRetryChunkJobName($job->getName()),
            function (JobRunner $jobRunner, Job $job) use ($body, $chunkFile, $retryReason) {
                $jobData = $job->getData();
                $jobData['retryReason'] = $retryReason;
                $job->setData($jobData);
                $this->jobManager->saveJob($job);

                $this->processingHelper->sendProcessChunkMessage(
                    $body,
                    $job,
                    $chunkFile,
                    $body['extra_chunk']
                );

                return true;
            }
        );

        return true;
    }

    private function getRetryChunkJobName(string $jobName): string
    {
        $pos = strrpos($jobName, ':r');
        if (false !== $pos) {
            $retryNumber = substr($jobName, $pos + 2);
            if (is_numeric($retryNumber)) {
                return sprintf('%s:r%d', substr($jobName, 0, $pos), (int)$retryNumber + 1);
            }
        }

        return $jobName . ':r1';
    }

    private function processChunksToRetry(
        JobRunner $jobRunner,
        Job $job,
        array $body,
        ChunkFile $parentChunkFile,
        array $chunksToRetry
    ): bool {
        $requestType = new RequestType($body['requestType']);
        $dataEncoder = $this->dataEncoderRegistry->getEncoder($requestType);
        if (null === $dataEncoder) {
            $this->logger->error(sprintf('Cannot get data encoder. Request Type: %s.', (string)$requestType));

            return false;
        }

        $numberOfChunksToRetry = \count($chunksToRetry);
        $chunkCount = $this->updateChunkCount($body['operationId'], $numberOfChunksToRetry);
        if (null === $chunkCount) {
            return false;
        }

        $chunkFileNames = [];
        $chunkFileNameTemplate = $parentChunkFile->getFileName() . '_%d';
        $chunkFileIndex = 0;
        foreach ($chunksToRetry as [$recordOffset, $items]) {
            $chunkFileName = sprintf($chunkFileNameTemplate, $chunkFileIndex);
            $this->fileManager->writeToStorage($dataEncoder->encodeItems($items), $chunkFileName);
            $chunkFileNames[] = [$recordOffset, $chunkFileIndex, $chunkFileName];
            $chunkFileIndex++;
        }

        $firstFileIndex = $chunkCount - $numberOfChunksToRetry;
        $firstRecordOffset = $parentChunkFile->getFirstRecordOffset();
        $sectionName = $parentChunkFile->getSectionName();
        foreach ($chunkFileNames as [$recordOffset, $chunkFileIndex, $chunkFileName]) {
            $this->createChunkJob(
                $jobRunner,
                sprintf('%s:%d', $job->getName(), $chunkFileIndex + 1),
                $body,
                new ChunkFile(
                    $chunkFileName,
                    $firstFileIndex + $chunkFileIndex,
                    $firstRecordOffset + $recordOffset,
                    $sectionName
                )
            );
        }

        return true;
    }

    private function createChunkJob(
        JobRunner $jobRunner,
        string $jobName,
        array $parentBody,
        ChunkFile $chunkFile
    ): void {
        $jobRunner->createDelayed(
            $jobName,
            function (JobRunner $jobRunner, Job $job) use ($parentBody, $chunkFile) {
                $this->processingHelper->sendProcessChunkMessage($parentBody, $job, $chunkFile, true);

                return true;
            }
        );
    }

    private function updateChunkCount(int $operationId, int $numberOfNewChunks): ?int
    {
        $infoFileName = $this->fileNameProvider->getInfoFileName($operationId);
        $lockFileName = $this->fileNameProvider->getLockFileName($infoFileName);
        if (!$this->fileLockManager->acquireLock($lockFileName)) {
            $this->logger->error(sprintf(
                'Cannot update the chunk count. Reason:'
                . ' Failed to update the info file "%s" because the lock cannot be acquired.',
                $infoFileName
            ));

            return null;
        }

        try {
            $chunkCount = $this->writeChunkCount($infoFileName, $numberOfNewChunks);
        } catch (\Throwable $e) {
            $chunkCount = null;
            $this->logger->error(
                sprintf('Cannot update the chunk count. Reason: Failed to update the info file "%s".', $infoFileName),
                ['exception' => $e]
            );
        } finally {
            $this->fileLockManager->releaseLock($lockFileName);
        }

        return $chunkCount;
    }

    private function writeChunkCount(string $infoFileName, int $numberOfNewChunks): int
    {
        $data = JsonUtil::decode($this->fileManager->getFileContent($infoFileName));
        $chunkCount = $data['chunkCount'] + $numberOfNewChunks;
        $data['chunkCount'] = $chunkCount;
        $this->fileManager->writeToStorage(JsonUtil::encode($data), $infoFileName);

        return $chunkCount;
    }
}
