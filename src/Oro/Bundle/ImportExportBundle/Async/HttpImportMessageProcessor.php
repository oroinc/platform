<?php

namespace Oro\Bundle\ImportExportBundle\Async;

use Psr\Log\LoggerInterface;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ImportExportBundle\Handler\HttpImportHandler;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\NotificationBundle\Async\Topics as NotificationTopics;
use Oro\Bundle\SecurityProBundle\Tokens\ProUsernamePasswordOrganizationToken;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;

class HttpImportMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var HttpImportHandler
     */
    private $httpImportHandler;

    /**
     * @var JobRunner
     */
    private $jobRunner;

    /**
     * @var MessageProducerInterface
     */
    private $producer;

    /**
     * @var RegistryInterface
     */
    private $doctrine;

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var LoggerInterface
     */
    private $logger;

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

        $body = array_replace_recursive([
            'fileName' => null,
            'userId' => null,
            'jobName' => JobExecutor::JOB_IMPORT_FROM_CSV,
            'processorAlias' => null,
            'options' => [],
        ], $body);

        if (! $body['fileName'] || ! $body['processorAlias'] || ! $body['userId']) {
            $this->logger->critical(
                sprintf('Invalid message: %s', $body),
                ['message' => $message]
            );

            return self::REJECT;
        }

        $user =  $this->doctrine->getRepository(User::class)->find($body['userId']);
        if (! $user instanceof User) {
            $this->logger->error(
                sprintf('User not found: %s', $body['userId']),
                ['message' => $message]
            );

            return self::REJECT;
        }

        $result = $this->jobRunner->runUnique(
            $message->getMessageId(),
            sprintf('oro:import:http:%s:%s', $body['processorAlias'], $message->getMessageId()),
            function () use ($body, $user) {
                $this->authorizeUser($user);

                $this->httpImportHandler->setImportingFileName($body['fileName']);
                // should also send acl attribute
                $result = $this->httpImportHandler->handleImport(
                    $body['jobName'],
                    $body['processorAlias'],
                    $body['options']
                );

                $summary = sprintf(
                    'Import for the %s is completed, success: %s, info: %s, errors url: %s, message: %s',
                    $body['fileName'],
                    $result['success'],
                    $result['importInfo'],
                    $result['errorsUrl'],
                    $result['message']
                );

                $this->logger->info($summary);

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


                return $result['success'];
            }
        );

        return $result ? self::ACK : self::REJECT;
    }

    /**
     * @param User $user
     */
    protected function authorizeUser(User $user)
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

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::IMPORT_HTTP];
    }
}
