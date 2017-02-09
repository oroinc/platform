<?php

namespace Oro\Bundle\ImportExportBundle\Async\Import;

use Oro\Bundle\ImportExportBundle\Async\ImportExportResultSummarizer;

use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\ImportExportBundle\File\FileManager;

use Oro\Bundle\ImportExportBundle\Handler\HttpImportHandler;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Job\JobStorage;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

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
     * @var RegistryInterface
     */
    protected $doctrine;

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

    public function __construct(
        HttpImportHandler $httpImportHandler,
        JobRunner $jobRunner,
        MessageProducerInterface $producer,
        RegistryInterface $doctrine,
        TokenStorageInterface $tokenStorage,
        ImportExportResultSummarizer $importExportResultSummarizer,
        JobStorage $jobStorage,
        LoggerInterface $logger,
        FileManager $fileManager
    ) {
        $this->httpImportHandler = $httpImportHandler;
        $this->jobRunner = $jobRunner;
        $this->producer = $producer;
        $this->doctrine = $doctrine;
        $this->tokenStorage = $tokenStorage;
        $this->importExportResultSummarizer = $importExportResultSummarizer;
        $this->jobStorage = $jobStorage;
        $this->logger = $logger;
        $this->fileManager = $fileManager;
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

        if (! ($user = $this->doctrine->getRepository(User::class)->find($body['userId'])) instanceof User) {
            $this->logger->error(
                sprintf('User not found. id: %s', $body['userId']),
                ['message' => $body]
            );

            return self::REJECT;
        }

        $result = $this
            ->jobRunner
            ->runDelayed(
                $body['jobId'],
                function (JobRunner $jobRunner, Job $job) use ($body, $user) {
                    $this->initUserToken($user);
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
                        $this->importExportResultSummarizer->getSummaryMessage(
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

    protected function initUserToken($user)
    {
        $token = new UsernamePasswordOrganizationToken(
            $user,
            null,
            'import',
            $user->getOrganization(),
            $user->getRoles()
        );
        $this->tokenStorage->setToken($token);
    }

    protected function saveJobResult(Job $job, array $data)
    {
        unset($data['message']);
        unset($data['importInfo']);
        $job = $this->jobStorage->findJobById($job->getId());
        $job->setData($data);
        $this->jobStorage->saveJob($job);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::HTTP_IMPORT, Topics::IMPORT_HTTP, Topics::IMPORT_HTTP_VALIDATION];
    }

    /**
     * Method convert body old import topic to new
     * @deprecated (deprecated since 2.1 will be remove in 2.3)
     * @param $message
     */
    private function backwardCompatibilityMapper(MessageInterface &$message)
    {
        $topic = $message->getProperty(Config::PARAMETER_TOPIC_NAME);

        if ($topic !== Topics::IMPORT_HTTP && $topic !== Topics::IMPORT_HTTP_VALIDATION) {
            return;
        }
        $body = JSON::decode($message->getBody());

        if (! isset($body['jobId'], $body['userId'], $body['processorAlias'], $body['filePath'])) {
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
}
