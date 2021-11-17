<?php

namespace Oro\Bundle\LoggerBundle\Monolog\EmailFactory;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LoggerBundle\DependencyInjection\Configuration;
use Oro\Bundle\LoggerBundle\Provider\ErrorLogNotificationRecipientsProvider;
use Symfony\Component\Mime\Email as SymfonyEmail;

/**
 * Creates a SymfonyEmail template that will be used to send the log message.
 */
class ErrorLogNotificationEmailFactory
{
    private ConfigManager $configManager;

    private ErrorLogNotificationRecipientsProvider $errorLogNotificationRecipientsProvider;

    public function __construct(
        ConfigManager $configManager,
        ErrorLogNotificationRecipientsProvider $errorLogNotificationRecipientsProvider
    ) {
        $this->configManager = $configManager;
        $this->errorLogNotificationRecipientsProvider = $errorLogNotificationRecipientsProvider;
    }

    /**
     * @param string $content Formatted email body to be sent
     * @param array $records Log records that formed the content
     *
     * @return SymfonyEmail
     *
     * @see \Symfony\Bridge\Monolog\Handler\MailerHandler::buildMessage
     */
    public function createEmail(string $content, array $records): SymfonyEmail
    {
        $message = new SymfonyEmail();

        $subject = (string)$this->configManager->get(
            Configuration::getFullConfigKey(Configuration::EMAIL_NOTIFICATION_SUBJECT)
        );
        $message->subject($subject);

        $sender = (string)$this->configManager->get('oro_notification.email_notification_sender_email');
        $message->from($sender);

        $recipients = $this->errorLogNotificationRecipientsProvider->getRecipientsEmailAddresses();
        if ($recipients) {
            $message->to(...$recipients);
        }

        return $message;
    }
}
