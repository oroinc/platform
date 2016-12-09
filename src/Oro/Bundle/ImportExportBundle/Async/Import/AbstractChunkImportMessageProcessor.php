<?php
namespace Oro\Bundle\ImportExportBundle\Async\Import;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ImportExportBundle\Handler\HttpImportHandler;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\SecurityProBundle\Tokens\ProUsernamePasswordOrganizationToken;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Oro\Bundle\NotificationBundle\Async\Topics as NotificationTopics;

abstract class AbstractChunkImportMessageProcessor  implements MessageProcessorInterface, TopicSubscriberInterface
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
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        HttpImportHandler $httpImportHandler,
        JobRunner $jobRunner,
        MessageProducerInterface $producer,
        RegistryInterface $doctrine,
        ConfigManager $configManager,
        TokenStorageInterface $tokenStorage,
        LoggerInterface $logger
    ) {
        $this->httpImportHandler = $httpImportHandler;
        $this->jobRunner = $jobRunner;
        $this->producer = $producer;
        $this->doctrine = $doctrine;
        $this->configManager = $configManager;
        $this->tokenStorage = $tokenStorage;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = JSON::decode($message->getBody());

        $result = $this->jobRunner->runDelayed($body['jobId'], function (JobRunner $jobRunner) use ($body) {
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
                $summary = $this->getSummaryMessage(array_merge(['fileName' => $body['fileName']], $result));

                $this->logger->info($summary);
                $this->sendNotification($result, $user, $summary);

                return !!$result['success'];
            }
        );

        return $result ? self::ACK : self::REJECT;
    }

    abstract protected function processData(array $body);

    abstract protected function getSummaryMessage(array $result);

    protected function getCreateToken($user)
    {
        $token = new ProUsernamePasswordOrganizationToken($user, null, 'import', $user->getOrganization(), $user->getRoles());
        $this->tokenStorage->setToken($token);
    }

    protected function sendNotification($result, $user, $summary)
    {
        $fromEmail = $this->configManager->get('oro_notification.email_notification_sender_email');
        $fromName = $this->configManager->get('oro_notification.email_notification_sender_name');

        $this->producer->send(
            NotificationTopics::SEND_NOTIFICATION_EMAIL,
            [
                'fromEmail' => $fromEmail,
                'fromName' => $fromName,
                'toEmail' => $user->getEmail(),
                'subject' => $result['message'],
                'body' => $summary
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    abstract  public static function getSubscribedTopics();
}