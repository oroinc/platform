<?php

namespace Oro\Bundle\ImportExportBundle\Async\Export;

use Oro\Bundle\ImportExportBundle\Async\Topic\PostExportTopic;
use Oro\Bundle\ImportExportBundle\Handler\ExportHandler;
use Oro\Bundle\UserBundle\Entity\UserInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\DependentJobService;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * A base class for entities related and grid related PreExportMessageProcessors.
 */
abstract class PreExportMessageProcessorAbstract implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var JobRunner
     */
    protected $jobRunner;

    /**
     * @var MessageProducerInterface
     */
    protected $producer;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var DependentJobService
     */
    protected $dependentJob;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ExportHandler
     */
    protected $exportHandler;

    /**
     * @var int
     */
    protected $batchSize;

    public function __construct(
        JobRunner $jobRunner,
        MessageProducerInterface $producer,
        TokenStorageInterface $tokenStorage,
        DependentJobService $dependentJob,
        LoggerInterface $logger,
        ExportHandler $exportHandler,
        $sizeOfBatch
    ) {
        $this->jobRunner = $jobRunner;
        $this->producer = $producer;
        $this->tokenStorage = $tokenStorage;
        $this->logger = $logger;
        $this->dependentJob = $dependentJob;
        $this->exportHandler = $exportHandler;
        $this->batchSize = $sizeOfBatch;
    }

    /**
     * {@inheritDoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $messageBody = $this->getMessageBody($message);
        if (!$messageBody) {
            return self::REJECT;
        }

        $result = $this->jobRunner->runUniqueByMessage(
            $message,
            $this->getRunUniqueJobCallback($messageBody)
        );

        return $result ? self::ACK : self::REJECT;
    }

    protected function getBatchSize(): int
    {
        return $this->batchSize;
    }

    /**
     * @param string $jobUniqueName
     * @param array $body
     *
     * @return \Closure
     */
    protected function getRunUniqueJobCallback(array $body)
    {
        return function (JobRunner $jobRunner, Job $job) use ($body) {
            $this->addDependentJob($job->getRootJob(), $body);
            $exportingEntityIds = $this->getExportingEntityIds($body);

            $jobUniqueName = $job->getName();
            $ids = $this->splitOnBatch($exportingEntityIds);
            if (empty($ids)) {
                $jobRunner->createDelayed(
                    sprintf('%s.chunk.%s', $jobUniqueName, 1),
                    $this->getDelayedJobCallback($body)
                );
            }

            foreach ($ids as $key => $batchData) {
                $jobRunner->createDelayed(
                    sprintf('%s.chunk.%s', $jobUniqueName, ++$key),
                    $this->getDelayedJobCallback($body, $batchData)
                );
            }

            return true;
        };
    }

    /**
     * @return UserInterface
     *
     * @throws \RuntimeException
     */
    protected function getUser()
    {
        $token = $this->tokenStorage->getToken();

        if (null === $token) {
            throw new \RuntimeException('Security token is null');
        }

        $user = $token->getUser();

        if (!is_object($user) || !$user instanceof UserInterface || !method_exists($user, 'getId')) {
            throw new \RuntimeException('Not supported user type');
        }

        return $user;
    }

    /**
     * @param array $ids
     *
     * @return array
     */
    protected function splitOnBatch(array $ids)
    {
        return array_chunk($ids, $this->getBatchSize());
    }

    protected function addDependentJob(Job $rootJob, array $body)
    {
        $context = $this->dependentJob->createDependentJobContext($rootJob);

        $context->addDependentJob(PostExportTopic::getName(), [
            'jobId' => $rootJob->getId(),
            'recipientUserId' => $this->getUser()->getId(),
            'jobName' => $body['jobName'],
            'exportType' => $body['exportType'],
            'outputFormat' => $body['outputFormat'],
            'entity' => $body['entity'],
        ]);

        $this->dependentJob->saveDependentJob($context);
    }

    /**
     * @param array $body
     *
     * @return array
     */
    abstract protected function getExportingEntityIds(array $body);

    /**
     * @param array $body
     * @param array $ids
     *
     * @return \Closure
     */
    abstract protected function getDelayedJobCallback(array $body, array $ids = []);

    /**
     * @param MessageInterface $message
     *
     * @return bool|array
     */
    abstract protected function getMessageBody(MessageInterface $message);
}
