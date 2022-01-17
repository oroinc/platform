<?php

namespace Oro\Component\MessageQueue\Job;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Topic\CalculateRootJobStatusTopic;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
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
        $data = $message->getBody();

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
        return [CalculateRootJobStatusTopic::getName()];
    }

    private function getJobRepository(): JobRepositoryInterface
    {
        return $this->doctrine->getManagerForClass($this->entityClass)->getRepository($this->entityClass);
    }
}
