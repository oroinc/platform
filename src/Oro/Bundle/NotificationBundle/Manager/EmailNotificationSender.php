<?php

namespace Oro\Bundle\NotificationBundle\Manager;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\NotificationBundle\Async\Topics;
use Oro\Bundle\NotificationBundle\Model\EmailNotificationInterface;
use Oro\Bundle\NotificationBundle\Model\SenderAwareEmailNotificationInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class EmailNotificationSender
{
    const TOPIC = Topics::SEND_NOTIFICATION_EMAIL;

    /**
     * @var MessageProducerInterface
     */
    protected $producer;

    /**
     * @var ConfigManager
     */
    private $configManager;


    public function __construct(
        ConfigManager $configManager,
        MessageProducerInterface $producer
    ) {
        $this->configManager = $configManager;
        $this->producer = $producer;
    }

    /**
     * @param EmailNotificationInterface $notification
     * @param $subject
     * @param $body
     * @param $contentType
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
            $this->sendQueryMessage([
                'fromEmail' => $senderEmail,
                'fromName' => $senderName,
                'toEmail' => $email,
                'subject' => $subject,
                'body' => $body,
                'contentType' => $contentType
            ]);
        }
    }

    protected function sendQueryMessage($messageParams = [])
    {
        $this->producer->send(self::TOPIC, $messageParams);
    }
}
