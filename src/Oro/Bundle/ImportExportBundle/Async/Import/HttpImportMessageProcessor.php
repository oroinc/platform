<?php

namespace Oro\Bundle\ImportExportBundle\Async\Import;

use Psr\Log\LoggerInterface;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\ImportExportBundle\Async\ImportExportResultSummarizer;
use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Handler\HttpImportHandler;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\SecurityBundle\Authentication\TokenSerializerInterface;
use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Job\JobStorage;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;

class HttpImportMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var HttpImportHandler
     */
    protected $httpImportHandler;

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
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var JobStorage
     */
    protected $jobStorage;

    /**
     * @var ImportExportResultSummarizer
     */
    protected $importExportResultSummarizer;

    /**
     * @var FileManager
     */
    protected $fileManager;

    /**
     * @var TokenSerializerInterface
     */
    private $tokenSerializer;

    /**
     * @param HttpImportHandler $httpImportHandler
     * @param JobRunner $jobRunner
     * @param MessageProducerInterface $producer
     * @param TokenStorageInterface $tokenStorage
     * @param ImportExportResultSummarizer $importExportResultSummarizer
     * @param JobStorage $jobStorage
     * @param LoggerInterface $logger
     * @param FileManager $fileManager
     */
    public function __construct(
        HttpImportHandler $httpImportHandler,
        JobRunner $jobRunner,
        MessageProducerInterface $producer,
        TokenStorageInterface $tokenStorage,
        ImportExportResultSummarizer $importExportResultSummarizer,
        JobStorage $jobStorage,
        LoggerInterface $logger,
        FileManager $fileManager
    ) {
        $this->httpImportHandler = $httpImportHandler;
        $this->jobRunner = $jobRunner;
        $this->producer = $producer;
        $this->tokenStorage = $tokenStorage;
        $this->importExportResultSummarizer = $importExportResultSummarizer;
        $this->jobStorage = $jobStorage;
        $this->logger = $logger;
        $this->fileManager = $fileManager;
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
        $this->backwardCompatibilityMapper($message);
        $body = JSON::decode($message->getBody());

        if (! isset(
            $body['jobId'],
            $body['userId'],
            $body['securityToken'],
            $body['processorAlias'],
            $body['fileName'],
            $body['jobName'],
            $body['process'],
            $body['originFileName']
        )) {
            $this->logger->critical(
                sprintf('Got invalid message. body: %s', $message->getBody()),
                ['message' => $message]
            );

            return self::REJECT;
        }

        $body = array_replace_recursive(
            ['options' => []],
            $body
        );

        if (! $this->setSecurityToken($body['securityToken'])) {
            $this->logger->critical(
                sprintf('[HttpImportMessageProcessor] Cannot set security token'),
                ['message' => $message]
            );

            return self::REJECT;
        }

        $result = $this
            ->jobRunner
            ->runDelayed(
                $body['jobId'],
                function (JobRunner $jobRunner, Job $job) use ($body) {
                    $filePath = $this->fileManager->writeToTmpLocalStorage($body['fileName']);
                    $this->httpImportHandler->setImportingFileName($filePath);
                    $result = $this->httpImportHandler->handle(
                        $body['process'],
                        $body['jobName'],
                        $body['processorAlias'],
                        $body['options']
                    );
                    $this->saveJobResult($job, $result);
                    $this->logger->info(
                        $this->importExportResultSummarizer->getImportSummaryMessage(
                            array_merge(['originFileName' => $body['originFileName']], $result),
                            $body['process'],
                            $this->logger
                        ),
                        ['message' => $body]
                    );

                    return $result['success'];
                }
            );

        $this->fileManager->deleteFile($body['fileName']);

        return $result ? self::ACK : self::REJECT;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::HTTP_IMPORT, Topics::IMPORT_HTTP, Topics::IMPORT_HTTP_VALIDATION];
    }

    /**
     * @param Job $job
     * @param array $data
     */
    protected function saveJobResult(Job $job, array $data)
    {
        unset($data['message']);
        unset($data['importInfo']);

        $job = $this->jobStorage->findJobById($job->getId());
        $job->setData($data);
        $this->jobStorage->saveJob($job);
    }

    /**
     * Method convert body old import topic to new
     * @deprecated (deprecated since 2.1 will be remove in 2.3)
     * @param $message
     */
    private function backwardCompatibilityMapper(MessageInterface $message)
    {
        $topic = $message->getProperty(Config::PARAMETER_TOPIC_NAME);

        if ($topic !== Topics::IMPORT_HTTP && $topic !== Topics::IMPORT_HTTP_VALIDATION) {
            return;
        }
        $body = JSON::decode($message->getBody());

        if (! isset(
            $body['jobId'],
            $body['userId'],
            $body['processorAlias'],
            $body['filePath'],
            $body['securityToken']
        )) {
            return;
        }

        $this->fileManager->writeFileToStorage($body['filePath'], basename($body['filePath']), true);
        $body['fileName'] = basename($body['filePath']);

        if (Topics::IMPORT_HTTP === $topic) {
            $body['process'] = ProcessorRegistry::TYPE_IMPORT;
            $body['jobName'] = JobExecutor::JOB_IMPORT_FROM_CSV;
        } else {
            $body['process'] = ProcessorRegistry::TYPE_IMPORT_VALIDATION;
            $body['jobName'] = JobExecutor::JOB_IMPORT_VALIDATION_FROM_CSV;
        }

        $message->setBody(JSON::encode($body));
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
}
