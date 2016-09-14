<?php

namespace Oro\Bundle\NotificationBundle\Manager;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Mailer\Processor;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\NotificationBundle\Async\Topics;
use Oro\Bundle\NotificationBundle\Model\EmailNotificationInterface;

use Oro\Bundle\NotificationBundle\Model\SenderAwareEmailNotificationInterface;
use Psr\Log\LoggerInterface;

class EmailNotificationManager extends AbstractNotificationManager
{
    const TOPIC = Topics::SEND_NOTIFICATION_EMAIL;

    /** @var EmailRenderer */
    private $renderer;

    /** @var \Swift_Mailer */
    private $mailer;

    /** @var ConfigManager */
    private $cm;

    /** @var Processor */
    private $processor;

    /**
     * Constructor
     *
     * @param LoggerInterface   $logger
     * @param EmailRenderer     $emailRenderer
     * @param \Swift_Mailer     $mailer
     * @param ConfigManager     $cm
     * @param Processor         $processor
     */
    public function __construct(
        LoggerInterface $logger,
        EmailRenderer $emailRenderer,
        \Swift_Mailer $mailer,
        ConfigManager $cm,
        Processor $processor
    ) {
        parent::__construct($logger);
        $this->renderer          = $emailRenderer;
        $this->mailer            = $mailer;
        $this->cm                = $cm;
        $this->processor         = $processor;
    }

    /**
     * @inheritdoc
     */
    public function process($object, $notifications, LoggerInterface $logger = null, $params = [])
    {
        if (!$logger) {
            $logger = $this->logger;
        }

        /** @var EmailNotificationInterface $notification */
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

            $senderEmail = $this->cm->get('oro_notification.email_notification_sender_email');
            $senderName = $this->cm->get('oro_notification.email_notification_sender_name');
            if ($notification instanceof SenderAwareEmailNotificationInterface && $notification->getSenderEmail()) {
                $senderEmail = $notification->getSenderEmail();
                $senderName = $notification->getSenderName();
            }

            if ($emailTemplate->getType() == 'txt') {
                $type = 'text/plain';
            } else {
                $type = 'text/html';
            }

            foreach ((array)$notification->getRecipientEmails() as $email) {
                $message = \Swift_Message::newInstance()
                    ->setSubject($subjectRendered)
                    ->setFrom($senderEmail, $senderName)
                    ->setTo($email)
                    ->setBody($templateRendered, $type);
                $this->processor->processEmbeddedImages($message);
                $this->mailer->send($message);
            }

            $this->addJob(self::SEND_COMMAND);
        }
    }
}