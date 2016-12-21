<?php
namespace Oro\Component\MessageQueue\Job;

use Psr\Log\LoggerInterface;

use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;

class CalculateRootJobProgressProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var JobStorage
     */
    private $jobStorage;

    /**
     * @var RootJobProgressCalculator
     */
    private $rootJobProgressCalculator;

    /**
     * @var MessageProducerInterface
     */
    private $producer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param JobStorage $jobStorage
     * @param RootJobProgressCalculator $rootJobProgressCalculator
     * @param MessageProducerInterface $producer
     * @param LoggerInterface $logger
     */
    public function __construct(
        JobStorage $jobStorage,
        RootJobProgressCalculator $rootJobProgressCalculator,
        MessageProducerInterface $producer,
        LoggerInterface $logger
    ) {
        $this->jobStorage = $jobStorage;
        $this->rootJobProgressCalculator = $rootJobProgressCalculator;
        $this->producer = $producer;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $data = JSON::decode($message->getBody());

        if (! isset($data['jobId'])) {
            $this->logger->critical(
                sprintf('Got invalid message. body: "%s"', $message->getBody()),
                ['message' => $message]
            );

            return self::REJECT;
        }

        $job = $this->jobStorage->findJobById($data['jobId']);
        if (! $job) {
            $this->logger->critical(
                sprintf('Job was not found. id: "%s"', $data['jobId']),
                ['message' => $message]
            );

            return self::REJECT;
        }

        $this->rootJobProgressCalculator->calculate($job);

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::CALCULATE_ROOT_JOB_PROGRESS];
    }
}
