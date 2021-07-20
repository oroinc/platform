<?php

namespace Oro\Bundle\ApiBundle\Batch\Async;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\MessageQueueBundle\Entity\Repository\JobRepository;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

/**
 * Creates child jobs that are used to process API batch operation chunks.
 * This processor is executed only if a API batch update request contains a lot of data
 * and UpdateListMessageProcessor requests delayed creation of child jobs.
 */
class UpdateListCreateChunkJobsMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /** @var JobRunner */
    private $jobRunner;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var AsyncOperationManager */
    private $operationManager;

    /** @var UpdateListProcessingHelper */
    private $processingHelper;

    /** @var LoggerInterface */
    private $logger;

    /** @var int */
    private $batchSize = 2000;

    public function __construct(
        JobRunner $jobRunner,
        DoctrineHelper $doctrineHelper,
        AsyncOperationManager $operationManager,
        UpdateListProcessingHelper $processingHelper,
        LoggerInterface $logger
    ) {
        $this->jobRunner = $jobRunner;
        $this->doctrineHelper = $doctrineHelper;
        $this->operationManager = $operationManager;
        $this->processingHelper = $processingHelper;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::UPDATE_LIST_CREATE_CHUNK_JOBS];
    }

    /**
     * Sets the maximum number of jobs that this processor can create at the one iteration.
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
            $body['rootJobId'],
            $body['chunkJobNameTemplate']
        )) {
            $this->logger->critical('Got invalid message.');

            return self::REJECT;
        }

        $rootJob = $this->getJobRepository()->findJobById((int)$body['rootJobId']);
        if (null === $rootJob) {
            $this->logger->critical('The root job does not exist.');

            return self::REJECT;
        }

        $operationId = $body['operationId'];
        $chunkJobNameTemplate = $body['chunkJobNameTemplate'];
        $firstChunkFileIndex = $body['firstChunkFileIndex'] ?? 0;
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
                $body,
                $nextChunkFileIndex,
                $this->processingHelper->calculateAggregateTime($startTimestamp, $body['aggregateTime'] ?? 0)
            );
        } else {
            // the creation of chunk jobs finished
            $this->processingHelper->sendMessageToStartChunkJobs($rootJob, $body);
            $this->operationManager->incrementAggregateTime(
                $operationId,
                $this->processingHelper->calculateAggregateTime($startTimestamp, $body['aggregateTime'] ?? 0)
            );
        }

        return self::ACK;
    }

    /**
     * @return JobRepository|EntityRepository
     */
    private function getJobRepository(): JobRepository
    {
        return $this->doctrineHelper->getEntityRepository(Job::class);
    }
}
