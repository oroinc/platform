<?php
namespace Oro\Bundle\ImportExportBundle\Async\Import;

use Oro\Bundle\ImportExportBundle\Handler\HttpImportHandler;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\SecurityProBundle\Tokens\ProUsernamePasswordOrganizationToken;
use Oro\Bundle\UserBundle\Entity\User;
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
    private $jobStorage;


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

        $result = $this->jobRunner->runDelayed($body['jobId'], function (JobRunner $jobRunner, Job $job) use ($body) {
                $body = array_replace_recursive([
                        'fileName' => null,
                        'userId' => null,
                        'jobName' => JobExecutor::JOB_IMPORT_FROM_CSV,
                        'processorAlias' => null,
                        'options' => [],
                    ], $body);


                if (! $body['fileName'] || ! $body['processorAlias'] || ! $body['userId']) {
                    $this->logger->critical(
                        'Invalid message',
                        ['message' => $body]
                    );

                    return false;
                }

                $user = $this->doctrine->getRepository(User::class)->find($body['userId']);
                if (! $user instanceof User) {
                    $this->logger->error(
                        sprintf('User not found: %s', $body['userId']),
                        ['message' => $body]
                    );

                    return false;
                }

                $this->getCreateToken($user);
                $result = $this->processData($body);
                $result = array_merge(['fileName' => $body['fileName']], $result);
                $summary = $this->getSummaryMessage($result);
                $this->logger->info($summary);
                $this->saveJobResult($job, $result);

                return $result['success'];
        });

        return $result ? self::ACK : self::REJECT;
    }

    abstract protected function processData(array $body);

    abstract protected function getSummaryMessage(array $result);

    protected function getCreateToken($user)
    {
        $token = new ProUsernamePasswordOrganizationToken(
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
        unset($data['errorsUrl']);
        unset($data['message']);
        unset($data['importInfo']);
        $this->jobStorage->saveJob($job, function (Job $job) use ($data) {
            $job->setData($data);
        });
    }

    /**
     * {@inheritdoc}
     */
    abstract  public static function getSubscribedTopics();
}
