<?php

namespace Oro\Bundle\EmailBundle\EmailSyncCredentials\NotificationSender;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\EmailBundle\EmailSyncCredentials\NotificationSenderInterface;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

/**
 * Wrong credential sync email box notification sender channel that uses email messaging as the channel.
 */
class EmailNotificationSender implements NotificationSenderInterface
{
    /** @var ConfigManager */
    private $configManager;

    /** @var \Swift_Mailer */
    private $mailer;

    /** @var EmailRenderer */
    protected $renderer;

    /** @var ManagerRegistry */
    protected $doctrine;

    /**
     * @param ConfigManager $configManager
     * @param EmailRenderer $renderer
     * @param ManagerRegistry $doctrine
     * @param \Swift_Mailer $mailer
     */
    public function __construct(
        ConfigManager $configManager,
        EmailRenderer $renderer,
        ManagerRegistry $doctrine,
        \Swift_Mailer $mailer
    ) {
        $this->mailer = $mailer;
        $this->configManager = $configManager;
        $this->renderer = $renderer;
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritdoc}
     */
    public function sendNotification(UserEmailOrigin $emailOrigin)
    {
        $senderEmail = $this->configManager->get('oro_notification.email_notification_sender_email');
        $senderName = $this->configManager->get('oro_notification.email_notification_sender_name');

        $originOwner = $emailOrigin->getOwner();
        if ($originOwner) {
            $templateName = 'sync_wrong_credentials_user_box';
            $sendTo = $emailOrigin->getOwner()->getEmail();
        } else {
            $templateName = 'sync_wrong_credentials_system_box';
            $sendTo = $senderEmail;
        }

        $template = $this->doctrine
            ->getManagerForClass(EmailTemplate::class)
            ->getRepository(EmailTemplate::class)
            ->findOneBy(['name' => $templateName]);

        $templateData = [
            'username' => $emailOrigin->getUser(),
            'host' => $emailOrigin->getImapHost()
        ];

        list($subjectRendered, $templateRendered) = $this->renderer->compileMessage($template, $templateData);
        $message = \Swift_Message::newInstance()
            ->setSubject($subjectRendered)
            ->setFrom($senderEmail, $senderName)
            ->setTo($sendTo)
            ->setBody($templateRendered, 'text/html');

        $this->mailer->send($message);
    }
}
