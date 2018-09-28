<?php

namespace Oro\Bundle\NotificationBundle\Manager;

use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;
use Oro\Bundle\EmailBundle\Model\SenderAwareInterface;
use Oro\Bundle\NotificationBundle\Async\Topics;
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

    /**
     * @param NotificationSettings $notificationSettings
     * @param MessageProducerInterface $producer
     */
    public function __construct(NotificationSettings $notificationSettings, MessageProducerInterface $producer)
    {
        $this->notificationSettings = $notificationSettings;
        $this->producer = $producer;
    }

    /**
     * Sends ordinary notification.
     *
     * @param TemplateEmailNotificationInterface $notification
     * @param EmailTemplateInterface $template
     * @throws \Oro\Component\MessageQueue\Transport\Exception\Exception
     */
    public function send(TemplateEmailNotificationInterface $notification, EmailTemplateInterface $template): void
    {
        $this->sendNotification($notification, $template, Topics::SEND_NOTIFICATION_EMAIL);
    }

    /**
     * Sends mass notification.
     *
     * @param TemplateEmailNotificationInterface $notification
     * @param EmailTemplateInterface $template
     * @throws \Oro\Component\MessageQueue\Transport\Exception\Exception
     */
    public function sendMass(TemplateEmailNotificationInterface $notification, EmailTemplateInterface $template): void
    {
        $this->sendNotification($notification, $template, Topics::SEND_MASS_NOTIFICATION_EMAIL);
    }

    /**
     * @param TemplateEmailNotificationInterface $notification
     * @param EmailTemplateInterface $template
     * @param string $topic
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
            $email = $recipient->getEmail();
            // added RFC 822 check to avoid consumer fail
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $messageParams = [
                'sender'      => $sender->toArray(),
                'toEmail'     => $email,
                'subject'     => $template->getSubject(),
                'body'        => $template->getContent(),
                'contentType' => $template->getType()
            ];

            $this->producer->send($topic, $messageParams);
        }
    }
}
