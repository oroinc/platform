<?php

namespace Oro\Bundle\ApiBundle\Batch\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ApiBundle\Batch\Async\Topic\UpdateListStartChunkJobsTopic;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\MessageQueueBundle\Entity\Repository\JobRepository;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

/**
 * Sends messages to start child jobs that are used to process API batch operation chunks.
 * This processor is executes after all child jobs are created by UpdateListCreateChunkJobsMessageProcessor.
 */
class UpdateListStartChunkJobsMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    private ManagerRegistry $doctrine;
    private AsyncOperationManager $operationManager;
    private UpdateListProcessingHelper $processingHelper;
    private LoggerInterface $logger;
    private int $batchSize = 3000;

    public function __construct(
        ManagerRegistry $doctrine,
        AsyncOperationManager $operationManager,
        UpdateListProcessingHelper $processingHelper,
        LoggerInterface $logger
    ) {
        $this->doctrine = $doctrine;
        $this->operationManager = $operationManager;
        $this->processingHelper = $processingHelper;
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedTopics(): array
    {
        return [UpdateListStartChunkJobsTopic::getName()];
    }

    /**
     * Sets the maximum number of MQ messages that this processor can send at the one iteration.
     */
    public function setBatchSize(int $batchSize): void
    {
        $this->batchSize = $batchSize;
    }

    /**
     * {@inheritDoc}
     */
    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $startTimestamp = microtime(true);
        $messageBody = $message->getBody();

        $rootJob = $this->getJobRepository()->findJobById($messageBody['rootJobId']);
        if (null === $rootJob) {
            $this->logger->critical('The root job does not exist.');

            return self::REJECT;
        }

        $operationId = $messageBody['operationId'];
        $firstChunkFileIndex = $messageBody['firstChunkFileIndex'];
        $chunkFiles = $this->processingHelper->loadChunkIndex($operationId);
        $chunkFileToJobIdMap = $this->processingHelper->loadChunkJobIndex($operationId);
        $maxChunkFileIndex = \count($chunkFiles) - 1;
        $lastChunkFileIndex = $firstChunkFileIndex + $this->batchSize - 1;
        if ($lastChunkFileIndex > $maxChunkFileIndex) {
            $lastChunkFileIndex = $maxChunkFileIndex;
        }
        $nextChunkFileIndex = $firstChunkFileIndex;
        while ($nextChunkFileIndex <= $lastChunkFileIndex) {
            $job = $this->getJobRepository()->findJobById((int)$chunkFileToJobIdMap[$nextChunkFileIndex]);
            if (null === $job) {
                $this->logger->critical(
                    'The child job does not exist.',
                    ['jobId' => $chunkFileToJobIdMap[$nextChunkFileIndex]]
                );

                return self::REJECT;
            }
            $this->processingHelper->sendProcessChunkMessage($messageBody, $job, $chunkFiles[$nextChunkFileIndex]);
            $nextChunkFileIndex++;
        }

        if ($nextChunkFileIndex <= $maxChunkFileIndex) {
            // do the next iteration
            $this->processingHelper->sendMessageToStartChunkJobs(
                $rootJob,
                $messageBody,
                $nextChunkFileIndex,
                $this->processingHelper->calculateAggregateTime($startTimestamp, $messageBody['aggregateTime'])
            );
        } else {
            // all messages that start chunk jobs were sent
            $this->processingHelper->deleteChunkIndex($operationId);
            $this->processingHelper->deleteChunkJobIndex($operationId);
            $this->operationManager->incrementAggregateTime(
                $operationId,
                $this->processingHelper->calculateAggregateTime($startTimestamp, $messageBody['aggregateTime'])
            );
        }

        return self::ACK;
    }

    private function getJobRepository(): JobRepository
    {
        return $this->doctrine->getRepository(Job::class);
    }
}
