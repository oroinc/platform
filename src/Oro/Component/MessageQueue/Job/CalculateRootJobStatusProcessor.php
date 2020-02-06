<?php

namespace Oro\Component\MessageQueue\Job;

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
    /** @var JobStorage */
    private $jobStorage;

    /** @var RootJobStatusCalculatorInterface */
    private $rootJobStatusCalculator;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param JobStorage $jobStorage
     * @param RootJobStatusCalculatorInterface $calculateRootJobStatusCase
     * @param LoggerInterface $logger
     */
    public function __construct(
        JobStorage $jobStorage,
        RootJobStatusCalculatorInterface $calculateRootJobStatusCase,
        LoggerInterface $logger
    ) {
        $this->jobStorage = $jobStorage;
        $this->rootJobStatusCalculator = $calculateRootJobStatusCase;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $data = JSON::decode($message->getBody());

        if (!isset($data['jobId'])) {
            $this->logger->critical('Got invalid message. Job id is missing.');

            return self::REJECT;
        }

        $job = $this->jobStorage->findJobById($data['jobId']);
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
    public static function getSubscribedTopics()
    {
        return [Topics::CALCULATE_ROOT_JOB_STATUS];
    }
}
