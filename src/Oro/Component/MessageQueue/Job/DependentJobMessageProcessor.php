<?php

namespace Oro\Component\MessageQueue\Job;

use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

class DependentJobMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /** @var JobStorage */
    private $jobStorage;

    /** @var MessageProducerInterface */
    private $producer;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param JobStorage               $jobStorage
     * @param MessageProducerInterface $producer
     * @param LoggerInterface          $logger
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
        if (!isset($data['jobId'])) {
            $this->logger->critical('Got invalid message');

            return self::REJECT;
        }

        $job = $this->jobStorage->findJobById($data['jobId']);
        if (null === $job) {
            $this->logger->critical(sprintf('Job was not found. id: "%s"', $data['jobId']));

            return self::REJECT;
        }
        if (!$job->isRoot()) {
            $this->logger->critical(sprintf('Expected root job but got child. id: "%s"', $data['jobId']));

            return self::REJECT;
        }

        $jobData = $job->getData();
        if (!isset($jobData['dependentJobs'])) {
            return self::ACK;
        }

        $dependentJobs = $jobData['dependentJobs'];
        if (!$this->validateDependentJobs($dependentJobs, $job)) {
            return self::REJECT;
        }

        $this->processDependentJobs($dependentJobs, $job);

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::ROOT_JOB_STOPPED];
    }

    /**
     * @param array $dependentJobs
     * @param Job   $job
     *
     * @return bool
     */
    private function validateDependentJobs(array $dependentJobs, Job $job)
    {
        $result = true;
        foreach ($dependentJobs as $dependentJob) {
            if (!isset($dependentJob['topic'], $dependentJob['message'])) {
                $this->logger->critical(sprintf(
                    'Got invalid dependent job data. job: "%s", dependentJob: "%s"',
                    $job->getId(),
                    JSON::encode($dependentJob)
                ));
                $result = false;
                break;
            }
        }

        return $result;
    }

    /**
     * @param array $dependentJobs
     * @param Job   $job
     */
    private function processDependentJobs(array $dependentJobs, Job $job)
    {
        foreach ($dependentJobs as $dependentJob) {
            $jobMessage = new Message();
            $jobMessage->setBody($dependentJob['message']);
            if (isset($dependentJob['priority'])) {
                $jobMessage->setPriority($dependentJob['priority']);
            }
            $jobProperties = $job->getProperties();
            if (!empty($jobProperties)) {
                $jobMessage->setProperties($jobProperties);
            }

            $this->producer->send($dependentJob['topic'], $jobMessage);
        }
    }
}
