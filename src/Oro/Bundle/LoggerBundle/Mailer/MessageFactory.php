<?php

namespace Oro\Bundle\LoggerBundle\Mailer;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LoggerBundle\DependencyInjection\Configuration;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * {@inheritDoc}
 */
class MessageFactory
{
    /**
     * We need to inject container instead of 'swiftmailer.mailer.default'
     * because the service doesn't exist at factory creation.
     * @var ContainerInterface
     */
    protected $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Creates a Swift_Message template that will be used to send the log message
     *
     * @param string $content formatted email body to be sent
     * @param array  $records Log records that formed the content
     * @return \Swift_Message
     */
    public function createMessage($content, array $records)
    {
        /** @var \Swift_Mailer $mailer */
        $mailer = $this->container->get('swiftmailer.mailer.default');

        /** @var \Swift_Message $message */
        $message = $mailer->createMessage();

        if (!$this->container->has('oro_config.global')) {
            return $message;
        }

        /** @var ConfigManager $config */
        $config = $this->container->get('oro_config.global');

        $recipients = $config->get(Configuration::getFullConfigKey(Configuration::EMAIL_NOTIFICATION_RECIPIENTS));
        if (!empty($recipients)) {
            $recipients = explode(';', $recipients);
            $message->setTo($recipients);
        }
        $sender = $config->get('oro_notification.email_notification_sender_email');
        if ($sender) {
            $message->setFrom($sender);
        }
        $subject = $config->get(Configuration::getFullConfigKey(Configuration::EMAIL_NOTIFICATION_SUBJECT));
        $message->setSubject($subject);
        $message->setContentType('text/html');

        return $message;
    }
}
