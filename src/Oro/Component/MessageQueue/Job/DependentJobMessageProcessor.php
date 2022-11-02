<?php

namespace Oro\Component\MessageQueue\Job;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Topic\RootJobStoppedTopic;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

/**
 * Process list of jobs that should be started after when root job was finished (all child jobs is processed).
 */
class DependentJobMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /** @var MessageProducerInterface */
    private $producer;

    /** @var ManagerRegistry */
    private $doctrine;

    /** @var string */
    private $entityClass;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        MessageProducerInterface $producer,
        ManagerRegistry $doctrine,
        string $entityClass,
        LoggerInterface $logger
    ) {
        $this->producer = $producer;
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
    public static function getSubscribedTopics(): array
    {
        return [RootJobStoppedTopic::getName()];
    }

    private function validateDependentJobs(array $dependentJobs, Job $job): bool
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

    private function processDependentJobs(array $dependentJobs, Job $job): void
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

    /**
     * @return JobRepositoryInterface|ObjectRepository
     */
    private function getJobRepository(): JobRepositoryInterface
    {
        return $this->doctrine->getManagerForClass($this->entityClass)->getRepository($this->entityClass);
    }
}
