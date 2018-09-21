<?php
namespace Oro\Bundle\ImportExportBundle\Async\Export;

use Oro\Bundle\ImportExportBundle\Async\ImportExportResultSummarizer;
use Oro\Bundle\ImportExportBundle\Async\Topics;
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
use Symfony\Component\Security\Core\User\UserInterface;

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
     * @var int
     */
    protected $batchSize;

    /**
     * @param JobRunner $jobRunner
     * @param MessageProducerInterface $producer
     * @param TokenStorageInterface $tokenStorage
     * @param DependentJobService $dependentJob
     * @param LoggerInterface $logger
     * @param int $sizeOfBatch
     */
    public function __construct(
        JobRunner $jobRunner,
        MessageProducerInterface $producer,
        TokenStorageInterface $tokenStorage,
        DependentJobService $dependentJob,
        LoggerInterface $logger,
        $sizeOfBatch
    ) {
        $this->jobRunner = $jobRunner;
        $this->producer = $producer;
        $this->tokenStorage = $tokenStorage;
        $this->logger = $logger;
        $this->dependentJob = $dependentJob;
        $this->batchSize = $sizeOfBatch;
    }

    /**
     * {@inheritDoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        if (! ($body = $this->getMessageBody($message))) {
            return self::REJECT;
        }

        $jobUniqueName = $this->getJobUniqueName($body);

        $result = $this->jobRunner->runUnique(
            $message->getMessageId(),
            $jobUniqueName,
            $this->getRunUniqueJobCallback($jobUniqueName, $body)
        );

        return $result ? self::ACK : self::REJECT;
    }

    /**
     * @param string $jobUniqueName
     * @param array $body
     *
     * @return \Closure
     */
    protected function getRunUniqueJobCallback($jobUniqueName, array $body)
    {
        return function (JobRunner $jobRunner, Job $job) use ($jobUniqueName, $body) {
            $exportingEntityIds = $this->getExportingEntityIds($body);

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

            $this->addDependentJob($job->getRootJob(), $body);

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

        if (! is_object($user) || ! $user instanceof UserInterface
            || ! method_exists($user, 'getId') || ! method_exists($user, 'getEmail')
        ) {
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
        return array_chunk($ids, $this->batchSize);
    }

    /**
     * @param Job   $rootJob
     * @param array $body
     */
    protected function addDependentJob(Job $rootJob, array $body)
    {
        $context = $this->dependentJob->createDependentJobContext($rootJob);

        $context->addDependentJob(Topics::POST_EXPORT, [
            'jobId' => $rootJob->getId(),
            'email' => $this->getUser()->getEmail(),
            'recipientUserId' => $this->getUser()->getId(),
            'jobName' => $body['jobName'],
            'exportType' => $body['exportType'],
            'outputFormat' => $body['outputFormat'],
            'notificationTemplate' =>
                $body['notificationTemplate'] ?? ImportExportResultSummarizer::TEMPLATE_EXPORT_RESULT,
        ]);

        $this->dependentJob->saveDependentJob($context);
    }

    /**
     * @param array $body
     *
     * @return string
     */
    abstract protected function getJobUniqueName(array $body);

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
