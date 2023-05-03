<?php

namespace Oro\Bundle\ApiBundle\Batch\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ApiBundle\Batch\Async\Topic\UpdateListCreateChunkJobsTopic;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\MessageQueueBundle\Entity\Repository\JobRepository;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

/**
 * Creates child jobs that are used to process API batch operation chunks.
 * This processor is executed only if a API batch update request contains a lot of data
 * and UpdateListMessageProcessor requests delayed creation of child jobs.
 */
class UpdateListCreateChunkJobsMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    private JobRunner $jobRunner;
    private ManagerRegistry $doctrine;
    private AsyncOperationManager $operationManager;
    private UpdateListProcessingHelper $processingHelper;
    private LoggerInterface $logger;
    private int $batchSize = 2000;

    public function __construct(
        JobRunner $jobRunner,
        ManagerRegistry $doctrine,
        AsyncOperationManager $operationManager,
        UpdateListProcessingHelper $processingHelper,
        LoggerInterface $logger
    ) {
        $this->jobRunner = $jobRunner;
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
        return [UpdateListCreateChunkJobsTopic::getName()];
    }

    /**
     * Sets the maximum number of jobs that this processor can create at the one iteration.
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
        $chunkJobNameTemplate = $messageBody['chunkJobNameTemplate'];
        $firstChunkFileIndex = $messageBody['firstChunkFileIndex'];
        $maxChunkFileIndex = $this->processingHelper->getChunkIndexCount($operationId) - 1;
        $lastChunkFileIndex = $firstChunkFileIndex + $this->batchSize - 1;
        if ($lastChunkFileIndex > $maxChunkFileIndex) {
            $lastChunkFileIndex = $maxChunkFileIndex;
        }

        $nextChunkFileIndex = $this->processingHelper->createChunkJobs(
            $this->jobRunner->getJobRunnerForChildJob($rootJob),
            $operationId,
            $chunkJobNameTemplate,
            $firstChunkFileIndex,
            $lastChunkFileIndex
        );

        if ($nextChunkFileIndex <= $maxChunkFileIndex) {
            // do the next iteration
            $this->processingHelper->sendMessageToCreateChunkJobs(
                $rootJob,
                $chunkJobNameTemplate,
                $messageBody,
                $nextChunkFileIndex,
                $this->processingHelper->calculateAggregateTime($startTimestamp, $messageBody['aggregateTime'])
            );
        } else {
            // the creation of chunk jobs finished
            $this->processingHelper->sendMessageToStartChunkJobs($rootJob, $messageBody);
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
