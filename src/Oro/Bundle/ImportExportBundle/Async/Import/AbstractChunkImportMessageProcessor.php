<?php
namespace Oro\Bundle\ImportExportBundle\Async\Import;

use Psr\Log\LoggerInterface;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\ImportExportBundle\Handler\HttpImportHandler;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Exception\JobRedeliveryException;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Job\JobStorage;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;

abstract class AbstractChunkImportMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
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


    public function __construct(
        HttpImportHandler $httpImportHandler,
        JobRunner $jobRunner,
        MessageProducerInterface $producer,
        RegistryInterface $doctrine,
        TokenStorageInterface $tokenStorage,
        LoggerInterface $logger,
        JobStorage $jobStorage
    ) {
        $this->httpImportHandler = $httpImportHandler;
        $this->jobRunner = $jobRunner;
        $this->producer = $producer;
        $this->doctrine = $doctrine;
        $this->tokenStorage = $tokenStorage;
        $this->logger = $logger;
        $this->jobStorage = $jobStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = JSON::decode($message->getBody());

        if (! isset($body['jobId'], $body['userId'], $body['processorAlias'], $body['filePath'])) {
            $this->logger->critical(
                sprintf('Got invalid message. body: %s', $message->getBody()),
                ['message' => $message]
            );

            return self::REJECT;
        }

        $body = array_replace_recursive([
            'filePath' => null,
            'userId' => null,
            'jobId' => null,
            'jobName' => JobExecutor::JOB_IMPORT_FROM_CSV,
            'processorAlias' => null,
            'options' => [],
            ], $body);

        if (! ($user = $this->doctrine->getRepository(User::class)->find($body['userId'])) instanceof User) {
            $this->logger->error(
                sprintf('User not found. id: %s', $body['userId']),
                ['message' => $body]
            );

            return self::REJECT;
        }

        try {
            $result = $this
                ->jobRunner
                ->runDelayed(
                    $body['jobId'],
                    function (JobRunner $jobRunner, Job $job) use ($body, $user) {
                        $this->getCreateToken($user);
                        $result = $this->processData($body);
                        $this->saveJobResult($job, $result);
                        $summary = $this->getSummaryMessage(array_merge(['filePath' => $body['filePath']], $result));
                        $this->logger->info($summary);

                        return $result['success'];
                    }
                );
        } catch (JobRedeliveryException $exception) {
            return self::REQUEUE;
        }

        return $result ? self::ACK : self::REJECT;
    }

    abstract protected function processData(array $body);

    abstract protected function getSummaryMessage(array $result);

    protected function getCreateToken($user)
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
}
