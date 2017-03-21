<?php
namespace Oro\Bundle\ImportExportBundle\Async\Export;

use Psr\Log\LoggerInterface;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\ImportExportBundle\Handler\ExportHandler;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenSerializerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Job\JobStorage;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;

class ExportMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
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
     * @var LoggerInterface
     */
    private $jobStorage;

    /**
     * @var TokenSerializerInterface
     */
    private $tokenSerializer;

    /**
     * @param ExportHandler $exportHandler
     * @param JobRunner $jobRunner
     * @param DoctrineHelper $doctrineHelper
     * @param TokenStorageInterface $tokenStorage
     * @param LoggerInterface $logger
     * @param JobStorage $jobStorage
     */
    public function __construct(
        ExportHandler $exportHandler,
        JobRunner $jobRunner,
        DoctrineHelper $doctrineHelper,
        TokenStorageInterface $tokenStorage,
        LoggerInterface $logger,
        JobStorage $jobStorage
    ) {
        $this->exportHandler = $exportHandler;
        $this->jobRunner = $jobRunner;
        $this->doctrineHelper = $doctrineHelper;
        $this->tokenStorage = $tokenStorage;
        $this->logger = $logger;
        $this->jobStorage = $jobStorage;
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
        $body = JSON::decode($message->getBody());
        $body = array_replace_recursive([
            'jobName' => null,
            'processorAlias' => null,
            'securityToken' => null,
            'organizationId' => null,
            'exportType' => ProcessorRegistry::TYPE_EXPORT,
            'outputFormat' => 'csv',
            'outputFilePrefix' => null,
            'options' => [],
        ], $body);

        if (! isset($body['jobId'], $body['jobName'], $body['processorAlias'], $body['securityToken'])) {
            $this->logger->critical(
                sprintf('[ExportMessageProcessor] Got invalid message: "%s"', $message->getBody()),
                ['message' => $message]
            );

            return self::REJECT;
        }

        if (! $this->setSecurityToken($body['securityToken'])) {
            $this->logger->critical(
                sprintf('[ExportMessageProcessor] Cannot set security token'),
                ['message' => $message]
            );

            return self::REJECT;
        }

        if (isset($body['organizationId'])) {
            $body['options']['organization'] = $this->doctrineHelper->getEntityRepository(Organization::class)
                ->find($body['organizationId']);
        }

        $result = $this->jobRunner->runDelayed(
            $body['jobId'],
            function (JobRunner $jobRunner, Job $job) use ($body) {
                $exportResult = $this->exportHandler->getExportResult(
                    $body['jobName'],
                    $body['processorAlias'],
                    $body['exportType'],
                    $body['outputFormat'],
                    $body['outputFilePrefix'],
                    $body['options']
                );

                $this->logger->info(sprintf(
                    'Export result. Success: %s. ReadsCount: %s. ErrorsCount: %s',
                    $exportResult['success'] ? 'Yes' : 'No',
                    $exportResult['readsCount'],
                    $exportResult['errorsCount']
                ));

                $this->saveJobResult($job, $exportResult);

                return $exportResult['success'];
            }
        );

        return $result ? self::ACK : self::REJECT;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::EXPORT];
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
     * @param Job $job
     * @param array $data
     */
    private function saveJobResult(Job $job, array $data)
    {
        $this->jobStorage->saveJob($job, function (Job $job) use ($data) {
            $job->setData($data);
        });
    }
}
