<?php
namespace Oro\Bundle\DataGridBundle\Async\Export;

use Psr\Log\LoggerInterface;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\DataGridBundle\Async\Topics;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionExtension;
use Oro\Bundle\DataGridBundle\Handler\ExportHandler;
use Oro\Bundle\DataGridBundle\ImportExport\DatagridExportIdFetcher;
use Oro\Bundle\ImportExportBundle\Async\Topics as ImportExportTopics;
use Oro\Bundle\ImportExportBundle\Formatter\FormatterProvider;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
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
     * @var DatagridExportIdFetcher
     */
    private $exportIdReader;

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
     * @var int
     */
    private $batchSize;

    /**
     * @param ExportHandler $exportHandler
     * @param JobRunner $jobRunner
     * @param MessageProducerInterface $producer
     * @param DatagridExportIdFetcher $exportIdReader
     * @param TokenStorageInterface $tokenStorage
     * @param LoggerInterface $logger
     * @param DependentJobService $dependentJob
     * @param int $batchSize
     */
    public function __construct(
        ExportHandler $exportHandler,
        JobRunner $jobRunner,
        MessageProducerInterface $producer,
        DatagridExportIdFetcher $exportIdReader,
        TokenStorageInterface $tokenStorage,
        LoggerInterface $logger,
        DependentJobService $dependentJob,
        $batchSize
    ) {
        $this->exportHandler = $exportHandler;
        $this->jobRunner = $jobRunner;
        $this->producer = $producer;
        $this->exportIdReader = $exportIdReader;
        $this->tokenStorage = $tokenStorage;
        $this->logger = $logger;
        $this->dependentJob = $dependentJob;
        $this->batchSize = $batchSize;
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
            'format' => null,
            'batchSize' => $this->batchSize,
            'parameters' => [
                'gridName' => null,
                'gridParameters' => [],
                FormatterProvider::FORMAT_TYPE => 'excel',
            ],
            'securityToken' => null,
        ], $body);

        if (! isset($body['securityToken'], $body['parameters']['gridName'], $body['format'])) {
            $this->logger->critical(
                sprintf('[DataGridPreExportMessageProcessor] Got invalid message: "%s"', $message->getBody()),
                ['message' => $message]
            );

            return self::REJECT;
        }

        if (! $this->setSecurityToken($body['securityToken'])) {
            $this->logger->critical(
                sprintf('[DataGridPreExportMessageProcessor] Cannot set security token'),
                ['message' => $message]
            );

            return self::REJECT;
        }

        $originBody = $body;
        $contextParameters = new ParameterBag($body['parameters']['gridParameters']);
        $contextParameters->set(ActionExtension::ENABLE_ACTIONS_PARAMETER, false);
        $body['parameters']['gridParameters'] = $contextParameters;

        $jobUniqueName = sprintf(
            'oro_datagrid.pre_export.%s.user_%s.%s',
            $body['parameters']['gridName'],
            $this->getUser()->getId(),
            $body['format']
        );

        $result = $this->jobRunner->runUnique(
            $message->getMessageId(),
            $jobUniqueName,
            function (JobRunner $jobRunner, Job $job) use ($originBody, $body, $jobUniqueName) {
                $exportingEntityIds = $this->exportHandler->getExportingEntityIds(
                    $this->exportIdReader,
                    $body['parameters']
                );

                if (!empty($exportingEntityIds)) {
                    foreach ($this->splitOnBatch($exportingEntityIds) as $key => $batchData) {
                        $jobRunner->createDelayed(
                            sprintf('%s.chunk.%s', $jobUniqueName, ++$key),
                            function (JobRunner $jobRunner, Job $child) use ($originBody, $batchData) {
                                $originBody['parameters']['gridParameters']['_export']['ids'] = $batchData;

                                $this->producer->send(
                                    Topics::EXPORT,
                                    array_merge($originBody, ['jobId' => $child->getId()])
                                );
                            }
                        );
                    }
                } else {
                    $this->producer->send(
                        Topics::EXPORT,
                        array_merge($originBody, ['jobId' => $job->getId()])
                    );
                }


                $this->addDependedJob($job->getRootJob(), $originBody);

                $this->logger->info(
                    sprintf(
                        '[DataGridPreExportMessageProcessor] Scheduled %s entities for export.',
                        count($exportingEntityIds)
                    ),
                    ['messageBody' => $body]
                );

                return true;
            }
        );

        return $result ? self::ACK : self::REJECT;
    }

    /**
     * @return array
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
    private function splitOnBatch(array $ids)
    {
        return array_chunk($ids, $this->batchSize);
    }

    /**
     * @param Job $rootJob
     * @param array $body
     */
    private function addDependedJob(Job $rootJob, array $body)
    {
        $context = $this->dependentJob->createDependentJobContext($rootJob);

        $context->addDependentJob(ImportExportTopics::POST_EXPORT, [
            'jobId' => $rootJob->getId(),
            'email' => $this->getUser()->getEmail(),
            'jobName' => $body['parameters']['gridName'],
            'exportType' => ProcessorRegistry::TYPE_EXPORT,
            'outputFormat' => $body['format'],
        ]);

        $this->dependentJob->saveDependentJob($context);
    }
}
