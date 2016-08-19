<?php
namespace Oro\Component\MessageQueue\Job;

use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

class DependentJobMessageProcessor implements MessageProcessorInterface
{
    /**
     * @var JobStorage
     */
    private $jobStorage;

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
     * @param MessageProducerInterface $producer
     * @param LoggerInterface $logger
     */
    public function __construct(JobStorage $jobStorage, MessageProducerInterface $producer, LoggerInterface $logger)
    {
        $this->jobStorage = $jobStorage;
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
            $this->logger->critical(sprintf(
                '[DependentJobMessageProcessor] Got invalid message. body: "%s"',
                $message->getBody()
            ));

            return self::REJECT;
        }

        $job = $this->jobStorage->findJobById($data['jobId']);
        if (! $job) {
            $this->logger->critical(sprintf(
                '[DependentJobMessageProcessor] Job was not found. id: "%s"',
                $data['jobId']
            ));

            return self::REJECT;
        }

        if (! $job->isRoot()) {
            $this->logger->critical(sprintf(
                '[DependentJobMessageProcessor] Expected root job but got child. id: "%s"',
                $data['jobId']
            ));

            return self::REJECT;
        }

        $jobData = $job->getData();

        if (! isset($jobData['dependentJob'])) {
            return self::ACK;
        }

        $dependentJob = $jobData['dependentJob'];

        if (! isset($dependentJob['topic']) || ! isset($dependentJob['message'])) {
            $this->logger->critical(sprintf(
                '[DependentJobMessageProcessor] Got invalid dependent job data. job: "%s", dependentJob: "%s"',
                $job->getId(),
                JSON::encode($dependentJob)
            ));

            return self::REJECT;
        }

        $priority = isset($dependentJob['priority']) ? $dependentJob['priority'] : MessagePriority::NORMAL;

        $this->producer->send($dependentJob['topic'], $dependentJob['message'], $priority);

        return self::ACK;
    }
}
