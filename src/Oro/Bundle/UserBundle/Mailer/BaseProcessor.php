<?php

namespace Oro\Bundle\UserBundle\Mailer;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\EmailBundle\Tools\EmailHolderHelper;
use Oro\Bundle\UserBundle\Entity\UserInterface;

class BaseProcessor
{
    /** @var ManagerRegistry */
    protected $managerRegistry;

    /** @var ConfigManager */
    protected $configManager;

    /** @var EmailRenderer */
    protected $renderer;

    /** @var EmailHolderHelper */
    protected $emailHolderHelper;

    /** @var \Swift_Mailer */
    protected $mailer;

    /**
     * @param ManagerRegistry   $managerRegistry
     * @param ConfigManager     $configManager
     * @param EmailRenderer     $renderer
     * @param EmailHolderHelper $emailHolderHelper
     * @param \Swift_Mailer     $mailer
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        ConfigManager $configManager,
        EmailRenderer $renderer,
        EmailHolderHelper $emailHolderHelper,
        \Swift_Mailer $mailer
    ) {
        $this->managerRegistry   = $managerRegistry;
        $this->configManager     = $configManager;
        $this->renderer          = $renderer;
        $this->emailHolderHelper = $emailHolderHelper;
        $this->mailer            = $mailer;
    }

    /**
     * @param UserInterface $user
     * @param array         $templateData
     * @param string        $type
     *
     * @return int          The return value is the number of recipients who were accepted for delivery
     */
    protected function sendEmail(UserInterface $user, array $templateData, $type)
    {
        list($subjectRendered, $templateRendered) = $templateData;

        $senderEmail = $this->configManager->get('oro_notification.email_notification_sender_email');
        $senderName  = $this->configManager->get('oro_notification.email_notification_sender_name');

        $email = $this->emailHolderHelper->getEmail($user);

        $message = \Swift_Message::newInstance()
            ->setSubject($subjectRendered)
            ->setFrom($senderEmail, $senderName)
            ->setTo($email)
            ->setBody($templateRendered, $type);

        return $this->mailer->send($message);
    }

    /**
     * @param string $emailTemplateName
     *
     * @return null|EmailTemplateInterface
     */
    protected function findEmailTemplateByName($emailTemplateName)
    {
        return $this->managerRegistry
            ->getManagerForClass('OroEmailBundle:EmailTemplate')
            ->getRepository('OroEmailBundle:EmailTemplate')
            ->findOneBy(['name' => $emailTemplateName]);
    }

    /**
     * @param UserInterface $user
     * @param string        $emailTemplateName
     * @param array         $emailTemplateParams
     *
     * @return int
     */
    public function getEmailTemplateAndSendEmail(
        UserInterface $user,
        $emailTemplateName,
        array $emailTemplateParams = []
    ) {
        $emailTemplate = $this->findEmailTemplateByName($emailTemplateName);

        return $this->sendEmail(
            $user,
            $this->renderer->compileMessage($emailTemplate, $emailTemplateParams),
            $this->getEmailTemplateType($emailTemplate)
        );
    }

    /**
     * @param EmailTemplateInterface $emailTemplate
     * @return string
     */
    protected function getEmailTemplateType(EmailTemplateInterface $emailTemplate)
    {
        return $emailTemplate->getType() === 'txt' ? 'text/plain' : 'text/html';
    }
}
