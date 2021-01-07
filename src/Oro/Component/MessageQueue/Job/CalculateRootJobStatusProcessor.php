<?php

namespace Oro\Component\MessageQueue\Job;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

/**
 * Calculate root job status asynchronously.
 *
 * Deprecated, all root job statuses are calculated in the end of each job.
 * If such messages still exists in message broker they will be processed with current processor.
 */
class CalculateRootJobStatusProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /** @var RootJobStatusCalculatorInterface */
    private $rootJobStatusCalculator;

    /** @var ManagerRegistry */
    private $doctrine;

    /** @var string */
    private $entityClass;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param RootJobStatusCalculatorInterface $calculateRootJobStatusCase
     * @param ManagerRegistry $doctrine
     * @param string $entityClass
     * @param LoggerInterface $logger
     */
    public function __construct(
        RootJobStatusCalculatorInterface $calculateRootJobStatusCase,
        ManagerRegistry $doctrine,
        string $entityClass,
        LoggerInterface $logger
    ) {
        $this->rootJobStatusCalculator = $calculateRootJobStatusCase;
        $this->doctrine = $doctrine;
        $this->entityClass = $entityClass;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $data = JSON::decode($message->getBody());

        if (!isset($data['jobId'])) {
            $this->logger->critical('Got invalid message. Job id is missing.');

            return self::REJECT;
        }

        $job = $this->getJobRepository()->findJobById((int)$data['jobId']);
        if (!$job) {
            $this->logger->critical(sprintf('Job was not found. id: "%s"', $data['jobId']));

            return self::REJECT;
        }

        $this->rootJobStatusCalculator->calculate($job);

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics(): array
    {
        return [Topics::CALCULATE_ROOT_JOB_STATUS];
    }

    /**
     * @return JobRepositoryInterface
     */
    private function getJobRepository(): JobRepositoryInterface
    {
        return $this->doctrine->getManagerForClass($this->entityClass)->getRepository($this->entityClass);
    }
}
