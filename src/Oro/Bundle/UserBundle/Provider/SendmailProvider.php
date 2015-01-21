<?php

namespace Oro\Bundle\UserBundle\Provider;

use Psr\Log\LoggerInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;

class SendmailProvider
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var EmailRenderer */
    protected $renderer;

    /** @var  UserManager */
    protected $userManager;

    /** @var \Swift_Mailer */
    protected $mailer;

    /** @var  LoggerInterface */
    protected $logger;

    /**
     * @param EmailRenderer       $renderer
     * @param ConfigManager       $configManager
     * @param LoggerInterface     $logger
     * @param \Swift_Mailer       $mailer
     */
    public function __construct(
        ConfigManager    $configManager,
        EmailRenderer    $renderer,
        LoggerInterface  $logger,
        \Swift_Mailer    $mailer = null
    ) {
        $this->configManager = $configManager;
        $this->renderer      = $renderer;
        $this->mailer        = $mailer;
        $this->logger        = $logger;
    }

    /**
     * @param User $entity
     * @param EmailTemplateInterface $emailTemplate
     * @param array $additionalParams
     */
    public function sendEmail(User $entity, $emailTemplate, $additionalParams = [])
    {
        try {
            list ($subjectRendered, $templateRendered) = $this->renderer->compileMessage(
                $emailTemplate,
                array_merge(['entity' => $entity], $additionalParams)
            );

            $senderEmail = $this->configManager->get('oro_notification.email_notification_sender_email');
            $senderName = $this->configManager->get('oro_notification.email_notification_sender_name');
            $type = $emailTemplate->getType() == 'txt' ? 'text/plain' : 'text/html';
            $message = \Swift_Message::newInstance()
                ->setSubject($subjectRendered)
                ->setFrom($senderEmail, $senderName)
                ->setTo($entity->getEmail())
                ->setBody($templateRendered, $type);
            $this->mailer->send($message);
        } catch (\Twig_Error $e) {
            $identity = method_exists($emailTemplate, '__toString')
                ? (string)$emailTemplate : $emailTemplate->getSubject();

            $this->logger->error(
                sprintf('Rendering of email template "%s" failed. %s', $identity, $e->getMessage()),
                ['exception' => $e]
            );
        }
    }

    /**
     * Check configure
     */
    public function checkSendmailConfigured()
    {
        if (in_array(null, [$this->configManager, $this->mailer], true)) {
            throw new \RuntimeException('Unable to send invitation email, unmet dependencies detected.');
        }
    }
}
