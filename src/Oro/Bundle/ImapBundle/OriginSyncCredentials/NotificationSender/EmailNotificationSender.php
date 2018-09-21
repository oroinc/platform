<?php

namespace Oro\Bundle\ImapBundle\OriginSyncCredentials\NotificationSender;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\EmailBundle\Manager\TemplateEmailManager;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\ImapBundle\OriginSyncCredentials\NotificationSenderInterface;
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

    /** @var TemplateEmailManager */
    private $templateEmailManager;

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
     * @param TemplateEmailManager $templateEmailManager
     */
    public function setTemplateEmailManager(TemplateEmailManager $templateEmailManager): void
    {
        $this->templateEmailManager = $templateEmailManager;
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
            $sendTo = $emailOrigin->getOwner();
        } else {
            $templateName = 'sync_wrong_credentials_system_box';
            $sendTo = $emailOrigin->getMailbox();
        }

        $templateCondition = [
            'name' => $templateName
        ];

        $templateParameters = [
            'username' => $emailOrigin->getUser(),
            'host' => $emailOrigin->getImapHost()
        ];

        if ($this->templateEmailManager) {
            $this->templateEmailManager->sendTemplateEmail(
                From::emailAddress($senderEmail, $senderName),
                [$sendTo],
                new EmailTemplateCriteria($templateName),
                $templateParameters
            );
        } else {
            $this->sendEmail($templateCondition, $templateParameters, $senderEmail, $senderName, $sendTo->getEmail());
        }
    }

    /**
     * @deprecated since 2.6. Will be removed in 3.0
     *
     * @param array $templateCondition
     * @param array $templateParameters
     * @param string $senderEmail
     * @param string $senderName
     * @param string $sendTo
     */
    private function sendEmail(
        array $templateCondition,
        array $templateParameters,
        $senderEmail,
        $senderName,
        $sendTo
    ) {
        $template = $this->doctrine
            ->getManagerForClass(EmailTemplate::class)
            ->getRepository(EmailTemplate::class)
            ->findOneBy($templateCondition);

        list($subjectRendered, $templateRendered) = $this->renderer->compileMessage($template, $templateParameters);
        $message = \Swift_Message::newInstance()
            ->setSubject($subjectRendered)
            ->setFrom($senderEmail, $senderName)
            ->setTo($sendTo)
            ->setBody($templateRendered, 'text/html');

        $this->mailer->send($message);
    }
}
