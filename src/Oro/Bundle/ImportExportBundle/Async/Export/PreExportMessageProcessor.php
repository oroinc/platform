<?php
namespace Oro\Bundle\ImportExportBundle\Async\Export;

use Psr\Log\LoggerInterface;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\ImportExportBundle\Handler\ExportHandler;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenSerializerInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\DependentJobService;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;

class PreExportMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var ExportHandler
     */
    private $exportHandler;

    /**
     * @var JobRunner
     */
    private $jobRunner;

    /**
     * @var MessageProducerInterface
     */
    private $producer;

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var DependentJobService
     */
    private $dependentJob;

    /**
     * @var TokenSerializerInterface
     */
    private $tokenSerializer;

    /**
     * @var integer
     */
    private $sizeOfBatch;

    /**
     * @param ExportHandler $exportHandler
     * @param JobRunner $jobRunner
     * @param MessageProducerInterface $producer
     * @param DoctrineHelper $doctrineHelper
     * @param TokenStorageInterface $tokenStorage
     * @param LoggerInterface $logger
     * @param DependentJobService $dependentJob
     * @param integer $sizeOfBatch
     */
    public function __construct(
        ExportHandler $exportHandler,
        JobRunner $jobRunner,
        MessageProducerInterface $producer,
        DoctrineHelper $doctrineHelper,
        TokenStorageInterface $tokenStorage,
        LoggerInterface $logger,
        DependentJobService $dependentJob,
        $sizeOfBatch
    ) {
        $this->exportHandler = $exportHandler;
        $this->jobRunner = $jobRunner;
        $this->producer = $producer;
        $this->doctrineHelper = $doctrineHelper;
        $this->tokenStorage = $tokenStorage;
        $this->logger = $logger;
        $this->dependentJob = $dependentJob;
        $this->sizeOfBatch = $sizeOfBatch;
    }

    /**
     * @param TokenSerializerInterface $tokenSerializer
     */
    public function setTokenSerializer(TokenSerializerInterface $tokenSerializer)
    {
        $this->tokenSerializer = $tokenSerializer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        if (! ($body = $this->getMessageBody($message))) {
            return self::REJECT;
        }

        $jobUniqueName = sprintf(
            'oro_importexport.pre_export.%s.user_%s',
            $body['jobName'],
            $this->getUser()->getId()
        );

        if (isset($body['organizationId'])) {
            $body['options']['organization'] = $this->doctrineHelper
                ->getEntityRepository(Organization::class)
                ->find($body['organizationId']);
        }

        $result = $this->jobRunner->runUnique(
            $message->getMessageId(),
            $jobUniqueName,
            function (JobRunner $jobRunner, Job $job) use ($body, $jobUniqueName) {
                $exportingEntityIds = $this->exportHandler->getExportingEntityIds(
                    $body['jobName'],
                    $body['exportType'],
                    $body['processorAlias'],
                    $body['options']
                );

                unset($body['options']['organization']);

                $ids = $this->splitOnBatch($exportingEntityIds);
                if (empty($ids)) {
                    $jobRunner->createDelayed(
                        sprintf('%s.chunk.%s', $jobUniqueName, 1),
                        function (JobRunner $jobRunner, Job $child) use ($body) {
                            $this->producer->send(
                                Topics::EXPORT,
                                array_merge($body, ['jobId' => $child->getId()])
                            );
                        }
                    );
                }
                foreach ($ids as $key => $batchData) {
                    $jobRunner->createDelayed(
                        sprintf(
                            '%s.chunk.%s',
                            $jobUniqueName,
                            ++$key
                        ),
                        function (JobRunner $jobRunner, Job $child) use ($body, $batchData) {
                            $body['options']['ids'] = $batchData;
                            $this->producer->send(
                                Topics::EXPORT,
                                array_merge($body, ['jobId' => $child->getId()])
                            );
                        }
                    );
                }

                $this->addDependedJob($job->getRootJob(), $body);

                return true;
            }
        );

        return $result ? self::ACK : self::REJECT;
    }

    /**
     * @param MessageInterface $message
     * @return boolean| array
     */
    private function getMessageBody(MessageInterface $message)
    {
        $body = JSON::decode($message->getBody());
        $body = array_replace_recursive([
                'jobName' => null,
                'processorAlias' => null,
                'securityToken' => null,
                'outputFormat' => 'csv',
                'organizationId' => null,
                'exportType' => ProcessorRegistry::TYPE_EXPORT,
                'options' => [],
            ], $body);

        if (! isset($body['jobName'], $body['processorAlias'], $body['securityToken'])) {
            $this->logger->critical(
                sprintf('[PreExportMessageProcessor] Got invalid message: "%s"', $message->getBody()),
                ['message' => $message]
            );

            return false;
        }

        if (! $this->setSecurityToken($body['securityToken'])) {
            $this->logger->critical(
                sprintf('[PreExportMessageProcessor] Cannot set security token'),
                ['message' => $message]
            );

            return false;
        }

        if (! ($user = $this->getUser()) instanceof UserInterface && $user->getEmail()) {
            $this->logger->critical(
                sprintf('[PreExportMessageProcessor] Cannot get User from Token'),
                ['message' => $message]
            );

            return false;
        }

        return $body;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::PRE_EXPORT];
    }

    /**
     * @param string $serializedToken
     *
     * @return bool
     */
    private function setSecurityToken($serializedToken)
    {
        $token = $this->tokenSerializer->deserialize($serializedToken);

        if (null === $token) {
            return false;
        }

        $this->tokenStorage->setToken($token);

        return true;
    }

    /**
     * @return UserInterface
     *
     * @throws \RuntimeException
     */
    private function getUser()
    {
        $token = $this->tokenStorage->getToken();

        if (null === $token) {
            throw new \RuntimeException('Security token is null');
        }

        return $token->getUser();
    }

    /**
     * @param array $ids
     * @return array
     */
    private function splitOnBatch(array $ids)
    {
        return array_chunk($ids, $this->sizeOfBatch);
    }

    /**
     * @param Job   $rootJob
     * @param array $body
     */
    private function addDependedJob(Job $rootJob, array $body)
    {
        $context = $this->dependentJob->createDependentJobContext($rootJob);

        $context->addDependentJob(
            Topics::POST_EXPORT,
            [
                'jobId' => $rootJob->getId(),
                'email' => $this->getUser()->getEmail(),
                'jobName' => $body['jobName'],
                'exportType' => $body['exportType'],
                'outputFormat' => $body['outputFormat'],
            ]
        );

        $this->dependentJob->saveDependentJob($context);
    }
}
