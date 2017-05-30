<?php

namespace Oro\Bundle\ImportExportBundle\Async;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\HttpKernel\Log\LoggerInterface;

use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;

use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\NotificationBundle\Async\Topics as NotificationTopics;

/**
 * @deprecated Renamed in 2.1 to SendImportErrorNotificationMessageProcessor
 */
class SendImportErrorInfoMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var MessageProducerInterface
     */
    private $producer;

    /**
     *  @var LoggerInterface
     */
    private $logger;

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var RegistryInterface
     */
    private $doctrine;

    /**
     * @param MessageProducerInterface $producer
     * @param LoggerInterface $logger
     * @param ConfigManager $configManager
     * @param RegistryInterface $doctrine
     */
    public function __construct(
        MessageProducerInterface $producer,
        LoggerInterface $logger,
        ConfigManager $configManager,
        RegistryInterface $doctrine
    ) {
        $this->producer = $producer;
        $this->logger = $logger;
        $this->configManager = $configManager;
        $this->doctrine = $doctrine;
    }

    /**
     * @var array
     * @deprecated Should be removed
     */
    protected static $stopStatuses = [Job::STATUS_SUCCESS, Job::STATUS_FAILED, Job::STATUS_CANCELLED];

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = JSON::decode($message->getBody());

        if (! isset($body['file'], $body['error']) &&
            ! (isset($body['userId']) || isset($body['notifyEmail']))
        ) {
            $this->logger->critical('Invalid message', ['message' => $body]);

            return self::REJECT;
        }

        if (!isset($body['notifyEmail']) || !$body['notifyEmail']) {
            $user = $this->doctrine->getRepository(User::class)->find($body['userId']);
            if (! $user instanceof User) {
                $this->logger->error(
                    sprintf('User not found. Id: %s', $body['userId']),
                    ['message' => $message]
                );

                return self::REJECT;
            }
            $notifyEmail = $user->getEmail();
        } else {
            $notifyEmail = $body['notifyEmail'];
        }

        $this->sendNotification(
            'Error importing file ' . $body['file'],
            $notifyEmail,
            'The import file could not be imported due to a fatal error. ' .
            'Please check its integrity and try again!'
        );

        return self::ACK;
    }

    /**
     * @param $subject
     * @param $toEmail
     * @param $summary
     */
    protected function sendNotification($subject, $toEmail, $summary)
    {
        $fromEmail = $this->configManager->get('oro_notification.email_notification_sender_email');
        $fromName = $this->configManager->get('oro_notification.email_notification_sender_name');
        $message = [
            'fromEmail' => $fromEmail,
            'fromName' => $fromName,
            'toEmail' => $toEmail,
            'subject' => $subject,
            'body' => $summary,
            'contentType' => 'text/html'
        ];

        $this->producer->send(
            NotificationTopics::SEND_NOTIFICATION_EMAIL,
            $message
        );

        $this->logger->info('Sent error message.', ['message' => $message]);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::SEND_IMPORT_ERROR_INFO];
    }
}
