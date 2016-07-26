<?php
namespace Oro\Component\MessageQueue\Job;

use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

class CalculateRootJobStatusProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var JobStorage
     */
    protected $jobStorage;

    /**
     * @var CalculateRootJobStatusCase
     */
    protected $calculateRootJobStatusCase;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param JobStorage                 $jobStorage
     * @param CalculateRootJobStatusCase $calculateRootJobStatusCase
     * @param LoggerInterface            $logger
     */
    public function __construct(
        JobStorage $jobStorage,
        CalculateRootJobStatusCase $calculateRootJobStatusCase,
        LoggerInterface $logger
    ) {
        $this->jobStorage = $jobStorage;
        $this->calculateRootJobStatusCase = $calculateRootJobStatusCase;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $data = JSON::decode($message->getBody());

        if (! isset($data['id'])) {
            $this->logger->critical(sprintf('Got invalid message. body: "%s"', $message->getBody()));

            return self::REJECT;
        }

        $job = $this->jobStorage->findJobById($data['id']);
        if (! $job) {
            $this->logger->critical(sprintf('Job was not found. id: "%s"', $data['id']));

            return self::REJECT;
        }

        $this->calculateRootJobStatusCase->calculate($job);

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
