<?php
namespace Oro\Component\MessageQueue\Job;

use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

class DependentJobMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
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

        if (! isset($jobData['dependentJobs'])) {
            return self::ACK;
        }

        $dependentJobs = $jobData['dependentJobs'];

        foreach ($dependentJobs as $dependentJob) {
            if (! isset($dependentJob['topic']) || ! isset($dependentJob['message'])) {
                $this->logger->critical(sprintf(
                    '[DependentJobMessageProcessor] Got invalid dependent job data. job: "%s", dependentJob: "%s"',
                    $job->getId(),
                    JSON::encode($dependentJob)
                ));

                return self::REJECT;
            }
        }

        foreach ($dependentJobs as $dependentJob) {
            if (isset($dependentJob['priority'])) {
                $this->producer->send($dependentJob['topic'], $dependentJob['message'], $dependentJob['priority']);
            } else {
                $this->producer->send($dependentJob['topic'], $dependentJob['message']);
            }
        }

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::ROOT_JOB_STOPPED];
    }
}
