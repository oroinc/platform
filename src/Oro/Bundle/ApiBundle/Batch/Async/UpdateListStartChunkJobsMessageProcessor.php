<?php

namespace Oro\Bundle\ApiBundle\Batch\Async;

use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobStorage;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

/**
 * Sends messages to start child jobs that are used to process API batch operation chunks.
 * This processor is executes after all child jobs are created by UpdateListCreateChunkJobsMessageProcessor.
 */
class UpdateListStartChunkJobsMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /** @var JobStorage */
    private $jobStorage;

    /** @var AsyncOperationManager */
    private $operationManager;

    /** @var UpdateListProcessingHelper */
    private $processingHelper;

    /** @var LoggerInterface */
    private $logger;

    /** @var int */
    private $batchSize = 3000;

    /**
     * @param JobStorage                 $jobStorage
     * @param AsyncOperationManager      $operationManager
     * @param UpdateListProcessingHelper $processingHelper
     * @param LoggerInterface            $logger
     */
    public function __construct(
        JobStorage $jobStorage,
        AsyncOperationManager $operationManager,
        UpdateListProcessingHelper $processingHelper,
        LoggerInterface $logger
    ) {
        $this->jobStorage = $jobStorage;
        $this->operationManager = $operationManager;
        $this->processingHelper = $processingHelper;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::UPDATE_LIST_START_CHUNK_JOBS];
    }

    /**
     * Sets the maximum number of MQ messages that this processor can send at the one iteration.
     *
     * @param int $batchSize
     */
    public function setBatchSize(int $batchSize): void
    {
        $this->batchSize = $batchSize;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $startTimestamp = microtime(true);
        $body = JSON::decode($message->getBody());
        if (!isset(
            $body['operationId'],
            $body['entityClass'],
            $body['requestType'],
            $body['version'],
            $body['rootJobId']
        )) {
            $this->logger->critical('Got invalid message.', ['message' => $message]);

            return self::REJECT;
        }

        $rootJob = $this->jobStorage->findJobById($body['rootJobId']);
        if (null === $rootJob) {
            $this->logger->critical('The root job does not exist.', ['message' => $message]);

            return self::REJECT;
        }

        $operationId = $body['operationId'];
        $firstChunkFileIndex = $body['firstChunkFileIndex'] ?? 0;
        $chunkFiles = $this->processingHelper->loadChunkIndex($operationId);
        $chunkFileToJobIdMap = $this->processingHelper->loadChunkJobIndex($operationId);
        $maxChunkFileIndex = count($chunkFiles) - 1;
        $lastChunkFileIndex = $firstChunkFileIndex + $this->batchSize - 1;
        if ($lastChunkFileIndex > $maxChunkFileIndex) {
            $lastChunkFileIndex = $maxChunkFileIndex;
        }
        $nextChunkFileIndex = $firstChunkFileIndex;
        while ($nextChunkFileIndex <= $lastChunkFileIndex) {
            $job = $this->jobStorage->findJobById($chunkFileToJobIdMap[$nextChunkFileIndex]);
            if (null === $job) {
                $this->logger->critical(
                    'The child job does not exist.',
                    ['message' => $message, 'jobId' => $chunkFileToJobIdMap[$nextChunkFileIndex]]
                );

                return self::REJECT;
            }
            $this->processingHelper->sendProcessChunkMessage($body, $job, $chunkFiles[$nextChunkFileIndex]);
            $nextChunkFileIndex++;
        }

        if ($nextChunkFileIndex <= $maxChunkFileIndex) {
            // do the next iteration
            $this->processingHelper->sendMessageToStartChunkJobs(
                $rootJob,
                $body,
                $nextChunkFileIndex,
                $this->processingHelper->calculateAggregateTime($startTimestamp, $body['aggregateTime'] ?? 0)
            );
        } else {
            // all messages that start chunk jobs were sent
            $this->processingHelper->deleteChunkIndex($operationId);
            $this->processingHelper->deleteChunkJobIndex($operationId);
            $this->operationManager->incrementAggregateTime(
                $operationId,
                $this->processingHelper->calculateAggregateTime($startTimestamp, $body['aggregateTime'] ?? 0)
            );
        }

        return self::ACK;
    }
}
