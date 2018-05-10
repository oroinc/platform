<?php

namespace Oro\Bundle\NotificationBundle\Manager;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\NotificationBundle\Async\Topics;
use Oro\Bundle\NotificationBundle\Model\EmailNotificationInterface;
use Oro\Bundle\NotificationBundle\Model\MassNotification;
use Oro\Bundle\NotificationBundle\Model\SenderAwareEmailNotificationInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class EmailNotificationSender
{
    /** @var MessageProducerInterface */
    protected $producer;

    /** @var ConfigManager */
    private $configManager;

    /**
     * @param ConfigManager            $configManager
     * @param MessageProducerInterface $producer
     */
    public function __construct(ConfigManager $configManager, MessageProducerInterface $producer)
    {
        $this->configManager = $configManager;
        $this->producer = $producer;
    }

    /**
     * @param EmailNotificationInterface $notification
     * @param string                     $subject
     * @param string                     $body
     * @param string                     $contentType
     *
     * @throws \Oro\Component\MessageQueue\Transport\Exception\Exception
     */
    public function send(EmailNotificationInterface $notification, $subject, $body, $contentType)
    {
        if ($notification instanceof SenderAwareEmailNotificationInterface && $notification->getSenderEmail()) {
            $senderEmail = $notification->getSenderEmail();
            $senderName = $notification->getSenderName();
        } else {
            $senderEmail = $this->configManager->get('oro_notification.email_notification_sender_email');
            $senderName = $this->configManager->get('oro_notification.email_notification_sender_name');
        }

        foreach ($notification->getRecipientEmails() as $email) {
            // added RFC 822 check to avoid consumer fail
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $messageParams = [
                'fromEmail'   => $senderEmail,
                'fromName'    => $senderName,
                'toEmail'     => $email,
                'subject'     => $subject,
                'body'        => $body,
                'contentType' => $contentType
            ];

            if ($notification instanceof MassNotification) {
                $this->producer->send(Topics::SEND_MASS_NOTIFICATION_EMAIL, $messageParams);
            } else {
                $this->producer->send(Topics::SEND_NOTIFICATION_EMAIL, $messageParams);
            }
        }
    }

    /**
     * @param array  $messageParams
     *
     * @throws \Oro\Component\MessageQueue\Transport\Exception\Exception
     */
    protected function sendQueryMessage($messageParams = [])
    {
        $this->producer->send(Topics::SEND_NOTIFICATION_EMAIL, $messageParams);
    }
}
