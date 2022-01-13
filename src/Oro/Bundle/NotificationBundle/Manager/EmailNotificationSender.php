<?php

namespace Oro\Bundle\NotificationBundle\Manager;

use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;
use Oro\Bundle\EmailBundle\Model\SenderAwareInterface;
use Oro\Bundle\NotificationBundle\Async\Topic\SendEmailNotificationTopic;
use Oro\Bundle\NotificationBundle\Async\Topic\SendMassEmailNotificationTopic;
use Oro\Bundle\NotificationBundle\Model\NotificationSettings;
use Oro\Bundle\NotificationBundle\Model\TemplateEmailNotificationInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * Produces MQ messages for email notifications for attendees specified in the notification message.
 */
class EmailNotificationSender
{
    /**
     * @var MessageProducerInterface
     */
    private $producer;

    /**
     * @var NotificationSettings
     */
    private $notificationSettings;

    public function __construct(NotificationSettings $notificationSettings, MessageProducerInterface $producer)
    {
        $this->notificationSettings = $notificationSettings;
        $this->producer = $producer;
    }

    /**
     * Sends ordinary notification.
     *
     * @throws \Oro\Component\MessageQueue\Transport\Exception\Exception
     */
    public function send(TemplateEmailNotificationInterface $notification, EmailTemplateInterface $template): void
    {
        $this->sendNotification($notification, $template, SendEmailNotificationTopic::getName());
    }

    /**
     * Sends mass notification.
     *
     * @throws \Oro\Component\MessageQueue\Transport\Exception\Exception
     */
    public function sendMass(TemplateEmailNotificationInterface $notification, EmailTemplateInterface $template): void
    {
        $this->sendNotification($notification, $template, SendMassEmailNotificationTopic::getName());
    }

    /**
     * @throws \Oro\Component\MessageQueue\Transport\Exception\Exception
     */
    private function sendNotification(
        TemplateEmailNotificationInterface $notification,
        EmailTemplateInterface $template,
        string $topic
    ): void {
        $sender = ($notification instanceof SenderAwareInterface && $notification->getSender())
            ? $notification->getSender()
            : $this->notificationSettings->getSender();

        foreach ($notification->getRecipients() as $recipient) {
            $messageParams = [
                'from' => $sender->toString(),
                'toEmail' => $recipient->getEmail(),
                'subject' => $template->getSubject(),
                'body' => $template->getContent(),
                'contentType' => $template->getType(),
            ];

            $this->producer->send($topic, $messageParams);
        }
    }
}
