<?php

namespace Oro\Bundle\NotificationBundle\Manager;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Mailer\Processor;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\NotificationBundle\Async\Topics;
use Oro\Bundle\NotificationBundle\Model\EmailNotificationInterface;
use Oro\Bundle\NotificationBundle\Model\SenderAwareEmailNotificationInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

use Psr\Log\LoggerInterface;

class EmailNotificationManager extends AbstractNotificationManager
{
    const TOPIC = Topics::SEND_NOTIFICATION_EMAIL;

    /** @var EmailRenderer */
    private $renderer;

    /** @var ConfigManager */
    private $cm;


    /**
     * EmailNotificationManager constructor.
     *
     * @param EmailRenderer $emailRenderer
     * @param ConfigManager $cm
     * @param MessageProducerInterface $producer
     * @param LoggerInterface $logger
     */
    public function __construct(
        EmailRenderer $emailRenderer,
        ConfigManager $cm,
        MessageProducerInterface $producer,
        LoggerInterface $logger
    ) {
        $this->renderer = $emailRenderer;
        $this->cm = $cm;
        $this->producer = $producer;
        $this->logger = $logger;
    }

    /**
     * Sends the email notifications
     *
     * @param mixed                        $object
     * @param EmailNotificationInterface[] $notifications
     * @param LoggerInterface              $logger Override for default logger. If this parameter is specified
     *                                             this logger will be used instead of a logger specified
     *                                             in the constructor
     * @param array                        $params Additional params for template renderer
     */
    public function process($object, $notifications, LoggerInterface $logger = null, $params = [])
    {
        if (!$logger) {
            $logger = $this->logger;
        }

        foreach ($notifications as $notification) {
            $emailTemplate = $notification->getTemplate();
            try {
                list ($subjectRendered, $templateRendered) = $this->renderer->compileMessage(
                    $emailTemplate,
                    ['entity' => $object] + $params
                );
            } catch (\Twig_Error $e) {
                $identity = method_exists($emailTemplate, '__toString')
                    ? (string)$emailTemplate : $emailTemplate->getSubject();

                $logger->error(
                    sprintf('Rendering of email template "%s" failed. %s', $identity, $e->getMessage()),
                    ['exception' => $e]
                );

                continue;
            }

            if ($notification instanceof SenderAwareEmailNotificationInterface && $notification->getSenderEmail()) {
                $senderEmail = $notification->getSenderEmail();
                $senderName = $notification->getSenderName();
            } else {
                $senderEmail = $this->cm->get('oro_notification.email_notification_sender_email');
                $senderName = $this->cm->get('oro_notification.email_notification_sender_name');
            }

            $type = 'txt' == $emailTemplate->getType() ? 'text/plain' : 'text/html';

            foreach ($notification->getRecipientEmails() as $email) {
                $this->sendQueryMessage([
                    'from' => [
                        'email' => $senderEmail,
                        'name' => $senderName,
                    ],
                    'to' => $email,
                    'subject' => $subjectRendered,
                    'body' => [
                        'body' => $templateRendered,
                        'contentType' => $type
                    ]
                ]);
            }
        }
    }

    /**
     * @inheritdoc
     */
    protected function sendQueryMessage($messageParams = [])
    {
        $this->producer->send(self::TOPIC, $messageParams);
    }
}